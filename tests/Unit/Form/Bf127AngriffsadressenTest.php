<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Tests\Functional\Controller\Bf119EmailValidierungTest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

/**
 * BF-127 — die Angriffsadressen des BF-119-Prüflaufs müssen den Befund auch
 * auslösen können.
 *
 * Zwei der drei ursprünglichen Datensätze sicherten nichts ab: `a"b(c)d@…` und
 * `jemand@@…` weist bereits der **HTML5-Default** des `Email`-Constraints ab.
 * Der Prüflauf blieb damit auch bei entferntem `VALIDATION_MODE_STRICT` grün —
 * in der Rückbau-Gegenprobe wurde **1 von 3** Datensätzen rot. Fachlich war er
 * richtig, als Rückbauschutz wirkte er zu einem Drittel.
 *
 * ⚠ **Dieser Lauf prüft die Liste, nicht das Verhalten.** Er ist der Grund,
 * weshalb der Befund nicht wiederkehren kann: Ein Datensatz, den schon der
 * Default abweist, färbt ihn rot — bevor jemand ihn für einen Nachweis hält. Die
 * Eigenschaft wird gegen die Bibliothek selbst gemessen (Validator im
 * HTML5-Modus, `Mime\Address`), nicht gegen eine abgeschriebene Kopie des
 * Musters; eine Kopie liefe beim nächsten Symfony-Update auseinander.
 */
final class Bf127AngriffsadressenTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function angriffsadressen(): iterable
    {
        yield from Bf119EmailValidierungTest::rfcWidrigeAdressen();
    }

    #[DataProvider('angriffsadressen')]
    public function testDerDefaultLaesstSieDurch(string $adresse): void
    {
        $fehler = Validation::createValidator()->validate(
            $adresse,
            new Email(mode: Email::VALIDATION_MODE_HTML5),
        );

        self::assertCount(
            0,
            $fehler,
            sprintf(
                'BF-127: „%s" wird schon vom HTML5-Default abgewiesen und kann den Befund '
                .'nicht auslösen — als Rückbauschutz für STRICT ist der Datensatz wirkungslos.',
                $adresse,
            ),
        );
    }

    #[DataProvider('angriffsadressen')]
    public function testMimeAddressLehntSieAb(string $adresse): void
    {
        $this->expectException(RfcComplianceException::class);

        new Address($adresse);
    }

    /**
     * Die Gegenprobe zur Gegenprobe: Der strikte Modus muss die Adressen
     * tatsächlich abweisen. Ohne diesen Fall prüften die beiden Läufe oben nur die
     * Auswahl der Datensätze, nicht dass sie ihren Zweck erfüllen.
     */
    #[DataProvider('angriffsadressen')]
    public function testStrictWeistSieAb(string $adresse): void
    {
        $fehler = Validation::createValidator()->validate(
            $adresse,
            new Email(mode: Email::VALIDATION_MODE_STRICT),
        );

        self::assertGreaterThan(
            0,
            \count($fehler),
            sprintf('STRICT muss „%s" abweisen — sonst schützt die Reparatur an dieser Stelle nicht.', $adresse),
        );
    }
}
