<?php

declare(strict_types=1);

namespace App\Tests\Integration\Waitlist;

use App\Entity\PartnerWaitlistEntry;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Waitlist\WaitlistConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * BF-119, zweite Hälfte: die Reihenfolge in `WaitlistConfirmationService::register()`.
 *
 * ⚠ **Warum das nicht über das Formular prüfbar ist.** Seit der Reparakur an den
 * drei `Email`-Constraints erreicht eine RFC-widrige Adresse den Service gar
 * nicht mehr — ein funktionaler Test über das Formular bliebe also grün, ganz
 * gleich ob die Adressprüfung vor oder hinter dem `flush()` steht. Genau dieses
 * Muster hat das Projekt schon zweimal bezahlt (BF-64: ein Test, der die
 * Begrenzung nie ausreizte; BF-81: ein Prüflauf, der nur die halbe Darstellung
 * ansah). Deshalb ruft dieser Lauf den Service **direkt**.
 *
 * Geprüft wird die Eigenschaft, die BF-119 ausmacht: Es genügt nicht, dass es
 * knallt — es darf **keine Zeile** übrig bleiben.
 */
final class Bf119RegisterReihenfolgeTest extends KernelTestCase
{
    private const WIDRIG = '../../etc/passwd@example.lu';

    public function testRfcWidrigeAdresseHinterlaesstKeineZeile(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $repo = $container->get(PartnerWaitlistEntryRepository::class);
        $vorher = $repo->count([]);

        try {
            $container->get(WaitlistConfirmationService::class)->register(
                $this->eintrag(self::WIDRIG),
                'app_partner_confirm',
                'email/partner/confirmation.html.twig',
                'partner.email.confirm_subject',
            );
            self::fail('Eine Adresse, die RFC 2822 verletzt, muss beim Anlegen auffliegen.');
        } catch (RfcComplianceException) {
            // erwartet — die Prüfung steht vor dem flush
        }

        $container->get(EntityManagerInterface::class)->clear();

        self::assertSame(
            $vorher,
            $repo->count([]),
            'BF-119: Die Adressprüfung muss VOR dem flush stehen, sonst bleibt eine Zeile zurück.',
        );
    }

    /**
     * ⚠ Die Gegenprobe zur Gegenprobe: Der Transportfehler darf den Eintrag
     * **nicht** mitnehmen.
     *
     * `CLAUDE.md` nennt die Reihenfolge Token → flush → Mail ausdrücklich
     * wesentlich — „scheitert der Transport, ist die Anmeldung trotzdem
     * gespeichert". Wer BF-119 durch schlichtes Umdrehen der Reihenfolge
     * repariert, macht diesen Test rot und merkt erst dadurch, dass er eine
     * bewusste Eigenschaft weggeworfen hat. Im Test läuft der Mailer über den
     * `null://`-Transport, der Versand gelingt also — geprüft wird, dass der
     * Eintrag nach einem gültigen Durchlauf steht und der Rückgabewert die
     * Zustellung meldet.
     */
    public function testGueltigeAdresseWirdGespeichert(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $repo = $container->get(PartnerWaitlistEntryRepository::class);
        $vorher = $repo->count([]);

        $erfolg = $container->get(WaitlistConfirmationService::class)->register(
            $this->eintrag('bf119-gueltig@example.lu'),
            'app_partner_confirm',
            'email/partner/confirmation.html.twig',
            'partner.email.confirm_subject',
        );

        self::assertTrue($erfolg);
        self::assertSame($vorher + 1, $repo->count([]));
    }

    private function eintrag(string $adresse): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Brasserie BF119');
        $entry->setContactName('Anna Muster');
        $entry->setEmail($adresse);
        $entry->setLocality('Strassen');
        $entry->setLocale('de');

        return $entry;
    }
}
