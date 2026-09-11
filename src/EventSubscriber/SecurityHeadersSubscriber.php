<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Setzt die Sicherheits-Kopfzeilen, die keine Antwort von selbst mitbringt.
 *
 * Gemessen am 2026-09-11 auf Produktion: Die Startseite lieferte **keinen
 * einzigen** dieser Header — weder HSTS noch `nosniff`, `X-Frame-Options`,
 * Referrer- oder Permissions-Policy. Das Basisimage bringt sie nicht mit, und
 * eine eigene Caddy-Konfiguration gibt es in diesem Projekt nicht.
 *
 * ⚠ **Warum hier und nicht in der Caddyfile.** Der naheliegende Ort wäre der
 * Webserver — dort erreichten die Header auch die statischen Dateien unter
 * `/build/`. Dagegen stehen drei Dinge: Die Standard-Caddyfile des
 * FrankenPHP-Image müsste dafür ersetzt werden (ein Fehler darin nimmt die
 * Seite offline — siehe BF-116, wo genau das passiert ist); der Weg über
 * `CADDY_SERVER_EXTRA_DIRECTIVES` wäre eine weitere Variable, die jemand in
 * Coolify von Hand setzen muss und beim nächsten Umzug vergisst (siehe
 * `TRUSTED_PROXIES`); und ein Prüflauf kann eine Caddyfile nicht messen, diesen
 * Subscriber dagegen schon. Praktisch trägt das: Jeder Besucher lädt zuerst ein
 * Dokument, und HSTS gilt danach für die ganze Herkunft, statische Dateien
 * eingeschlossen.
 *
 * ⚠ **Die Header werden nicht überschrieben, wenn sie schon stehen.** Sonst
 * nähme dieser Subscriber einer Antwort, die es besser weiß, die eigene Angabe
 * weg — etwa einer, die `frame-ancestors` für einen Einbettungsfall lockert.
 */
final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    /**
     * Gilt für jede Antwort, auch JSON und Weiterleitungen.
     *
     * `nosniff` ist gerade bei den Daten-Endpunkten wichtig: `/open/dataset.csv`
     * liefert fremdbestimmten Inhalt, und ein Browser, der den Typ errät, kann
     * daraus HTML machen.
     */
    private const ALLE = [
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    /**
     * Nur für Dokumente — bei JSON und Bildern ohne Bedeutung.
     *
     * `X-Frame-Options` steht neben `frame-ancestors` in der CSP, weil ältere
     * Browser die CSP-Richtlinie nicht kennen. Doppelt, aber nicht widersprüchlich.
     */
    private const NUR_HTML = [
        'X-Frame-Options' => 'DENY',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
    ];

    /**
     * ⚠ **Report-Only, und das ist Absicht.** Eine scharfe Richtlinie, die
     * irgendwo zu eng ist, nimmt die Seite nicht offline — sie nimmt ihr das
     * JavaScript, und damit den Passkey-Knopf, den Wizard und Turbo. Das sähe
     * aus wie ein Frontend-Fehler und wäre keiner (dasselbe Muster wie beim
     * fehlenden `TRUSTED_PROXIES`, wo der Anmeldeknopf schlicht nichts tat).
     *
     * Die Richtlinie bildet den **gemessenen** Bestand ab: keine Inline-Skripte
     * in `templates/` (geprüft), aber Inline-`style=` in 23 Dateien, deshalb
     * `'unsafe-inline'` für Stile. Externe Verweise sind Links, keine geladenen
     * Ressourcen.
     *
     * Wer sie scharf schaltet, tauscht den Kopfzeilennamen gegen
     * `Content-Security-Policy` — **nachdem** er in der Browser-Konsole
     * nachgesehen hat, dass über mehrere Seiten kein Verstoß mehr gemeldet wird.
     */
    private const CSP = "default-src 'self'; "
        ."script-src 'self'; "
        ."style-src 'self' 'unsafe-inline'; "
        ."img-src 'self' data:; "
        ."font-src 'self'; "
        ."connect-src 'self'; "
        ."form-action 'self'; "
        ."frame-ancestors 'none'; "
        ."base-uri 'self'; "
        .'object-src \'none\'';

    public function __construct(private readonly bool $debug)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [['onKernelResponse', -100]],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;

        foreach (self::ALLE as $name => $wert) {
            if (!$headers->has($name)) {
                $headers->set($name, $wert);
            }
        }

        // ⚠ Die Seite verrät sonst die genaue Patch-Version des Interpreters
        // (gemessen: `x-powered-by: PHP/8.4.25`). Das ist keine Lücke für sich,
        // erspart einem Angreifer aber die Frage, welche Lücken überhaupt in
        // Betracht kommen. `expose_php=Off` in der php.ini nimmt es an der
        // Quelle; diese Zeile deckt jeden Betrieb ohne das Container-Image ab.
        $headers->remove('X-Powered-By');

        if (!$this->istDokument($response)) {
            return;
        }

        foreach (self::NUR_HTML as $name => $wert) {
            if (!$headers->has($name)) {
                $headers->set($name, $wert);
            }
        }

        if (!$headers->has('Content-Security-Policy-Report-Only')
            && !$headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy-Report-Only', self::CSP);
        }

        // ⚠ **HSTS nur über HTTPS und nie im Debug-Betrieb.** Über `http://`
        // ist die Kopfzeile nach RFC 6797 ohnehin zu verwerfen; lokal gesetzt
        // zwingt sie den Browser dauerhaft auf `https://localhost`, und das
        // hält sich hartnäckig, bis jemand die Herkunft von Hand aus
        // `chrome://net-internals/#hsts` löscht.
        //
        // ⚠ Kein `preload` und kein `includeSubDomains`: Beides ist eine Zusage
        // über Namen, die es noch gar nicht gibt, und die Preload-Liste ist
        // praktisch nicht mehr zu verlassen. Ein Jahr `max-age` auf der
        // Hauptherkunft ist die Zusage, die das Projekt heute halten kann.
        if (!$this->debug && $event->getRequest()->isSecure() && !$headers->has('Strict-Transport-Security')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
    }

    /**
     * Nur echte Dokumente bekommen die Dokument-Kopfzeilen.
     *
     * Eine Weiterleitung zählt mit: Der Browser bewertet die CSP der Antwort,
     * die das Dokument liefert — aber eine 302 auf `/de/login` trägt selbst
     * einen HTML-Rumpf, und `frame-ancestors` soll auch dort gelten.
     */
    private function istDokument(\Symfony\Component\HttpFoundation\Response $response): bool
    {
        $typ = $response->headers->get('Content-Type', '');

        return '' === $typ || str_contains($typ, 'text/html');
    }
}
