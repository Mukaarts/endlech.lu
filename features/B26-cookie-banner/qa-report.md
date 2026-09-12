# B26 · Cookie-Banner — Testbericht

Stand: 2026-09-12 · Geprüft gegen `spec.md` vom 2026-08-23 (Rekonstruktion)

## Fazit

**Production-ready: ja**

Alle elf Kriterien bestanden, alle vier Randfälle belegt. Das Banner erscheint beim
Erstbesuch, verschwindet nach der Wahl, bleibt nach einem Neuladen verschwunden, lässt
sich über die Fußzeile erneut öffnen und entsteht im Verwaltungsbereich gar nicht erst.
Die Auszeichnung für Screenreader ist vollständig, die Knöpfe sind 46 px hoch, und die
Kontraste liegen zwischen **5,54 : 1** und **17,75 : 1** — alle über der AA-Schwelle.

Der einzige Befund betrifft **nicht den Code, sondern die Spezifikation**: Sie führt zwei
Angaben, die überholt sind (BF-146). Deshalb ist auch kein Deployment nötig — an der
Anwendung ändert sich nichts.

Zwei Dinge sind erwähnenswert, weil sie die Bewertung des Features drehen. Erstens:
Gemessen setzt die Anwendung **vor** der Wahl überhaupt keine Cookies
(`cookies_vor_wahl=[]`), und die Seite lädt **nichts** von Dritten
(`fremde_ressourcen=[]`) — es gibt also nichts, wofür eine Einwilligung nötig wäre. Der
Banner fragt nach etwas, das nicht stattfindet; OF-01 der Spec stellt genau diese Frage,
und diese Prüfung liefert ihr die Zahlen. Zweitens: Die in FB-05 beschriebene fehlende
Fokusführung ist **kein Mangel, sondern eine Entscheidung** — beim Klick auf
„Cookie-Einstellungen" wandert der Fokus ins Banner (gemessen: `DIV [im Banner]`), beim
automatischen Erscheinen bewusst nicht, weil sonst der Skip-Link „Zum Inhalt springen"
nicht mehr das erste Tab-Ziel wäre (BF-74, WCAG 2.4.1 — gemessen ist er es).

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 11 von 11 |
| davon bestanden | 11 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 4 von 4 (EC-04 teilweise, siehe unten) |
| Tests neu geschrieben | 4 |
| Tests grün | 1109 von 1109 |
| Befunde | 1 — *niedrig*, betrifft die Spezifikation |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Browsermessung bei 500 px: `cookie_vorher=keins banner_sichtbar=true` |
| AK-02 | ✅ bestanden | Nach der Wahl und `location.reload()`: `cookie=cookie_consent=accepted banner_sichtbar_nach_reload=false` |
| AK-03 | ✅ bestanden | Klick auf „ablehnen": `cookie=cookie_consent=declined banner_sichtbar=false`; Klick auf „annehmen" in einem zweiten Durchlauf: `cookie_consent=accepted`. ⚠ Das Cookie wurde über **HTTP** (127.0.0.1) gesetzt und kam an — also ohne `secure`, wie es das Protokoll verlangt (EC-04) |
| AK-04 | ✅ bestanden | Klick auf den Fußzeilenknopf (`cookie-consent#openSettings`): `banner_nach_klick=true`. Damit sind **EC-01 und EC-02 mitbelegt** — die Verständigung zwischen den beiden Controller-Instanzen läuft über das Fenster-Ereignis, und die Fußzeilen-Instanz ohne Banner-Ziel wirft nicht |
| AK-05 | ✅ bestanden | `CookieBannerTest::testAk05…` (role `dialog`, `aria-modal="false"`, `aria-labelledby`, `aria-describedby`, `tabindex="-1"`, zwei echte `<button>`); Messung: `position=fixed`, Knöpfe **117 × 46** und **135 × 46** px, Kontraste Titel **17,75**, Text **14,33**, Link **9,81**, Knopf „ablehnen" **14,33**, Knopf „annehmen" **5,54** — Grundfarbe `rgb(16,24,40)` |
| AK-06 | ✅ bestanden | `CookieBannerTest::testAk06…`; Messung: auf `/de/admin` **0** Treffer für Banner und Fußzeilenknopf, auf `/de/` je **1** |
| AK-07 | ✅ bestanden | Messung: `cookies_vor_wahl=[]`, `cookies_nach_ablehnen=["cookie_consent"]` — die Anwendung setzt auf der Startseite **gar kein** eigenes Cookie, weder vor noch nach der Wahl. Die Ablehnung ändert also nichts, weil es nichts zu ändern gibt |
| AK-08 ⚠ | ✅ bestanden | Bestand bestätigt: Ausser dem Banner-Template und `cookie_consent_controller.ts` liest **kein** Code `cookie_consent` (Suche über `src/`, `templates/`, `assets/`). Keine serverseitige Auswertung, kein bedingtes Nachladen |
| AK-09 ⚠ | ✅ bestanden | Bestand bestätigt: `cookie_inhalt=declined enthaelt_zeitstempel=false` — kein Zeitstempel, keine Fassungsnummer |
| AK-10 | ✅ bestanden | dito: Der Wert ist `accepted` bzw. `declined`, nichts Personenbeziehbares |
| AK-11 | ✅ bestanden | `CookieBannerTest::testAk11…` (keine Adresse mit Protokoll oder `//` in `script[src]`, `link[rel=stylesheet]`, `link[rel=preload]`, `img[src]`); Browsermessung über `performance.getEntriesByType('resource')`: `fremde_ressourcen=[]`. ⚠ Das gilt **auch nach BF-99**: Inter wird selbst gehostet, es geht keine Anfrage an `fonts.gstatic.com` |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | Der Fußzeilenknopf öffnet das Banner (AK-04) — das Fenster-Ereignis `cookie-consent:open@window` trägt also zwischen zwei getrennten Controller-Instanzen |
| EC-02 | ✅ | Derselbe Klick löst keinen Fehler aus, obwohl die Fußzeilen-Instanz **kein** Banner-Ziel hat; `hasBannerTarget` greift. Zusätzlich: Die Browserkonsole blieb über alle Messläufe ohne Ausnahme |
| EC-03 | ✅ | `CookieBannerTest::testEc03…`: Das Banner wird mit der Klasse `hidden` ausgeliefert; nur `#show()` nimmt sie weg (`classList.remove('hidden')`). Ohne JavaScript bleibt es damit unsichtbar — und die Anwendung unverändert bedienbar |
| EC-04 | ✅ (teilweise) | Belegt ist die Protokollabhängigkeit in der Richtung, die prüfbar ist: Über **HTTP** wurde das Cookie gesetzt und war lesbar — mit `secure` hätte der Browser es verworfen. Dass auf **HTTPS** zusätzlich `secure` gesetzt wird, ist von aussen nicht messbar: `document.cookie` gibt die Flags nicht her, und auf HTTPS käme ein Cookie mit und ohne `secure` gleichermassen an |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Fremde ID (IDOR) | nicht anwendbar | B26 führt keine Datensätze und keine IDs |
| Zugriffsregeln serverseitig | nicht anwendbar | keine Route, keine Tabelle — das Feature ist ein Template und ein Stimulus-Controller |
| Rate Limit | nicht anwendbar | kein Endpunkt; die Wahl wird ausschliesslich im Browser gespeichert |
| Personendaten im Cookie | bestanden | `cookie_consent=declined` bzw. `accepted` — zwei Werte, keine Kennung, kein Zeitstempel (AK-09, AK-10) |
| Personendaten an externe Dienste | bestanden | `fremde_ressourcen=[]` — die Seite ruft keinen Dritten auf (AK-11) |
| Cookie-Eigenschaften | bestanden | `path=/`, Lebensdauer 365 Tage, `samesite=lax`, `secure` protokollabhängig (EC-04). Kein `httpOnly` — richtig, das Cookie **muss** für JavaScript lesbar sein, sonst könnte der Controller die Wahl nicht auswerten |
| Eingaben | nicht anwendbar | Das Feature nimmt keine Eingabe an; die Wahl sind zwei Knöpfe ohne Parameter |
| Geheimnisse | bestanden | Der Controller trägt keine Schlüssel; `public/build/` wurde im Rahmen der B25-Prüfung abgesucht (kein Treffer) |

