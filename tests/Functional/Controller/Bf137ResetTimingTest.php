<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * BF-137 — die Anti-Enumeration des Passwort-Resets darf sich auch nicht über die
 * Laufzeit verraten.
 *
 * Die Antwort war schon immer identisch (302 auf `/de/login`), die Dauer nicht:
 * gemessen **31–36 ms** für eine bekannte gegen **23–24 ms** für eine unbekannte
 * Adresse — zwei Bereiche ohne Überlappung. Eine einzige Messung genügte für die
 * Frage „hat diese Person hier ein Konto?".
 *
 * ⚠ **Dieser Lauf misst die Untergrenze, nicht den Unterschied.** Ein Vergleich
 * zweier Laufzeiten wäre auf einem geteilten Prüfrechner notorisch wackelig — schon
 * ein Hintergrundprozess kippt ihn. Geprüft wird deshalb die Eigenschaft, die die
 * Reparatur herstellt: **Beide Zweige brauchen mindestens so lange wie die
 * festgelegte Mindestdauer.** Fällt einer darunter, ist der Angleich weg.
 */
final class Bf137ResetTimingTest extends AbstractWebTestCase
{
    /** Muss zu PasswordResetController::MINDESTDAUER_SEKUNDEN passen. */
    private const MINDESTDAUER_MS = 120;

    /**
     * ⚠ Grosszügiger Abschlag: Der Prüfrechner misst die Wandzeit inklusive
     * Framework-Overhead, und `usleep()` garantiert nur eine Untergrenze. Zu knapp
     * gesetzt wäre dieser Lauf sprunghaft — und ein sprunghafter Lauf wird
     * abgeschaltet statt gelesen.
     */
    private const TOLERANZ_MS = 20;

    public function testBekannteAdresseHaeltDieMindestdauer(): void
    {
        self::assertGreaterThanOrEqual(
            self::MINDESTDAUER_MS - self::TOLERANZ_MS,
            $this->dauerFuer('user@endlech.lu'),
            'BF-137: Der Zweig mit Konto muss die Mindestdauer halten.',
        );
    }

    public function testUnbekannteAdresseHaeltDieselbeMindestdauer(): void
    {
        self::assertGreaterThanOrEqual(
            self::MINDESTDAUER_MS - self::TOLERANZ_MS,
            $this->dauerFuer('gibt-es-garantiert-nicht@example.invalid'),
            'BF-137: Ohne Konto passiert fachlich nichts — genau deshalb muss die '
            .'Dauer angeglichen werden, sonst verrät sie die Existenz des Kontos.',
        );
    }

    /**
     * Die fachliche Zusage bleibt: gleiche Antwort, gleiches Ziel.
     */
    public function testBeideZweigeAntwortenGleich(): void
    {
        $client = static::createClient();

        $a = $this->sende($client, 'user@endlech.lu');
        $b = $this->sende($client, 'auch-nicht-vorhanden@example.invalid');

        self::assertSame($a, $b, 'Statuscode und Ziel müssen übereinstimmen.');
    }

    private function dauerFuer(string $adresse): float
    {
        $client = static::createClient();

        $start = microtime(true);
        $this->sende($client, $adresse);

        return (microtime(true) - $start) * 1000;
    }

    /**
     * @return array{int, string} Statuscode und Weiterleitungsziel
     */
    private function sende(KernelBrowser $client, string $adresse): array
    {
        $crawler = $client->request('GET', self::LOCALE.'/passwort-vergessen');

        $client->submit($this->formWithField($crawler, 'password_reset_request[email]', [
            'password_reset_request[email]' => $adresse,
        ]));

        $antwort = $client->getResponse();

        return [$antwort->getStatusCode(), (string) $antwort->headers->get('Location')];
    }
}
