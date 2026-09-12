# B25 · PWA und mobile Navigation — Testbericht

Stand: 2026-09-12 · Geprüft gegen `spec.md` vom 2026-08-23 (Rekonstruktion)

## Fazit

**Production-ready: ja**

Der Kern des Features hält, und er hält nachgemessen: Der Service Worker cacht genau die
fünf Dateien der App-Shell, verwirft fremde Caches, liefert offline die Ersatzseite,
bedient gebaute Assets aus dem Cache mit Nachladen im Hintergrund und **lehnt sauber ab**,
wenn offline noch nichts gecacht ist — der Fall, in dem eine naive Fassung zu `undefined`
auflöst. Die mobile Leiste trägt vier Felder mit 121 × 60 px, kennzeichnet das aktive
Feld doppelt (Farbe **und** `aria-current`) und erscheint im Verwaltungsbereich nicht.
Kein Kriterium ist kritisch oder hoch durchgefallen.

Drei Kriterien sind durchgefallen, und zwei davon hängen zusammen: **Profilbilder landen
im Cache des Service Workers** (BF-140) — auf einem geteilten Gerät bleibt das Bild eines
abgemeldeten Nutzers liegen, was AK-19 und AK-20 ausdrücklich ausschließen. Das dritte
ist ein Befund gegen die **Spezifikation selbst**: AK-17 beschreibt, dass es auf dem
Telefon keinen Weg zum Abmelden und zur Sprachwahl gebe. Gemessen gibt es beide (BF-142)
— die Rekonstruktion ist an dieser Stelle überholt, und sie nennt den Punkt „den
zentralen Befund" des Features.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 20 von 20 |
| davon bestanden | 16 |
| davon durchgefallen | 3 |
| **nicht prüfbar** | 1 |
| Edge Cases belegt | 4 von 4 |
| Tests neu geschrieben | 8 (plus ein ausführbares Prüfskript für den Worker) |
| Befunde | 6 — einer *mittel*, fünf *niedrig* |
| Agentenfunde geprüft | 2, davon 1 bestätigt und 1 widerlegt |
| Tests grün | 1105 von 1105 |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `PwaTest::testAk01KopfTraegtDiePwaAngaben`; live gemessen: `rel="manifest"`, `theme-color #0891b2`, vier iOS-Meta-Tags, `viewport-fit=cover`, **neun** `apple-touch-icon` (acht mit `sizes`: 152, 144, 120, 114, 76, 72, 60, 57 — dazu der 180er ohne Angabe) |
| AK-02 | ✅ bestanden | `PwaTest::testAk02ManifestTraegtScopeUndIcons`; live: `start_url` und `scope` `/`, `display standalone`, `orientation portrait`, Icons `192 any`, `512 any`, `512 maskable`; alle drei Dateien HTTP 200 |
| AK-03 | ⚠️ nicht prüfbar | Die Registrierung schliesst im headless Chrome dieser Umgebung **nicht ab**: `isSecureContext=true`, `'serviceWorker' in navigator=true`, `caches` vorhanden — aber der Promise von `register('/sw.js', {scope:'/'})` bleibt pending, ohne `then` und ohne `catch`. Belegt ist damit nur die Voraussetzung (`/sw.js` → HTTP 200, `text/javascript`), nicht der Vollzug. Ein Nachweis bräuchte einen Browser mit Profil oder das CDP |
| AK-04 | ✅ bestanden | `node qa/B25/sw-verhalten.mjs` → `cache=endlech-v2 inhalt=["/offline.html","/images/logo.png","/icons/icon-192.png","/icons/icon-512.png","/manifest.webmanifest"] skipWaiting=true` |
| AK-05 | ✅ bestanden | dito: zwei fremde Caches (`endlech-v1`, `fremder-cache`) angelegt, nach `activate` → `verbleibende_caches=["endlech-v2"] claim=true` |
| AK-06 | ✅ bestanden | dito, Netz abgeschaltet: `offline_navigation_liefert=/offline.html` |
| AK-07 | ✅ bestanden | dito: `sofort_aus_cache=/build/app.abc.css-ALT netzaufruf_im_hintergrund=true` |
| AK-08 | ✅ bestanden | dito, leerer Cache und kein Netz: `cold_start_offline=abgelehnt (offline)` — kein `undefined` |
| AK-09 | ✅ bestanden | dito: `POST → eingegriffen=nein`, `/api/v1/restaurants → nein`, `/api/cuisines/search → nein`. ⚠ **Mit einer Lücke, die das Kriterium nicht abdeckt:** Der real erreichbare Weg ist `/{_locale}/api/cuisines` — siehe BF-141 |
| AK-10 | ✅ bestanden | dito: `https://fremd.example/bild.png → eingegriffen=nein` |
| AK-11 | ✅ bestanden | `PwaTest::testAk11Ak12…`; Browsermessung bei 500 px: `position=fixed display=block felder=4 groessen=[121x60 121x60 121x60 121x60] unter44px=0`, Klasse trägt `pb-[env(safe-area-inset-bottom)]` |
| AK-12 | ✅ bestanden | dito: `ariaCurrent=1 aktivKlasse=text-cyan-600` |
| AK-13 | ✅ bestanden | `PwaTest::testAk13…`; Messung `/de/admin` bei 500 px → `bottomnav=FEHLT`, Gegenprobe `/de/profile` → `bottomnav=da` |
| AK-14 | ✅ bestanden | Messung auf `/de/login` bei 500 px: `eingabefeld.fontSize=16px` |
| AK-15 | ✅ bestanden | `PwaTest::testAk15…`; gemessen `main.paddingBottom=64px` bei 500 px und `0px` bei 1280 px |
| AK-16 ⚠ | ✅ bestanden | Das beschriebene Verhalten ist eingetreten, und zwar heute: `CACHE_VERSION` musste im Rahmen von BF-99 **von Hand** von `endlech-v1` auf `endlech-v2` gesetzt werden, weil die Wort-Bildmarken im Presse-Kit ersetzt wurden. Live gemessen: `endlech-v2`. Der Handgriff ist also real, nicht theoretisch (FB-02, OF-02) |
| AK-17 ⚠ | ❌ durchgefallen | **Überholt** — siehe BF-142. Gemessen auf `/de/profile` bei 500 px: Abmelden **sichtbar**, 387 × 44 px (das zweite der beiden Formulare trägt kein `hidden md:`), Sprachwahl **sichtbar**, 55 × 28 px. „Vorschlagen", Partner und Organisationen stehen in der Fussleiste, „Über uns" in der Leiste **und** seit dem 2026-09-12 in der Fussleiste |
| AK-18 ⚠ | ✅ bestanden | Das beschriebene Verhalten ist bestätigt: eine zuvor besuchte und **gecachte** Seite liefert offline trotzdem die Ersatzseite — `besuchte_seite_offline=/offline.html`. Der Navigationszweig fragt den Cache nur für `OFFLINE_URL` (FB-03) |
| AK-19 | ❌ durchgefallen | `avatar_im_cache=["https://endlech.lu/uploads/avatars/nutzer-7.jpg"]` — siehe BF-140 |
| AK-20 | ❌ durchgefallen | dito. Nach dem Abmelden bleibt das Profilbild im Cache; geleert wird er erst bei einem Wechsel von `CACHE_VERSION` |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | `PwaTest::testEc01DateienLiegenSprachfreiAufWurzelebene` (drei Datensätze): Datei liegt unter `public/`, und der **Router** hat für `/de/sw.js`, `/de/manifest.webmanifest`, `/de/offline.html` keine Route (`ResourceNotFoundException`). Live gegengeprüft: `/sw.js` 200, `/de/sw.js` 404 |
| EC-02 | ✅ | `file public/icons/icon-*.png` → 192 × 192, 512 × 512, 180 × 180, 57 × 57 — alle **quadratisch**, keine Verzerrung aus dem 10000 × 7664 grossen Ausgangsbild |
| EC-03 | ✅ | Manifest führt `icon-512.png` zweimal: als `any` und als `maskable` (siehe AK-02) |
| EC-04 | ✅ | `viewport-fit=cover` im Viewport-Tag gemessen (AK-01), und die Leiste trägt `pb-[env(safe-area-inset-bottom)]` (AK-11) — beides ist nötig, eines allein wirkt nicht |

