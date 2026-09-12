<?php

namespace App\Tests\Unit\Command;

use App\Command\WorkerPulseCommand;
use App\Monolog\SecretMaskingProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * BE-01, Puls an den Wächter außerhalb dieses Servers.
 *
 * Geprüft wird vor allem das, was im Betrieb weh tut: dass ein unerreichbarer
 * Wächter den Zeitplan nicht rot färbt, und dass das Push-Token nirgends in einem
 * Protokoll landet.
 */
final class WorkerPulseCommandTest extends TestCase
{
    /** Eine echte Push-Adresse hat diese Form — Token im Pfad, nicht im Query-Teil. */
    private const ZIEL = 'https://kuma.example.org/api/push/GEHEIM123abc';

    private function sammelLogger(): AbstractLogger
    {
        return new class extends AbstractLogger {
            /** @var list<array{string, string, array<mixed>}> */
            public array $zeilen = [];

            public function log($level, $message, array $context = []): void
            {
                $this->zeilen[] = [(string) $level, (string) $message, $context];
            }

            public function alles(): string
            {
                return json_encode($this->zeilen, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) ?: '';
            }
        };
    }

    public function testOhneZielWirdNichtsAufgerufen(): void
    {
        $aufrufe = 0;
        $client = new MockHttpClient(function () use (&$aufrufe): MockResponse {
            ++$aufrufe;

            return new MockResponse('OK');
        });

        $tester = new CommandTester(new WorkerPulseCommand($client, new NullLogger(), ''));
        $code = $tester->execute([]);

        self::assertSame(0, $code);
        self::assertSame(0, $aufrufe, 'Ohne APP_UPTIME_PUSH_URL darf kein Aufruf hinausgehen.');
        self::assertStringContainsString('Kein Puls-Ziel', $tester->getDisplay());
    }

    public function testMitZielGehtEinPulsHinaus(): void
    {
        $gesehen = null;
        $client = new MockHttpClient(function (string $method, string $url) use (&$gesehen): MockResponse {
            $gesehen = $method.' '.$url;

            return new MockResponse('OK');
        });

        $tester = new CommandTester(new WorkerPulseCommand($client, new NullLogger(), self::ZIEL));

        self::assertSame(0, $tester->execute([]));
        self::assertNotNull($gesehen);
        self::assertStringStartsWith('GET https://kuma.example.org/api/push/GEHEIM123abc', $gesehen);
        self::assertStringContainsString('status=up', $gesehen, 'Kuma erwartet status=up.');
        self::assertStringContainsString('msg=messenger-consumer', $gesehen);
        self::assertStringContainsString('Puls angekommen', $tester->getDisplay());
    }

