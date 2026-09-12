<?php

declare(strict_types=1);

namespace App\Tests\Unit\Messenger;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Yaml\Yaml;

/**
 * BF-124 / BF-131 — der Mailversand läuft asynchron, und daran hängen drei
 * Zusagen, die niemand nebenbei umdrehen sollte.
 *
 * Seit dem 2026-09-02 geht `SendEmailMessage` in den `async`-Transport. Das hat
 * zwei Folgen, die weit auseinanderliegen:
 *
 * 1. **`MailerInterface::send()` wirft keine `TransportExceptionInterface` mehr.**
 *    Jeder `catch`-Zweig darauf ist auf Produktion unerreichbar — und damit auch
 *    jede Warnung an den Nutzer (AK-04 in B14, AK-12 in B01, AK-20 in Feature 08).
 *    Beim ersten Prüflauf von B14 war der Versand synchron, dort galt AK-04 zu
 *    Recht als erfüllt; die Regression ist still eingetreten.
 * 2. **Ein Rückstau ist der einzige Hinweis, dass nichts hinausgeht** — gemessen
 *    über `messenger:stats` bzw. gemeldet von `app:messenger:watch`.
 *
 * ⚠ **Und der Rückweg wäre teuer:** `CLAUDE.md` hält fest, dass `sync://` der
 * Warteschlange Retry und `failed`-Transport nimmt — und damit die einzige
 * Sichtbarkeit für gescheiterten Versand. Zwölf `catch`-Blöcke in acht Dateien
 * fingen den Fehler dann wieder ab, **ohne ihn zu protokollieren**.
 *
 * ⚠ **Geparst, nicht durchsucht.** Die Konfiguration wird über `Yaml::parseFile()`
 * gelesen, nicht als Zeichenkette abgesucht: Ein `assertStringContainsString`
 * träfe auch einen auskommentierten Eintrag — genau der Fehler, der BF-125
 * ausmachte.
 */
final class MailVersandRoutingTest extends TestCase
{
    private const DATEI = __DIR__.'/../../../config/packages/messenger.yaml';

    /**
     * @return array<string, mixed>
     */
    private static function konfiguration(): array
    {
        $geparst = Yaml::parseFile(self::DATEI);
        self::assertIsArray($geparst);

        return $geparst;
    }

    public function testMailversandIstAsynchronGeroutet(): void
    {
        $routing = self::konfiguration()['framework']['messenger']['routing'] ?? [];

        self::assertSame(
            'async',
            $routing[SendEmailMessage::class] ?? null,
            'BF-124: Wer den Mailversand umroutet, ändert damit, ob ein Nutzer bei '
            .'gescheitertem Versand gewarnt werden kann — und nimmt der Warteschlange '
            .'Retry und failed-Transport. Diese Entscheidung gehört nicht in einen '
            .'Nebenbei-Commit.',
        );
    }

    /**
     * Die Gegenseite derselben Entscheidung: Im Test-Env ist der Versand `sync`,
     * und **nur deshalb** greifen `assertEmailCount()` sowie die
     * Versandfehler-Zweige in den Prüfläufen (BF-131, BF-135). Wer hier auf `async`
     * stellt, färbt keinen Lauf rot — er macht sie bloss blind.
     */
    public function testImTestEnvIstDerVersandSynchron(): void
    {
        $routing = self::konfiguration()['when@test']['framework']['messenger']['routing'] ?? [];

        self::assertSame(
            'sync',
            $routing[SendEmailMessage::class] ?? null,
            'Ohne sync im Test-Env prüfen die Mail-Zusicherungen nichts mehr.',
        );
    }
}