## Sicherheitsprüfung

Aktiv angegriffen, nicht gelesen. Grundlage: `~/.claude/sdd/sicherheit.md` und
`references/angriff.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Fremde ID (IDOR) | nicht anwendbar | B25 führt keine Datensätze. Das Äquivalent ist der **lokale Cache** — dort wurde gefunden, siehe BF-140 |
| Zugriffsregeln serverseitig | nicht anwendbar | keine Tabelle, keine Route mit Rechteprüfung |
| Rate Limit | nicht erforderlich | `/sw.js` wird **statisch** ausgeliefert (`etag`, `last-modified`, kein Symfony-Header) — kein PHP-Aufruf, keine Kosten je Anfrage |
| Personendaten in Protokollen | bestanden (für `prod`) | `var/log/dev.log` und `test.log` enthalten Testadressen (`.anna@example.lu`, `6a876d…@example.lu`) — das ist die bekannte, **akzeptierte** Lage aus BF-12 (`doctrine.DEBUG` in dev/test). In `prod` schreibt Monolog nach `php://stderr` erst ab `error` |
| Personendaten an externe Dienste | bestanden | `sw.js`, `offline.html`, `manifest.webmanifest` und `_bottom_nav.html.twig` enthalten **keine** externe URL. Die PWA lädt nichts von Dritten |
| Geheimnisse im Bundle | bestanden | `grep -roE "(sk-…|brevo\+api://…|SUPABASE_SERVICE|BEGIN … PRIVATE KEY)" public/build/` → kein Treffer |
| Cache-Vergiftung von aussen | bestanden | Der Worker cacht ausschliesslich die eigene Herkunft (AK-10) und nur `/build/`-Dateien sowie Bilder; eine Antwort mit `ok: false` wird **nicht** aufgenommen (`bei_ok_false_gecacht=nein`) |
| Personenbezogene Reste im Cache | **BF-140** | `avatar_im_cache=["…/uploads/avatars/nutzer-7.jpg"]` |

