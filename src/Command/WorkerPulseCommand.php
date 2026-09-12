<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Meldet einem Wächter außerhalb dieses Servers, dass der Messenger-Consumer läuft
 * (BE-01, seit 2026-09-12).
 *
 * **Die Lücke, die dieser Befehl schließt.** Der Ausfall des Worker-Containers ist
 * der lautlose: Nachrichten stapeln sich in `messenger_messages`, während die
 * Anwendung weiter „erfolgreich" meldet — keine Bestätigungsmail geht mehr hinaus,
 * kein Monats-Snapshot entsteht, kein Brevo-Abgleich läuft. Bemerkt wurde das
 * bisher, wenn sich jemand über eine fehlende Mail beschwerte. Der vorhandene
 * Wächter {@see MessengerWatchCommand} hilft dort nicht: Er läuft **im selben
 * Consumer**, den er beobachtet, und schweigt mit ihm.
 *
 * ⚠ **Die Umkehrung ist der ganze Trick.** Ein Wächter, der etwas *abfragt*, kann
 * einen stehenden Prozess nicht von einem gesunden unterscheiden — es antwortet
 * schlicht niemand, und das sieht von außen wie ein Netzproblem aus. Hier ruft
 * stattdessen der Beobachtete an. Bleibt der Anruf aus, ist das die Aussage. Kuma
 * nennt das einen Push-Monitor; die Adresse steht in `APP_UPTIME_PUSH_URL`.
 *
 * ⚠ **Das ist eine Lebendigkeits-, keine Fortschrittsprüfung** — dieselbe
 * Unterscheidung wie beim Healthcheck des `worker`-Stages: „der Consumer läuft",
 * nicht „er arbeitet den Rückstau ab". Letzteres beantwortet weiterhin
 * `app:messenger:watch`. Beide zusammen deckten den Fall ab, keiner allein.
 *
 * ⚠ **Ein Netzproblem zwischen den beiden Servern sieht aus wie ein toter Worker.**
 * Kuma meldet dann „ausgefallen", obwohl der Consumer arbeitet. Genau dafür steht
 * die Protokollzeile unten: Erscheint sie, war der Worker am Leben und nur der Weg
 * versperrt. Sie ist der einzige Unterschied zwischen den beiden Fällen, und deshalb
 * wird sie geschrieben, obwohl der Alarm ohne sie zustande käme.
 *
 * ⚠ **Dieser Befehl gibt NIE `FAILURE` zurück.** Der Zeitplan ruft ihn über
 * `RunCommandMessage`, und ein Fehlschlag würfe dort eine Ausnahme: Der
 * `failed`-Transport füllte sich im Fünf-Minuten-Takt mit Rauschen — dieselbe
 * Überlegung, aus der ein belegtes Schloss in {@see MarketingSyncCommand} `SUCCESS`
 * liefert. Ein unerreichbarer Wächter ist ohnehin kein Fehler dieser Anwendung; er
 * schlägt von sich aus Alarm, weil der Puls ausbleibt.
 *
 * ⚠ **Die Adresse wird nirgends protokolliert, auch nicht in einer Fehlermeldung.**
 * Sie trägt das Token im Pfad, und wer sie hat, kann dem Wächter dauerhaft „alles in
 * Ordnung" melden — also den Alarm abschalten. Symfonys Transport-Ausnahmen führen
 * die vollständige URL in ihrem Text (`Could not resolve host … for https://…`);
 * deshalb wird ausschließlich die **Klasse** der Ausnahme geloggt, nicht ihr Text.
 * Den zweiten Weg — Symfonys eigener `http_client`-Kanal — deckt
 * {@see \App\Monolog\SecretMaskingProcessor} ab. Genau dasselbe Muster wie bei
 * BF-45.
 *
 * ⚠ **Kein `LockableTrait`**, anders als bei den beiden übrigen Zeitplan-Befehlen.
 * Dort verhindert es eine doppelte *Handlung* mit Nebenwirkungen; hier gibt es keine
 * — zwei Pulse sind dasselbe Signal zweimal, und Kuma zählt Herzschläge, keine
 * Aufträge. Eine Sperre brächte nur einen weiteren Weg, auf dem der Puls ausfällt.
 */
#[AsCommand(
    name: 'app:worker:pulse',
    description: 'Meldet dem externen Wächter, dass der Messenger-Consumer läuft (BE-01)',
)]
final class WorkerPulseCommand extends Command
{
    /**
     * Kurz gehalten, und zwar absichtlich: Der Befehl läuft im Consumer, der in
     * derselben Zeit Bestätigungsmails zustellen soll. Ohne eigene Vorgabe griffe
     * `default_socket_timeout` — im Bestand mit 60 s gemessen (BF-47), also eine
     * Minute Stillstand alle fünf Minuten bei hängendem Wächter.
     */
    private const TIMEOUT = 5;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.uptime_push_url%')]
        private readonly string $pushUrl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Sagt nur, ob ein Ziel eingerichtet ist — ruft es nicht auf',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('' === $this->pushUrl) {
            $output->writeln('Kein Puls-Ziel eingerichtet (APP_UPTIME_PUSH_URL ist leer) — nichts zu tun.');

            return Command::SUCCESS;
        }

        if ($input->getOption('dry-run')) {
            $output->writeln(sprintf('Puls-Ziel steht bei %s — es wird nichts aufgerufen.', $this->zielRechner()));

            return Command::SUCCESS;
        }

        try {
            $code = $this->client->request('GET', $this->pushUrl, [
                'timeout' => self::TIMEOUT,
                'max_duration' => self::TIMEOUT * 2,
                // `status=up` ist Kumas Vorgabe; `msg` erscheint in seinem Verlauf
                // und sagt dem, der ihn liest, woher der Herzschlag kommt.
                'query' => ['status' => 'up', 'msg' => 'messenger-consumer'],
            ])->getStatusCode();
        } catch (\Throwable $fehler) {
            // Nur die Klasse, nie der Text: siehe Klassenkommentar.
            $this->logger->warning('Puls an den externen Wächter nicht zustellbar (BE-01).', [
                'ziel' => $this->zielRechner(),
                'ausnahme' => $fehler::class,
            ]);
            $output->writeln(sprintf('Puls nicht zustellbar (%s).', $fehler::class));

            return Command::SUCCESS;
        }

        if ($code < 200 || $code >= 300) {
            $this->logger->warning('Der externe Wächter hat den Puls abgelehnt (BE-01).', [
                'ziel' => $this->zielRechner(),
                'status' => $code,
            ]);
            $output->writeln(sprintf('Wächter antwortete mit HTTP %d.', $code));

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Puls angekommen (HTTP %d).', $code));

        return Command::SUCCESS;
    }

    /**
     * Nur der Rechnername des Wächters — genug, um zu erkennen, wer gemeint war,
     * und zu wenig, um den Alarm abzuschalten.
     */
    private function zielRechner(): string
    {
        return parse_url($this->pushUrl, \PHP_URL_HOST) ?: 'unbekannt';
    }
}
