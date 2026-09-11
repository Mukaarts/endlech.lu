<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Belegt, dass die Kopfzeilen an einer echten Antwort ankommen.
 *
 * Der Unit-Test prüft die Logik des Subscribers; er bliebe grün, wenn niemand
 * ihn registriert. Genau das ist der Fall, den dieser Lauf abdeckt — am
 * 2026-09-11 lieferte die Produktion **keinen einzigen** dieser Header.
 *
 * ⚠ Gemessen wird an `/health`: Die Route kommt bewusst ohne Datenbank aus und
 * ist sprachfrei, der Lauf hängt damit an nichts, was ihn aus anderen Gründen
 * rot färben könnte.
 */
final class SecurityHeadersTest extends WebTestCase
{
    public function testEchteAntwortTraegtDieKopfzeilen(): void
    {
        $antwort = $this->rufe()->getResponse();

        self::assertSame('nosniff', $antwort->headers->get('X-Content-Type-Options'));
        self::assertSame('strict-origin-when-cross-origin', $antwort->headers->get('Referrer-Policy'));
    }

    public function testXPoweredByVerlaesstDieAnwendungNicht(): void
    {
        self::assertFalse($this->rufe()->getResponse()->headers->has('X-Powered-By'));
    }

    /**
     * ⚠ Über `http://` gehört HSTS nicht gesetzt — der Testclient spricht
     * unverschlüsselt, also darf die Kopfzeile hier fehlen. Wäre sie da, würde
     * sie es auch auf einer Entwicklungsmaschine tun und den Browser dauerhaft
     * auf `https://localhost` zwingen.
     */
    public function testKeinHstsOhneVerschluesselung(): void
    {
        self::assertFalse($this->rufe()->getResponse()->headers->has('Strict-Transport-Security'));
    }

    private function rufe(): KernelBrowser
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();

        return $client;
    }
}