⚠ **Ein Messfehler auf dem Weg, hier dokumentiert, weil er sich wiederholen wird:** Der
erste Anlauf des Cache-Vergiftungstests meldete „bei 404 gecacht = JA". Das war falsch —
der nachgebaute `fetch` lieferte immer `ok: true`, der Lauf prüfte also nichts. Erst mit
einem Schalter für den Antwortstatus wurde die Prüfung aussagekräftig.

## Fehler

### BF-140 · Profilbilder landen im Cache des Service Workers — mittel

**Betrifft:** AK-19, AK-20
**Reproduktion:**
1. `node qa/B25/sw-verhalten.mjs`
2. Der Abschnitt *Angriff 2* stellt eine Anfrage auf
   `https://endlech.lu/uploads/avatars/nutzer-7.jpg` mit `destination: 'image'`
3. Danach den Cache-Inhalt lesen
**Erwartet:** Der Cache enthält App-Shell und Assets — „keine personenbezogenen Inhalte"
(AK-20)
**Tatsächlich:** `avatar_im_cache=["https://endlech.lu/uploads/avatars/nutzer-7.jpg"]`
**Ort:** `public/sw.js:96-104` — der letzte `fetch`-Zweig nimmt jede Antwort auf, deren
`request.destination === 'image'` ist. Avatare liegen unter `/uploads/avatars/`, werden
als Bild geladen und erfüllen die Bedingung.
**Folge:** Auf einem geteilten Gerät bleibt das Profilbild eines abgemeldeten Nutzers im
Cache — bis `CACHE_VERSION` wechselt, also möglicherweise monatelang. Kein Abfluss an
Dritte, aber ein personenbezogenes Datum, das dort nichts zu suchen hat (Art. 5 Abs. 1
lit. e DSGVO).
**Vorschlag:** Im Bild-Zweig `/uploads/avatars/` ausnehmen — dieselbe Konstruktion wie
die `/api/`-Ausnahme weiter oben. Restaurantfotos (`/uploads/restaurants/`) dürfen
bleiben, sie sind Gemeingut der Plattform.

