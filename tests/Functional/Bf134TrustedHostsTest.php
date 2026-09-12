<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * BF-134 — die Anwendung darf ihre absoluten Links nicht aus einem beliebigen
 * `Host`-Header bauen.
 *
 * Vor der Reparatur ausgenutzt: Mit gefälschtem `Host` **und** passendem `Referer`
 * und `Origin` entstand ein Konto, und der Bestätigungslink in der ausgehenden Mail
 * lautete `http://boeser-server.example/de/verify/…` — eine authentische Mail des
 * Betreibers samt seiner SPF/DKIM-Signaturen, deren Link auf den Server des
 * Angreifers zeigt. Nach der Reparatur: **HTTP 400**, kein Konto, keine Mail.
 *
 * ⚠ **Mit bloß gefälschtem `Host` griff schon vorher der stateless-CSRF-Schutz**
 * (422). Wer nur das probiert, hält die Lücke für geschlossen — deshalb steht hier
 * der vollständige Angriff mit allen drei Kopfzeilen.
 *
 * ⚠ **Dieser Lauf schützt vor allem die Erreichbarkeit.** `trusted_hosts` ist eine
 * harte Sperre: Fehlt ein legitimer Eintrag, antwortet die Anwendung mit 400 — und
 * wenn das den Healthcheck trifft, gilt ein frischer Container als krank und Coolify
 * rollt zurück (das Muster von BF-116). Die Positivfälle unten sind deshalb genauso
 * wichtig wie der Negativfall.
 */
final class Bf134TrustedHostsTest extends WebTestCase
{
    /**
     * ⚠ `127.0.0.1` ist der Host, den der `HEALTHCHECK` des Images ruft
     * (`http://127.0.0.1/health`), `localhost` der des Testclients. Beide **müssen**
     * in der Liste bleiben; `endlech.lu` und `www.endlech.lu` sind die live
     * gemessenen Hostnamen (beide antworten mit einem Redirect auf sich selbst).
     *
     * @return iterable<string, array{string}>
     */
    public static function erlaubteHosts(): iterable
    {
        yield 'Healthcheck des Images' => ['127.0.0.1'];
        yield 'Testclient und lokale Entwicklung' => ['localhost'];
        yield 'Produktion' => ['endlech.lu'];
        yield 'Produktion mit www' => ['www.endlech.lu'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fremdeHosts(): iterable
    {
        yield 'fremde Domain' => ['boeser-server.example'];
        // ⚠ Der Anker `$` im Muster trägt diesen Fall: Ohne ihn wäre jede Domain
        // erlaubt, die mit unserer beginnt.
        yield 'eigene Domain als Präfix' => ['endlech.lu.angreifer.test'];
        yield 'eigene Domain als Teilstring' => ['nicht-endlech.lu'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('erlaubteHosts')]
    public function testErlaubterHostWirdBedient(string $host): void
    {
        $client = static::createClient();
        $client->request('GET', '/health', server: ['HTTP_HOST' => $host]);

        self::assertResponseIsSuccessful(
            sprintf('BF-134: „%s" muss bedient werden — sonst bricht Healthcheck oder Betrieb.', $host),
        );
    }

    /**
     * ⚠ **Geprüft wird an der geladenen Konfiguration, nicht über HTTP.** Der
     * naheliegende Weg — Request mit fremdem `HTTP_HOST` durch den Testclient —
     * trägt nicht: Dort scheitert schon der `LocaleSubscriber` an der fehlenden
     * Session (`BadRequestHttpException: Session has not been set.`), bevor
     * `getHost()` überhaupt gerufen wird. Das ist ein Artefakt des Testclients; **am
     * laufenden Server antwortet dieselbe Anfrage mit HTTP 400** (gemessen für
     * `boeser-server.example` und `endlech.lu.angreifer.test`).
     *
     * Dieser Lauf nimmt die Muster, die der Container tatsächlich geladen hat, und
     * hält sie gegen `Request::getHost()` — die Stelle, an der Symfony verwirft.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('fremdeHosts')]
    public function testFremderHostWirdAbgewiesen(string $host): void
    {
        static::createClient();   // bootet den Kernel und setzt die trusted_hosts

        self::assertNotEmpty(
            Request::getTrustedHosts(),
            'Vorbedingung: Ohne konfigurierte trusted_hosts prüft dieser Lauf nichts.',
        );

        $this->expectException(SuspiciousOperationException::class);

        Request::create(sprintf('http://%s/health', $host))->getHost();
    }
}
