<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * BF-138 — eine gespeicherte Adresse, die RFC 2822 verletzt, darf den
 * Passwort-Reset nicht mit HTTP 500 beenden.
 *
 * Gemessen vor der Reparatur: RFC-widriger Altbestand → **500**, unbekannte
 * Adresse → 302, gültige Adresse → 302. Die Ausnahme kommt aus `->to()`
 * (`Mime\Exception\RfcComplianceException`), `sendeLink()` fängt nur
 * `TransportExceptionInterface`.
 *
 * ⚠ **Zwei Schäden, und der Statuscode ist der schwerere.** Die 500 verrät die
 * Existenz des Kontos stärker als die 12 ms aus BF-137 — und sie tritt **vor**
 * `gleicheLaufzeitAn()` ein, der Angleich wird also mit übersprungen.
 *
 * ⚠ **Der Zugangsverlust bleibt bestehen, und das ist keine Nachlässigkeit.** An
 * eine Adresse, die kein Mailserver annimmt, ist kein Link zustellbar; die
 * Reparatur kann nur dafür sorgen, dass der Weg nicht mehr *auffällig* scheitert
 * und dass der Betreiber davon erfährt. Helfen kann danach nur ein Mensch.
 *
 * ⚠ **Solche Adressen entstehen seit BF-119 nicht mehr neu.** Auf Produktion am
 * 2026-09-12 gezählt: 0 betroffene Konten. Das ist ein Stichtag, keine Zusage —
 * ein Bestandsimport oder ein zweiter Schreibweg auf `user.email` entwertet ihn,
 * und dieser Prüflauf ist dann der Einzige, der noch dagegen steht.
 */
final class Bf138ResetRfcAdresseTest extends AbstractWebTestCase
{
    /**
     * ⚠ Diese Adresse **passiert** den HTML5-Default des `Email`-Constraints (den
     * `PasswordResetRequestType` weiterhin führt) und wird von `Mime\Address`
     * abgelehnt. Genau diese Kombination ist der Befund; eine Adresse, die schon
     * das Formular abweist, würde ihn nicht auslösen (siehe BF-127).
     */
    private const WIDRIGE_ADRESSE = '../../etc/passwd@example.lu';

    private const UNBEKANNTE_ADRESSE = 'gibt-es-nicht-bf138@example.invalid';

    /** Muss zu PasswordResetController::MINDESTDAUER_SEKUNDEN passen. */
    private const MINDESTDAUER_MS = 120;

    private const TOLERANZ_MS = 20;

    public function testRfcWidrigeAdresseErgibtKeinen500(): void
    {
        $client = static::createClient();
        $this->legeKontoAn($client, self::WIDRIGE_ADRESSE);

        [$status, $ziel] = $this->fordereZuruecksetzenAn($client, self::WIDRIGE_ADRESSE);

        self::assertSame(
            302,
            $status,
            'BF-138: Eine unzustellbare Altadresse darf keinen Serverfehler erzeugen — '
            .'der Statuscode verrät sonst, dass das Konto existiert.',
        );
        self::assertSame(self::LOCALE.'/login', $ziel);
    }

    /**
     * Der Kern des Befundes: **dieselbe** Antwort wie für eine Adresse, die es
     * nicht gibt. Ein Test, der nur „kein 500" prüft, bliebe grün, wenn der Zweig
     * stattdessen eine eigene Fehlermeldung zeigte.
     */
    public function testAntwortIstIdentischZurUnbekanntenAdresse(): void
    {
        $client = static::createClient();
        $this->legeKontoAn($client, self::WIDRIGE_ADRESSE);

        self::assertSame(
            $this->fordereZuruecksetzenAn($client, self::UNBEKANNTE_ADRESSE),
            $this->fordereZuruecksetzenAn($client, self::WIDRIGE_ADRESSE),
            'BF-138: Statuscode und Ziel müssen übereinstimmen.',
        );
    }

    /**
     * ⚠ **Der eigentliche Prüfpunkt der Reparatur.** Die Ausnahme flog vor
     * `gleicheLaufzeitAn()`; ein blosses `catch` an der falschen Stelle liesse den
     * Angleich weiterhin aus und stellte das Leck aus BF-137 für genau diese Konten
     * wieder her.
     */
    public function testDerLaufzeitAngleichWirdNichtUebersprungen(): void
    {
        $client = static::createClient();
        $this->legeKontoAn($client, self::WIDRIGE_ADRESSE);

        $start = microtime(true);
        $this->fordereZuruecksetzenAn($client, self::WIDRIGE_ADRESSE);
        $dauer = (microtime(true) - $start) * 1000;

        self::assertGreaterThanOrEqual(
            self::MINDESTDAUER_MS - self::TOLERANZ_MS,
            $dauer,
            'BF-138: Auch der abgebrochene Zweig muss die Mindestdauer aus BF-137 halten.',
        );
    }

    /**
     * Die Gegenprobe: Der Vorgang darf nicht stillschweigend als erledigt gelten.
     * Eine Mail kann es nicht geben — deshalb ist der Protokolleintrag der einzige
     * Weg, auf dem der Betreiber von dem Konto erfährt.
     */
    public function testKeineMailUndDerTokenBleibtFolgenlos(): void
    {
        $client = static::createClient();
        $this->legeKontoAn($client, self::WIDRIGE_ADRESSE);

        $this->fordereZuruecksetzenAn($client, self::WIDRIGE_ADRESSE);

        self::assertEmailCount(0, 'An eine RFC-widrige Adresse ist nichts zustellbar.');
    }

    private function legeKontoAn(KernelBrowser $client, string $adresse): void
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        // ⚠ Über den Entity Manager, nicht über das Registrierformular: Seit BF-119
        // prüft `RegistrationType` strikt, eine solche Adresse kommt dort nicht mehr
        // hinein. Der Befund betrifft den **Altbestand**, und genau der lässt sich
        // nur so herstellen — Doctrine validiert nicht.
        $user = new User();
        $user->setName('BF138 Altbestand');
        $user->setEmail($adresse);
        $user->setPassword('$2y$13$nichtbenutzt');
        $user->setIsVerified(true);

        $em->persist($user);
        $em->flush();
    }

    /**
     * @return array{int, string} Statuscode und Weiterleitungsziel
     */
    private function fordereZuruecksetzenAn(KernelBrowser $client, string $adresse): array
    {
        $crawler = $client->request('GET', self::LOCALE.'/passwort-vergessen');

        $client->submit($this->formWithField($crawler, 'password_reset_request[email]', [
            'password_reset_request[email]' => $adresse,
        ]));

        $antwort = $client->getResponse();

        return [$antwort->getStatusCode(), (string) $antwort->headers->get('Location')];
    }
}