### BF-141 · Der Worker greift auf dem locale-präfixierten API-Weg ein — niedrig

**Betrifft:** AK-09
**Reproduktion:** `node qa/B25/sw-verhalten.mjs`, Abschnitt *Angriff 1*:
`https://endlech.lu/de/api/cuisines/search?q=pizza` → `locale_api_eingegriffen=JA`
**Erwartet:** Kein Eingriff — „API-Daten bleiben immer frisch" (AK-09)
**Tatsächlich:** Die Anfrage landet im letzten Zweig (cache-first mit Netz-Fallback), weil
`url.pathname.startsWith('/api/')` auf `/de/api/cuisines/search` nicht zutrifft.
**Ort:** `public/sw.js:58-61`; der Pfad entsteht aus `config/routes.yaml` — der ältere
`CuisineApiController` liegt weiterhin **unter** `/{_locale}` (so auch in `CLAUDE.md`
vermerkt), anders als `/api/v1` und `/open`.
**Folge:** Heute kein Schaden: Gecacht wird die JSON-Antwort nicht, weil der Zweig nur
Bilder und `/icons/` aufnimmt. Der Worker fragt aber zuerst den Cache — und wer diesen
Zweig je erweitert, macht die Lücke im selben Moment scharf.
**Vorschlag:** Die Ausnahme auf `/\/(?:[a-z]{2}\/)?api\//` erweitern, oder den
`CuisineApiController` locale-frei stellen (das wäre die Ursache, betrifft aber Routing
und Admin-Frontend).

### BF-142 · AK-17 und FB-01 beschreiben einen überholten Stand — niedrig

**Betrifft:** AK-17, FB-01
**Reproduktion:** Messung auf `/de/profile` bei 500 px Viewport
**Erwartet (laut Spec):** „dann findet er keinen Weg dafür" — für Abmelden und Sprachwahl
**Tatsächlich:** Abmelden ist sichtbar (387 × 44 px; das zweite Logout-Formular der Seite
trägt kein `hidden md:`), die Sprachwahl ebenfalls (55 × 28 px, seit BF-72 auch auf
Mobil). „Restaurant vorschlagen", Partner und Organisationen stehen in der Fussleiste,
„Über uns" in der Leiste und seit dem 2026-09-12 zusätzlich in der Fussleiste.
**Ort:** `features/B25-pwa-mobile-navigation/spec.md:88-97` (AK-17), `:120-121` (FB-01)
**Folge:** Die Spec nennt AK-17 „den zentralen Befund" des Features und OF-01 fragt nach
einem Menüfeld als Ersatz. Wer das heute liest, baut eine Lösung für ein Problem, das
zum Teil nicht mehr besteht — dieselbe Klasse wie BF-126, BF-130, BF-132.
**Vorschlag:** AK-17 auf den gemessenen Stand bringen: Was tatsächlich fehlt, ist ein
Weg zum **Menü „Mitmachen"** und zu den Unterseiten der Organisationen innerhalb der
Leiste; alles andere ist über Leiste, Profilseite und Fussleiste erreichbar. OF-01 danach
neu beantworten.

### BF-143 · 64 px Leerraum am Fuss jeder Verwaltungsseite auf dem Telefon — niedrig

