<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;

/**
 * BF-135 — bei einem Versandfehler verrät die Registrierung doch, ob die Adresse
 * schon vergeben ist.
 *
 * Die Anti-Enumeration aus BF-09 gleicht Text, Statuscode und Laufzeit an, deckt
 * aber nur den Erfolgsweg ab: Der **Neuanlage**-Zweig fängt die
 * `TransportExceptionInterface` und setzt `warning`/`flash.register_email_failed`,
 * der **Bestands**-Zweig verschluckt dieselbe Ausnahme in
 * `sendeKontoExistiertHinweis()` und fällt in `success`. Bei gestörtem Transport
 * ist die Meldung damit die Antwort auf die Frage „gibt es dieses Konto?".
 *
 * ⚠ **Der Kommentar im Bestandszweig lautete „Die Antwort bleibt in jedem Fall
 * dieselbe".** Sie blieb nur *innerhalb* dieses Zweigs dieselbe — der Vergleich
 * mit dem anderen Zweig fand nicht statt. Genau das prüft dieser Lauf.
 *
 * ⚠ **Heute nicht von aussen auslösbar**, weil `SendEmailMessage` auf `async`
 * läuft und `MailerInterface::send()` dort keine `TransportExceptionInterface`
 * mehr wirft (BF-124/BF-131). Der Befund wird scharf, sobald jemand auf `sync://`
 * zurückstellt — und dann ist dieser Lauf der Einzige, der davor steht. Er setzt
 * den Mailer deshalb selbst auf Störung, statt auf den Transport zu warten.
 */
final class Bf135RegisterVersandfehlerTest extends AbstractWebTestCase
{
    private const BESTEHENDE_ADRESSE = 'user@endlech.lu';

    private ?string $dsnVorher = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dsnVorher = $_ENV['MAILER_DSN'] ?? null;
    }

    public function testBeideZweigeMeldenBeiVersandfehlerDasselbe(): void
    {
        $bestand = $this->meldungBeiVersandfehler(self::BESTEHENDE_ADRESSE);
        $neu = $this->meldungBeiVersandfehler('bf135_'.uniqid().'@endlech.lu');

        self::assertSame(
            $neu,
            $bestand,
            'BF-135: Die Art der Meldung darf nicht davon abhängen, ob die Adresse vergeben ist.',
        );
    }

    /**
     * Die fachliche Zusage aus AK-12 bleibt: Wer nichts bekommt, soll es erfahren.
     * Die Reparatur gleicht die Zweige an, ohne die Warnung abzuschaffen — sie
     * abzuschaffen wäre der bequemere Weg und nähme dem Nutzer die einzige
     * Rückmeldung, die er in diesem Fall hat.
     */
    public function testDieWarnungBleibtErhalten(): void
    {
        self::assertSame(
            ['warning'],
            $this->meldungBeiVersandfehler('bf135_'.uniqid().'@endlech.lu'),
            'AK-12: Ein gescheiterter Versand gehört dem Nutzer gesagt.',
        );
    }

    /**
     * Gegenprobe: Ohne Störung bleibt es beim Erfolg — in beiden Zweigen.
     */
    public function testOhneStoerungMeldenBeideErfolg(): void
    {
        self::assertSame(
            ['success'],
            $this->registriere(self::BESTEHENDE_ADRESSE, stoerung: false),
            'Ohne Versandfehler darf die Meldung sich nicht ändern.',
        );
    }

    /**
     * @return list<string> die Arten der gesetzten Flash-Meldungen
     */
    private function meldungBeiVersandfehler(string $adresse): array
    {
        return $this->registriere($adresse, stoerung: true);
    }

    /**
     * @return list<string>
     */
    private function registriere(string $adresse, bool $stoerung): array
    {
        // ⚠ **Die Störung kommt über den DSN, nicht über den Dienstbehälter.** Der
        // naheliegende Weg — `$client->getContainer()->set(MailerInterface::class,
        // …)`, wie ihn `testMailerFailureShowsWarningAndStillRedirects` geht —
        // **wirkt nicht**: Der Mailer ist ein Methodenargument des Controllers und
        // wird aus dessen Dienst-Locator geholt, den das Überschreiben im
        // Test-Behälter nicht erreicht. Gemessen: Der Ersatz wirft, und es gingen
        // trotzdem zwei Mails hinaus. Ein unerreichbarer SMTP-Port stört dagegen
        // echt — `%env()%` wird zur Laufzeit aufgelöst, der Wert greift also noch
        // beim Booten.
        self::ensureKernelShutdown();

        if ($stoerung) {
            $_ENV['MAILER_DSN'] = $_SERVER['MAILER_DSN'] = 'smtp://127.0.0.1:1';
        }

        $client = static::createClient();

        $crawler = $client->request('GET', self::LOCALE.'/register');
        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'BF135 Test',
            'registration[email]' => $adresse,
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        self::assertResponseRedirects();

        $session = $client->getRequest()->getSession();

        // peekAll() liest ohne zu verbrauchen — die Meldung soll die Seite danach
        // trotzdem noch erreichen.
        return array_keys($session->getFlashBag()->peekAll());
    }

    /**
     * ⚠ **Zurückgestellt wird auf den vorigen Wert, nicht gelöscht.** Beim Bauen
     * gemessen: Ein `unset()` liess `%env(MAILER_DSN)%` undefiniert — `.env.test`
     * füllt `$_ENV` einmalig beim Bootstrap, nicht bei jedem Kernel — und der
     * nächste Aufruf von `/register` antwortete mit **500**. Ein
     * stehengelassener Wert wäre genauso schädlich, nur in die andere Richtung.
     */
    protected function tearDown(): void
    {
        if (null === $this->dsnVorher) {
            unset($_ENV['MAILER_DSN'], $_SERVER['MAILER_DSN']);
        } else {
            $_ENV['MAILER_DSN'] = $_SERVER['MAILER_DSN'] = $this->dsnVorher;
        }

        parent::tearDown();
    }
}