⚠ **Drei eigene Messfehler auf dem Weg, hier dokumentiert, weil sie sich wiederholen
werden:**

1. Der erste Kontrastwert lautete **262,39** — unmöglich, das Maximum ist 21. Ursache:
   Der Hintergrund des Banners ist am Element selbst `rgba(0,0,0,0)`; gerechnet wurde
   gegen eine transparente Farbe.
2. Nach der Korrektur (effektive Hintergrundfarbe über die Elternkette) kamen Werte wie
   **1,00** heraus. Ursache: **Tailwind v4 liefert `oklch()`**, und ein Parser, der
   Zahlen aus der Zeichenkette zieht, rechnet damit Unsinn. Tragfähig wurde die Messung
   erst über ein `<canvas>`: Es nimmt jede CSS-Farbe an und gibt sRGB zurück.
3. Der erste Fokus-Messwert (`fokus_nach_erscheinen=BODY`) sah wie ein Befund aus. Er ist
   das **gewollte** Verhalten — siehe BF-146 und die Begründung im Controller.

## Fehler

### BF-146 · Die Spezifikation beschreibt an zwei Stellen einen überholten Stand — niedrig

**Betrifft:** FB-05, AK-11 (Klammer)
**Reproduktion und Gegenbeweis:**
1. **FB-05 sagt:** „Keine Fokusführung. Beim Erscheinen wandert der Fokus nicht ins
   Banner; ein Tastaturnutzer erreicht es erst nach der gesamten Seite."
   Gemessen: Beim Klick auf „Cookie-Einstellungen" wandert der Fokus **ins Banner**
   (`nach reopen: fokus=DIV [im Banner]`), das Banner trägt `tabindex="-1"`. Beim
   automatischen Erscheinen bleibt er aussen — **mit Begründung im Code**
   (`cookie_consent_controller.ts:50-56`): Sonst zöge der Fokus den ersten Tab in das
   Banner, und der Skip-Link wäre nicht mehr das erste Tab-Ziel (BF-74). Gemessen ist er
   es: `erstes Tab-Ziel=A „Zum Inhalt springen"`.
   ⚠ **OF-02 vermerkt das bereits** („Geprüft 2026-08-25: Bereits umgesetzt") — nur wurde
   FB-05 nicht nachgezogen. Die Spec widerspricht sich damit selbst.
2. **AK-11 sagt:** „(Der Feedback-Link in der Fußzeile führt zu `endlech.userjot.com`,
   lädt aber nichts nach.)" Der Link zeigt seit dem 2026-08-30 auf das eigene Board
   (`/community/ideen`), und `endlech.userjot.com` ist seit dem 2026-09-12 **abgeschaltet**
   (BF-103, von aussen nachgemessen).
**Ort:** `features/B26-cookie-banner/spec.md` — FB-05 und die Klammer in AK-11
**Folge:** Keine für den Betrieb. Aber FB-05 wäre der Punkt, an dem jemand eine
Fokusführung „nachrüstet", die absichtlich so ist, wie sie ist — und dabei BF-74 wieder
aufreisst. Genau deshalb steht das hier.
**Vorschlag:** FB-05 auf den gemessenen Stand bringen (umgesetzt für `reopen`, bewusst
nicht für `connect`, Verweis auf BF-74) und die userjot-Klammer in AK-11 streichen.

## Hinweise ohne Fehlerstatus

- **OF-01 hat jetzt Zahlen.** Die Frage „Braucht die Seite den Banner überhaupt?" lässt
  sich mit dieser Prüfung beantworten: Vor der Wahl setzt die Anwendung **kein** Cookie,
  und die Seite lädt **nichts** von Dritten. Es gibt damit keine einwilligungspflichtige
  Verarbeitung, für die der Banner die Einwilligung einholen könnte — die technisch
  notwendigen Cookies (Sitzung, CSRF, `REMEMBERME`) sind einwilligungsfrei und entstehen
  ohnehin erst, wenn man sich anmeldet oder ein Formular abschickt. **Die Entscheidung
  bleibt beim Betreiber**, aber sie ist keine Vermutung mehr.
- **Kein `httpOnly` auf `cookie_consent` — und das ist richtig.** Der Controller muss die
  Wahl im Browser lesen; ein `httpOnly`-Cookie wäre für ihn unsichtbar. Erwähnt, weil ein
  Prüflauf „Cookies ohne httpOnly" hier sonst fälschlich anschlägt.
- **Die Knöpfe sind 46 px hoch** und liegen damit über den 44 px, die B25/AK-11 für die
  Bottom-Navigation verlangt — auf dieser Plattform der richtige Bezugswert, nicht die
  24 px aus WCAG 2.5.8.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/CookieBannerTest.php` | 4 | EC-03, AK-05, AK-06, AK-11 |

Die clientseitigen Zusagen (AK-01 bis AK-04, AK-07, AK-09, AK-10, EC-01, EC-02) sind mit
Browsermessungen belegt, die im Abschnitt *Akzeptanzkriterien* zitiert sind; sie lassen
sich ohne einen Browser nicht automatisieren und ohne einen Testrunner für JavaScript —
den das Projekt bewusst nicht hat — auch nicht festschreiben.

## Nächster Schritt

Kein Befund am Code, einer an der Spezifikation. **Kein Deployment nötig** — die Anwendung
ist unverändert. BF-146 gehört in den nächsten Dokumentationsdurchgang, zusammen mit
BF-142 (dieselbe Klasse bei B25).

Status: `rekonstruiert` → **`approved`**.

---

## Nachtrag vom 2026-09-12 · BF-146 behoben

Beide Stellen der Spezifikation sind berichtigt:

- **FB-05** sagt jetzt, was gemessen wurde: Beim nutzergetriggerten Öffnen wandert der
  Fokus ins Banner, beim automatischen Erscheinen bewusst nicht — mit Verweis auf BF-74
  und darauf, dass der Skip-Link das erste Tab-Ziel bleiben muss. Der alte Wortlaut steht
  durchgestrichen daneben, weil er drei Absätze über OF-02 stand, das die Umsetzung schon
  am 2026-08-25 vermerkte.
- **Die Klammer in AK-11** nennt statt `endlech.userjot.com` das eigene Ideen-Board und
  hält fest, dass die Zusage „keine Fremdressourcen" auch nach BF-99 gilt: Inter liegt
  unter `/build/fonts/`, gemessen `fremde_ressourcen=[]`.

**Damit hat dieses Feature keinen offenen Befund mehr.**