**Betrifft:** AK-13, AK-15
**Reproduktion:** `/de/admin` bei 500 px messen → `bottomnav=FEHLT` **und**
`main.paddingBottom=64px`
**Erwartet:** Polster nur, wo die Leiste liegt
**Tatsächlich:** `<main>` trägt `pb-16 md:pb-0` unabhängig von der Route; die Leiste
erscheint im Verwaltungsbereich aber nicht (AK-13, gewollt).
**Ort:** `templates/base.html.twig`, `<main …class="flex-grow pb-16 md:pb-0 …">`
**Folge:** Rein kosmetisch — ein leerer Streifen unter dem Inhalt, nur im
Verwaltungsbereich und nur unter 768 px.
**Vorschlag:** Dieselbe Bedingung verwenden, die schon über die Leiste entscheidet
(`app.request.attributes.get('_route') starts with 'admin_'`), und das Polster daran
hängen.

### BF-144 · Der Sprachumschalter ist auf dem Telefon 28 px hoch — niedrig

**Betrifft:** AK-11 (mittelbar), Projektregel für Zielgrössen
**Reproduktion:** Messung auf `/de/profile` bei 500 px → `sprachwahl … groesse=55x28`
**Erwartet:** Nach der Projektregel tragen Aktionen `min-h-[48px]` (so in `CLAUDE.md` für
die Aussenseiten festgehalten); die Leiste selbst hält 44 px (AK-11)
**Tatsächlich:** 55 × 28 px. WCAG 2.5.8 (24 × 24 px) ist erfüllt, die Projektregel nicht.
**Ort:** `templates/partials/_language_switcher.html.twig`, Knopf mit `px-2 py-1`
**Folge:** Das kleinste Ziel im mobilen Kopfbereich — und seit BF-72 ausdrücklich für
Mobil gedacht.
**Vorschlag:** `py-2.5` bzw. `min-h-[44px]` unter `md:`, ohne die Desktop-Darstellung zu
verändern.

### BF-145 · `offline.html` trägt `lang="de"`, der Inhalt ist luxemburgisch — niedrig

**Betrifft:** AK-05 (mittelbar), WCAG 3.1.1
**Reproduktion:** `grep -oE '<html[^>]*>' public/offline.html` → `<html lang="de">`; der
sichtbare Text lautet „Keng Internetverbindung", „Du bass momentan offline. Iwwerpréif
deng Verbindung a probéier et nach eemol.", „Nach eemol probéieren"
**Erwartet:** Das `lang`-Attribut nennt die Sprache des Inhalts
**Tatsächlich:** `de` bei luxemburgischem Text. Gegengeprüft am `lb`-Katalog („Keng
Öffnungszäiten uginn", „Keng oppe Virschléi") — die Formen sind luxemburgisch; im
`de`-Katalog gibt es keine Entsprechung.
**Ort:** `public/offline.html:2`
**Folge:** Ein Screenreader wendet deutsche Ausspracheregeln auf luxemburgischen Text an.
Auf einer Barrierefreiheitsplattform ist das ein sinnfälliger Fehler, auch wenn die Seite
selten erscheint. ⚠ **Nicht der Text ist falsch, sondern die Auszeichnung:**
`translation.yaml` setzt `default_locale: lb`, und eine statische Datei ist nicht
lokalisierbar (EC-01) — luxemburgisch ist hier die richtige Wahl.
**Vorschlag:** `lang="lb"`.
**Herkunft:** `code-reviewer`, verifiziert.

## Vom `code-reviewer` gemeldet und **nicht** bestätigt

