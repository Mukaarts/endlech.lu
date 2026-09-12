<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractWebTestCase;

/**
 * B26 · Cookie-Banner — die Zusagen, die sich an der gerenderten Antwort prüfen
 * lassen (QA vom 2026-09-12).
 *
 * ⚠ **Das Banner ist rein clientseitig.** Sichtbarkeit, Cookie-Schreibweise,
 * Fokusführung und das Fenster-Ereignis zwischen Fußzeile und Banner lassen sich
 * mit PHPUnit nicht prüfen; sie sind im Prüfbericht mit Browsermessungen belegt.
 * Hier steht, was der Server ausliefert — und das ist mehr, als es klingt: Ob das
 * Banner ohne JavaScript verborgen bleibt und ob es im Verwaltungsbereich
 * überhaupt erst entsteht, entscheidet die Antwort.
 */
final class CookieBannerTest extends AbstractWebTestCase
{
    /**
     * EC-03 · Ohne JavaScript erscheint das Banner **nie**: Es wird mit der Klasse
     * `hidden` ausgeliefert, und nur der Stimulus-Controller nimmt sie weg.
     */
    public function testEc03BannerWirdVerborgenAusgeliefert(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/');

        $banner = $crawler->filter('[data-cookie-consent-target="banner"]');
        self::assertCount(1, $banner, 'Das Banner gehört in die Antwort — verborgen, aber vorhanden.');

        self::assertStringContainsString(
            'hidden',
            (string) $banner->attr('class'),
            'EC-03: Ohne die Klasse `hidden` blitzte das Banner bei jedem Seitenaufruf auf, '
            .'bevor JavaScript es verstecken könnte — und ohne JavaScript blieb es stehen.',
        );
    }

    /**
     * AK-05 · Die Auszeichnung für Screenreader steht in der Antwort.
     */
    public function testAk05BannerTraegtDieAuszeichnungFuerScreenreader(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/');

        $banner = $crawler->filter('[data-cookie-consent-target="banner"]');

        self::assertSame('dialog', $banner->attr('role'));
        self::assertSame('false', $banner->attr('aria-modal'), 'AK-05: Das Banner sperrt die Seite nicht — `aria-modal="true"` wäre eine Lüge.');
        self::assertNotNull($banner->attr('aria-labelledby'));
        self::assertNotNull($banner->attr('aria-describedby'));
        self::assertSame('-1', $banner->attr('tabindex'), 'OF-02: Ohne tabindex kann `focus()` beim erneuten Öffnen nicht greifen.');

        self::assertCount(2, $banner->filter('button'), 'AK-05: Annehmen und Ablehnen, beide als echte Knöpfe.');
    }

    /**
     * AK-06 · Im Verwaltungsbereich entstehen weder Banner noch Fußzeilenlink.
     */
    public function testAk06KeinBannerImVerwaltungsbereich(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-cookie-consent-target="banner"]'), 'AK-06: Kein Banner in der Verwaltung.');
        self::assertStringNotContainsString(
            'cookie-consent#openSettings',
            (string) $client->getResponse()->getContent(),
            'AK-06: Auch der Fußzeilenlink gehört dort nicht hin.',
        );

        // Gegenprobe: auf einer öffentlichen Seite entsteht beides.
        $crawler = $client->request('GET', self::LOCALE.'/');
        self::assertCount(1, $crawler->filter('[data-cookie-consent-target="banner"]'));
        self::assertStringContainsString('cookie-consent#openSettings', (string) $client->getResponse()->getContent());
    }

    /**
     * AK-11 · Keine Fremdressourcen. Geprüft wird die **Antwort**: jede Adresse in
     * `src` und `href`, die auf eine andere Herkunft zeigt.
     *
     * ⚠ Erlaubt bleiben Verweise, die der Besucher selbst anklickt (GitHub in der
     * Fußzeile) — sie **laden** nichts nach. Der Unterschied ist der Punkt: AK-11
     * verbietet geladene Ressourcen, nicht Hyperlinks.
     */
    public function testAk11KeineFremdressourcenWerdenGeladen(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/');

        $geladen = array_merge(
            $crawler->filter('script[src]')->each(static fn ($k) => (string) $k->attr('src')),
            $crawler->filter('link[rel="stylesheet"]')->each(static fn ($k) => (string) $k->attr('href')),
            $crawler->filter('link[rel="preload"]')->each(static fn ($k) => (string) $k->attr('href')),
            $crawler->filter('img[src]')->each(static fn ($k) => (string) $k->attr('src')),
        );

        $fremd = array_values(array_filter(
            $geladen,
            static fn (string $adresse) => (bool) preg_match('#^(https?:)?//#', $adresse),
        ));

        self::assertSame(
            [],
            $fremd,
            'AK-11: Diese Seite lädt nichts von Dritten — auch keine Schriften, seit BF-99 '
            .'Inter selbst gehostet wird.',
        );
    }
}