    /**
     * ⚠ Der Fall, der den `failed`-Transport im Fünf-Minuten-Takt füllen würde.
     *
     * Ein unerreichbarer Wächter ist kein Fehler dieser Anwendung — er schlägt von
     * sich aus Alarm, weil der Puls ausbleibt. Würde der Befehl hier FAILURE liefern,
     * würfe `RunCommandMessage` eine Ausnahme, und im Ablagefach stapelten sich 288
     * Nachrichten am Tag.
     */
    public function testEinUnerreichbarerWaechterFaerbtDenLaufNichtRot(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            throw new TransportException(sprintf('Could not resolve host for "%s?status=up".', self::ZIEL));
        });

        $tester = new CommandTester(new WorkerPulseCommand($client, new NullLogger(), self::ZIEL));

        self::assertSame(0, $tester->execute([]), 'Der Lauf darf nicht scheitern.');
        self::assertStringContainsString('nicht zustellbar', $tester->getDisplay());
    }

    public function testEineAblehnungDesWaechtersFaerbtDenLaufEbenfallsNichtRot(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $logger = $this->sammelLogger();

        $tester = new CommandTester(new WorkerPulseCommand($client, $logger, self::ZIEL));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('HTTP 404', $tester->getDisplay());
        self::assertStringContainsString('"status":404', $logger->alles());
    }

    /**
     * ⚠ **Der eigentliche Grund für diesen Prüflauf.** Wer die Push-Adresse hat, kann
     * dem Wächter dauerhaft „alles in Ordnung" melden — er schaltet damit keinen
     * Einblick frei, sondern einen **Alarm ab**. Symfonys Transport-Ausnahmen führen
     * die vollständige URL in ihrem Text; würde der Befehl `getMessage()` durchreichen,
     * stünde das Token im Protokoll des Hosters.
     */
    public function testDasTokenLandetWederInDerAusgabeNochImProtokoll(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            throw new TransportException(sprintf('Could not resolve host for "%s?status=up".', self::ZIEL));
        });
        $logger = $this->sammelLogger();

        $tester = new CommandTester(new WorkerPulseCommand($client, $logger, self::ZIEL));
        $tester->execute([]);

        foreach (['im Protokoll' => $logger->alles(), 'in der Ausgabe' => $tester->getDisplay()] as $wo => $text) {
            self::assertStringNotContainsString('GEHEIM123abc', $text, "Das Token steht $wo.");
            self::assertStringNotContainsString('/api/push/', $text, "Der Push-Pfad steht $wo.");
        }

        // Der Rechnername darf drin stehen — sonst wüsste niemand, welcher Wächter
        // gemeint war, und das Protokoll wäre zur Fehlersuche unbrauchbar.
        self::assertStringContainsString('kuma.example.org', $logger->alles());
        self::assertStringContainsString('TransportException', $logger->alles(), 'Die Klasse gehört ins Protokoll, der Text nicht.');
    }

    public function testDryRunRuftNichtsAufUndZeigtDasTokenNicht(): void
    {
        $aufrufe = 0;
        $client = new MockHttpClient(function () use (&$aufrufe): MockResponse {
            ++$aufrufe;

            return new MockResponse('OK');
        });

        $tester = new CommandTester(new WorkerPulseCommand($client, new NullLogger(), self::ZIEL));

        self::assertSame(0, $tester->execute(['--dry-run' => true]));
        self::assertSame(0, $aufrufe);
        self::assertStringContainsString('kuma.example.org', $tester->getDisplay());
        self::assertStringNotContainsString('GEHEIM123abc', $tester->getDisplay());
    }

    /**
     * Der zweite Weg, genau wie bei BF-45: Symfonys eigener `http_client`-Kanal
     * protokolliert jede Anfrage samt vollständiger URL, und `monolog.yaml` schließt
     * ihn in `prod` **nicht** aus. Davon hält kein Anwendungscode etwas ab — dafür
     * gibt es den Processor.
     *
     * ⚠ Die Gegenprobe im selben Lauf ist die eigentliche Aussage: Das Token steht im
     * **Pfad**, nicht als Query-Parameter, und die Parameterliste des Processors
     * griff dort nicht.
     */
    public function testDerProcessorMaskiertDasPushTokenImPfad(): void
    {
        $processor = new SecretMaskingProcessor();

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'http_client',
            Level::Info,
            'Request: "GET '.self::ZIEL.'?status=up&msg=messenger-consumer"',
            ['url' => self::ZIEL],
        );

        $maskiert = $processor($record);

        self::assertStringNotContainsString('GEHEIM123abc', $maskiert->message);
        self::assertStringContainsString('/api/push/<maskiert>', $maskiert->message);
        self::assertStringContainsString('kuma.example.org', $maskiert->message, 'Der Rechnername bleibt lesbar.');
        self::assertStringContainsString('status=up', $maskiert->message, 'Der harmlose Rest bleibt lesbar.');

        // Ohne Query-Teil gibt es kein einziges `=` — die Abkürzung im Processor
        // hätte diese Zeile unangetastet durchgelaufen lassen.
        self::assertStringNotContainsString('GEHEIM123abc', $maskiert->context['url']);
        self::assertStringContainsString('/api/push/<maskiert>', $maskiert->context['url']);
    }
}