- **„`aria-current="page"` wird durch Twigs Auto-Escaping zerstört"** (Konfidenz 85 des
  Agenten). Der Befund beschreibt `templates/partials/_bottom_nav.html.twig:22`,
  `{{ is_active ? 'aria-current="page"' : '' }}`, und sagt, die Anführungszeichen kämen
  als `&quot;` heraus. **Widerlegt, dreifach:** Das gerenderte HTML enthält
  `aria-current="page"` unescaped (`curl … | grep -oE 'aria-current[^ >]*'`), die Suche
  nach `&quot;page&quot;` bleibt leer, und der Browser liest
  `getAttribute('aria-current') === 'page'` (Messung `ariaCurrent=1`). Auch
  `PwaTest::testAk11Ak12…` greift mit dem Selektor `a[aria-current="page"]` und ist grün.
  **Der Grund:** Twigs Auto-Escaping lässt **konstante Ausdrücke** unangetastet — ein
  String-Literal im Ternary gilt als sicher und wird nicht escaped. Das Muster ist damit
  zulässig, auch wenn die `{% if %}`-Form aus
  `templates/admin/waitlist/index.html.twig:21` die lesbarere ist.
  ⚠ Aufgeführt, weil ein widerlegter Fund genauso zur Prüfung gehört wie ein bestätigter:
  Wer ihn später in der Agentenausgabe findet, sieht hier, dass er geprüft wurde.

## Hinweise ohne Fehlerstatus

- **`/sw.js` wird ohne `Cache-Control` ausgeliefert** (gemessen: nur `etag` und
  `last-modified`). Browser behandeln den Worker heuristisch und prüfen ihn bei jeder
  Navigation ohnehin; garantiert ist eine sofortige Aktualisierung damit aber nicht. Für
  eine Datei, deren ganzer Zweck die Steuerung des Caches ist, wäre `Cache-Control:
  no-cache` die klarere Zusage. **Kein Befund**, weil kein Fehlverhalten nachgewiesen
  ist — aber der Punkt gehört notiert, weil er genau dann zählt, wenn
  `CACHE_VERSION` erhöht wurde (heute der Fall, BF-99).
- **Auch die gehashten Dateien unter `/build/` tragen kein `Cache-Control`.** Bei
  Encore-Hashing wäre `max-age=31536000, immutable` gefahrlos möglich. Gemildert wird das
  heute vom Worker selbst (AK-07, stale-while-revalidate) — für Besucher **ohne**
  Service Worker, etwa im privaten Fenster, bleibt es ein vermeidbarer Rückweg zum
  Server. Gehört zur App-Hülle, nicht zu B25.
- **AK-16 ist heute eingetreten, nicht theoretisch.** Die Handpflege von
  `CACHE_VERSION` war im Rahmen von BF-99 nötig; ohne sie hätte ein wiederkehrender
  Besucher die alte Presse-Vorschau neben dem neuen Paket gesehen. OF-02 (Ableitung aus
  `app.version`) ist damit eine Frage mit Beleg statt einer Vermutung.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/PwaTest.php` | 8 (drei davon über einen Datenprovider) | AK-01, AK-02, AK-11, AK-12, AK-13, AK-15, EC-01, EC-03 |
| `qa/B25/sw-verhalten.mjs` | 11 Messungen, ausführbar mit `node` | AK-04 bis AK-10, AK-18, AK-19 und drei Angriffe |

⚠ **Warum das Worker-Skript kein PHPUnit-Test ist:** Der Service Worker läuft im Browser,
nicht im Kernel. Das Skript lädt `public/sw.js`, ruft die Handler mit nachgebauten
Ereignissen auf und protokolliert, was tatsächlich passiert — ausgeführter Code also,
kein gelesener. Es bringt **keine neue Abhängigkeit** mit (reines Node, kein Testrunner;
das Projekt hat keinen für JavaScript).

## Nächster Schritt

Kein Befund ist kritisch oder hoch — die Erfassung läuft weiter. Die fünf Befunde stehen
in `features/befunde.md` — **sechs** mit dem Fund des `code-reviewer` (BF-145); BF-140 ist
der einzige mit Datenschutzbezug und gehört in den nächsten Reparaturauftrag
(`/sdd-build` mit BF-140 bis BF-145).

Status: `rekonstruiert` → **`approved`**.
