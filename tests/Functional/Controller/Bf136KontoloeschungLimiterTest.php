<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * BF-136 — die Kontolöschung prüft das Passwort und braucht deshalb einen Deckel.
 *
 * Gemessen vor der Reparatur: **20 Fehlversuche in Folge, alle HTTP 302, keine
 * Bremse.** Dieselbe Geheimnisprüfung ist beim Passwortwechsel seit BF-20 gedeckelt;
 * hier fehlte sie, und die Folge ist schwerer — eine Löschung ist unumkehrbar.
 *
 * ⚠ **Geprüft wird der Verbrauch, nicht der Statuscode.** Beide Fälle — falsches
 * Passwort und erschöpftes Kontingent — antworten mit 302 auf `/de/profile`; ein
 * Test, der nur den Code ansieht, bliebe grün, während der Deckel fehlt. Genau so
 * ist der Befund entstanden. Maßgeblich ist `getRemainingTokens()`.
 */
final class Bf136KontoloeschungLimiterTest extends AbstractWebTestCase
{
    private const KONTO = 'user@endlech.lu';

    /**
     * ⚠ **Der Zähler des Limiters liegt NICHT in der DAMA-Transaktion.** Er steht im
     * Cache-Pool und übersteht sowohl den Rollback zwischen zwei Tests als auch das
     * Ende des Prüflaufs. Ohne dieses Zurücksetzen färbt ein Test, der das Kontingent
     * leerläuft, den nächsten rot — und zwar erst beim **zweiten** Aufruf der Suite,
     * was die Ursache schwer findbar macht. Beim Schreiben dieser Datei genau so
     * passiert: `testFehlversuchVerbrauchtKontingent` schlug mit
     * „0 is identical to -1" fehl, nachdem der Erschöpfungstest einmal allein gelaufen
     * war.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();
        $factory = $client->getContainer()->get('limiter.account_delete');
        self::assertInstanceOf(RateLimiterFactoryInterface::class, $factory);
        $factory->create(self::KONTO)->reset();
        $factory->create('jemand-anders@endlech.lu')->reset();

        self::ensureKernelShutdown();
    }


    /**
     * ⚠ Der Verbrauch steht **vor** der Passwortprüfung — anders als bei
     * Registrierung und Wartelisten (BF-11). Dort ist ein Fehlversuch ein
     * Tippfehler, hier **ist** er der Angriff.
     */
    public function testFehlversuchVerbrauchtKontingent(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);

        $vorher = $this->restkontingent($client);
        $this->versucheLoeschung($client, 'voellig-falsches-passwort');
        $nachher = $this->restkontingent($client);

        self::assertSame(
            $vorher - 1,
            $nachher,
            'BF-136: Ein Fehlversuch muss das Kontingent verbrauchen — sonst ist der Deckel wirkungslos.',
        );
    }

    /**
     * Die Gegenprobe zum Befund: Das Konto darf den Fehlversuch überleben.
     */
    public function testFalschesPasswortLoeschtWeiterhinNichts(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);

        $this->versucheLoeschung($client, 'voellig-falsches-passwort');

        self::assertNotNull(
            $client->getContainer()->get(UserRepository::class)->findOneBy(['email' => self::KONTO]),
            'Ein Fehlversuch darf das Konto nicht löschen.',
        );
    }

    /**
     * ⚠ Am Konto gezählt, nicht an der IP: Der Angriff läuft aus einer gekaperten
     * Sitzung heraus, und dort wechselt die IP mühelos, das Konto nicht. Der
     * Prüflauf belegt das über die Kennung, mit der der Zähler angelegt wird.
     */
    public function testKontingentHaengtAmKontoNichtAnDerIp(): void
    {
        $client = static::createClient();
        $factory = $client->getContainer()->get('limiter.account_delete');
        self::assertInstanceOf(RateLimiterFactoryInterface::class, $factory);

        $eins = $factory->create(self::KONTO);
        $zwei = $factory->create('jemand-anders@endlech.lu');

        $eins->consume(1);

        self::assertNotSame(
            $eins->consume(0)->getRemainingTokens(),
            $zwei->consume(0)->getRemainingTokens(),
            'Zwei Konten dürfen sich kein Kontingent teilen.',
        );
    }

    /**
     * ⚠ **Die Kernzusage von BF-136, und sie war ungetestet.** Die bisherigen Läufe
     * belegten, dass ein Fehlversuch verbraucht und dass das Konto einen falschen
     * Versuch übersteht — nicht aber, dass die Sperre nach Erschöpfung **auch mit dem
     * richtigen Passwort** hält. Genau das ist die Eigenschaft, wegen der der Limiter
     * existiert: Wer das Passwort erraten hat, darf es nicht mehr anwenden können.
     *
     * Lücke vom `code-reviewer` im QA-Durchlauf vom 2026-09-11 gemeldet, am laufenden
     * Server gegengeprüft (Konto blieb bestehen) und hier festgeschrieben.
     */
    public function testNachErschoepfungBlocktAuchDasRichtigePasswort(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);

        $factory = $client->getContainer()->get('limiter.account_delete');
        self::assertInstanceOf(RateLimiterFactoryInterface::class, $factory);

        // ⚠ Im Test-Env steht das Limit auf 10000 (Pflicht-Override), deshalb wird das
        // Kontingent in EINEM Zug erschöpft statt über wiederholte Submits — eine
        // Schleife mit 10000 Durchläufen wäre kein Prüflauf, sondern eine Wartezeit.
        $limiter = $factory->create(self::KONTO);
        $limiter->reset();
        $limiter->consume(10_000);

        self::assertSame(0, $limiter->consume(0)->getRemainingTokens(), 'Vorbedingung: Kontingent ist leer.');

        $this->versucheLoeschung($client, 'user123');

        $nochDa = null !== $client->getContainer()->get(UserRepository::class)->findOneBy(['email' => self::KONTO]);

        // ⚠ Aufräumen VOR der Zusicherung: Der Zähler liegt im Cache-Pool und
        // übersteht den DAMA-Rollback. Bleibt er leer, färbt er `AccountDeletionTest`
        // rot, das dasselbe Konto benutzt — gemessen als zwei Failures in
        // `AccountDeletionTest.php:79`. Ein `tearDown()` taugt dafür nicht: Dort ist
        // der Kernel schon heruntergefahren.
        $limiter->reset();

        self::assertTrue(
            $nochDa,
            'BF-136: Ist das Kontingent erschöpft, darf auch das richtige Passwort nicht mehr löschen.',
        );
    }

    private function restkontingent(KernelBrowser $client): int
    {
        $factory = $client->getContainer()->get('limiter.account_delete');
        self::assertInstanceOf(RateLimiterFactoryInterface::class, $factory);

        // ⚠ consume(0) speichert nichts und ist deshalb als Sonde brauchbar — als
        // PRÜFUNG wäre es falsch (`0 >= 0` gilt auch bei leerem Kontingent), siehe
        // die ActionLimiter-Konvention in CLAUDE.md.
        return $factory->create(self::KONTO)->consume(0)->getRemainingTokens();
    }

    private function versucheLoeschung(KernelBrowser $client, string $passwort): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/profile');

        $client->submit($this->formByAction($crawler, self::LOCALE.'/profile/loeschen'), [
            'password' => $passwort,
        ]);
    }
}
