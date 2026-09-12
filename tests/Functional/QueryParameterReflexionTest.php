<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractWebTestCase;

/**
 * BF-110 — was mit einem angehängten Abfrageparameter in der Antwort passieren darf
 * und was nicht.
 *
 * ⚠ **Der Befund benannte die falsche Stelle.** Er las den `hreflang`-Block in
 * `base.html.twig` als Ursache: `/de/roadmap?stage=secret` liefere
 * `<link rel="alternate" href="/lb/roadmap?stage=secret">`. Am 2026-09-12
 * nachgemessen: **Die hreflang-Verweise sind frei von der Abfragezeichenfolge.** Der
 * Block baut seine Adressen aus `_route_params`, und darin stehen ausschliesslich
 * Routenvariablen — niemals die Query. Wer den Befund wörtlich nimmt, reparierte eine
 * Stelle, die nichts tut.
 *
 * ⚠ **Die drei Treffer im Dokument stammen vom Sprachumschalter, und dort ist die
 * Übernahme Absicht (BF-68).** Sieben Controller werten Abfrageparameter aus
 * (Restaurantliste, Ideen-Board, Organisationsseiten und vier Verwaltungsseiten); ein
 * Sprachwechsel, der die Filter wegwirft, wäre ein Rückschritt. Eine Positivliste
 * erlaubter Parameter oder Routen wäre die nächste Pflegefalle — sie veraltet beim
 * nächsten Filter, und zwar lautlos.
 *
 * **Was dieser Lauf deshalb festschreibt, ist die Eigenschaft, auf die es ankommt:**
 * Ein Fremdparameter darf in der **Query-Position** eines eigenen Verweises auftauchen,
 * aber niemals in **Pfad-Position** und niemals ungeschützt. Genau das trennt den
 * harmlosen Fall von BF-68, wo `?_locale=//fremd.example/de` ein
 * `href="///fremd.example/…"` erzeugte und der Browser den fremden Host ansteuerte.
 */
final class QueryParameterReflexionTest extends AbstractWebTestCase
{
    public function testHreflangVerweiseTragenKeineAbfragezeichenfolge(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/roadmap?stage=secret');

        self::assertResponseIsSuccessful();

        $alternativen = $crawler->filter('link[rel="alternate"]')->each(
            static fn ($knoten) => (string) $knoten->attr('href'),
        );

        self::assertNotEmpty($alternativen, 'Vorbedingung: Die Seite führt hreflang-Verweise.');

        foreach ($alternativen as $href) {
            self::assertStringNotContainsString(
                'stage=secret',
                $href,
                'BF-110: Die hreflang-Verweise stammen aus den Routenvariablen und dürfen '
                .'keine Eingabe des Aufrufers tragen.',
            );
        }
    }

    /**
     * ⚠ **Der eigentliche Prüfpunkt.** Ein Parameter, der wie eine Adresse aussieht,
     * darf das Ziel eines Verweises nicht verschieben. Bei BF-68 war genau das der
     * Fehler, und dort hing es an `_locale` — der einzige Parameter, der in die
     * **Pfad**-Position gerät.
     */
    public function testFremdparameterLandetNieInPfadPosition(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/roadmap?foo=//fremd.example');

        $ziele = $crawler->filter('a[href], link[href]')->each(
            static fn ($knoten) => (string) $knoten->attr('href'),
        );

        foreach ($ziele as $href) {
            [$pfad] = explode('?', $href, 2);

            self::assertStringNotContainsString(
                'fremd.example',
                $pfad,
                sprintf('BF-110/BF-68: „%s" zeigt auf einen fremden Host.', $href),
            );
        }

        // Gegenprobe zur Gegenprobe: Der Parameter ist wirklich angekommen — sonst
        // prüfte der Lauf oben eine Seite, die ihn schlicht verworfen hat.
        self::assertStringContainsString(
            'fremd.example',
            (string) $client->getResponse()->getContent(),
            'Vorbedingung: Der Sprachumschalter trägt die Query weiter (BF-68, Absicht).',
        );
    }

    public function testMarkupImParameterErscheintNurGeschuetzt(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/roadmap?x=<script>alert(1)</script>');

        $html = (string) $client->getResponse()->getContent();

        self::assertStringNotContainsString(
            '<script>alert(1)</script>',
            $html,
            'BF-110: Eine Eingabe des Aufrufers darf nie ungeschützt im Dokument stehen.',
        );
    }

    /**
     * Die andere Hälfte der Zusage: Was BF-68 gebaut hat, bleibt. Ein Sprachwechsel auf
     * einer gefilterten Liste darf die Filter nicht wegwerfen — sonst landet der
     * Besucher auf einer Seite, die er nicht gesucht hat, und die Reparatur dieses
     * Befundes hätte einen Rückschritt bezahlt.
     */
    public function testFilterUeberlebenDenSprachwechsel(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/restaurants?sort=name&wheelchair=1');

        self::assertResponseIsSuccessful();

        $sprachverweise = array_filter(
            $crawler->filter('a[href]')->each(static fn ($knoten) => (string) $knoten->attr('href')),
            static fn (string $href) => str_starts_with($href, '/fr/restaurants'),
        );

        self::assertNotEmpty($sprachverweise, 'Vorbedingung: Der Sprachumschalter führt einen Verweis auf /fr.');

        $treffer = array_filter(
            $sprachverweise,
            static fn (string $href) => str_contains($href, 'sort=name') && str_contains($href, 'wheelchair=1'),
        );

        self::assertNotEmpty(
            $treffer,
            'BF-68: Der Sprachwechsel muss die gesetzten Filter mitnehmen.',
        );
    }
}
