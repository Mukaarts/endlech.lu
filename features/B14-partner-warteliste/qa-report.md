# B14 · Partner-Warteliste — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — zwei mittlere und zwei niedrige Befunde, keiner davon
technisch.

23 von 23 Kriterien bestanden, 5 von 5 Edge Cases. Das ist das sauberste Feature dieser
Prüfreihe: Honeypot, Rate Limit, Turbo-Stream, Double-Opt-In, Mailfehlerbehandlung und
die Trennung zwischen „bereits bestätigt" und „unbekannter Token" verhalten sich alle
exakt wie beschrieben, an den Grenzwerten nachgemessen. Auch die Mailfehlerbehandlung
stimmt: Bei gestopptem Transport blieb der Eintrag gespeichert und der Nutzer bekam eine
verständliche Meldung.

**Was fehlt, ist nicht der Code, sondern der Rechtsrahmen.** Die Einwilligung wird
sauber erfasst (`consentAt`, Zeitpunkt, Sprache, Herkunft) — aber sie lässt sich nicht
widerrufen, und die Daten haben keine Löschfrist. Art. 7 Abs. 3 DSGVO verlangt, dass der
Widerruf so einfach ist wie die Erteilung; Art. 5 Abs. 1 lit. e verlangt eine
Speicherbegrenzung. Beides fehlt, und beides ist eine Zeile Code weniger als eine
Entscheidung.

Bemerkenswert: `PartnerWaitlistEntryRepository::findPendingOlderThan()` existiert und ist
offensichtlich für die Aufräumroutine gedacht — sie wird **nirgends im Produktivcode
aufgerufen**, nur in einem Test. Toter Code, der eine Aufräumlogik vortäuscht.

Nächster Aufruf: **`/sdd-erfassen B15`**. Die Erfassung läuft weiter.

## Eine Korrektur an der Rekonstruktion

**FB-06 der Spec ist falsch.** Sie behauptet: *„Der Freitext `message` wird nicht
begrenzt geprüft."*

Gemessen: 20.000 Zeichen im Feld `message` → **HTTP 422**. Im Code steht
`src/Form/PartnerWaitlistType.php:77`: `new Length(max: 2000, maxMessage: 'partner_waitlist.message_max')`.
Alle fünf Textfelder tragen eine Längengrenze (180 / 120 / 180 / 40 / 120 / 2000).

Die Spec ist in `spec.md` berichtigt, der alte Wortlaut bleibt durchgestrichen stehen.

## Akzeptanzkriterien im Einzelnen

### Anmeldung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `/de/partner` → **200**, Formular mit neun Feldern gerendert |
| AK-02 | ✅ bestanden | DB nach dem Absenden: `status=pending`, `locale=de`, `source=qa-kampagne` (aus `?utm_source=`), `consent_at=2026-08-24 16:16:22`, Token vorhanden |
| AK-03 | ✅ bestanden | Mail an `anna@qa.example`, Betreff „Bestätigen Sie Ihre Anmeldung zur Warteliste", Link `http://localhost:8000/de/partner/confirmation/3dc4faa8…` (64 Hex, absolut) |
| **AK-04** | ✅ bestanden | Mailpit gestoppt → HTTP 302, Einträge **10 → 11**, `QA Mailfehler status=pending` in der DB, Meldung: *„Ihre Anmeldung ist gespeichert, aber die Bestätigungsmail konnte nicht versendet werden."* |
| **AK-05** | ✅ bestanden | mit `Accept: text/vnd.turbo-stream.html` → **200**, `Content-Type: text/vnd.turbo-stream.html`, Rumpf beginnt mit `<turbo-stream action="replace" target="partner-waitlist-form">` |
| AK-06 | ✅ bestanden | ohne Turbo-Accept → **302** nach `/de/partner` |
| **AK-07** | ✅ bestanden | ungültiges Formular **mit** Turbo-Accept → **422** und `Content-Type: text/html` — nicht turbo-stream. EC-03 damit belegt |

### Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-08** | ✅ bestanden | Honeypot gefüllt → 302, Einträge **1 → 1**, **0 Mails**. Die Antwortrümpfe von Honeypot- und Erfolgsfall sind **byteweise identisch** (md5 `dca1be0d…` beide) |
| **AK-09** | ✅ bestanden | `302 302 302 302 302 429` — fünf durch, der sechste abgewiesen; Meldung *„Sie haben in kurzer Zeit mehrere Anmeldungen abgeschickt…"* |
| **AK-10** | ✅ bestanden | **10 reine GETs** auf `/de/partner`, danach gingen fünf Submits durch. Der Seitenaufruf verbraucht nichts. EC-04 damit belegt |
| AK-18 | ✅ bestanden | `PartnerWaitlistType`: kein `Blank`-Constraint am Feld `website`, nur `'mapped' => false` |
| **AK-19** | ✅ bestanden | `<div aria-hidden="true" class="absolute w-px h-px -left-[9999px] overflow-hidden">` mit `<input type="text" … tabindex="-1">`. **Kein** `type="hidden"` — genau wie beschrieben |

### Bestätigung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-11 | ✅ bestanden | Link eingelöst → 200, `status=confirmed`, `confirmed_at` gesetzt, interne Meldung an `info@endlech.lu` |
| AK-12 | ✅ bestanden | zweiter Aufruf desselben Links → Seite enthält „bereits"; Token steht noch in der DB (EC-02) |
| AK-13 | ✅ bestanden | 64 unbekannte Hex-Zeichen → **404**, keine Exception |
| AK-14 | ✅ bestanden | `abc` → 404 · 64 **Groß**buchstaben → 404. Das Requirement `[a-f0-9]{64}` greift vor dem Controller |
| **AK-15** | ✅ bestanden | Bestätigung über `/fr/partner/confirmation/…` → interne Mail trotzdem **auf Deutsch** („Neue bestätigte Partner-Anmeldung"), `Reply-To: anna@qa.example`. Tests `testAk15InterneMeldungBleibtDeutschBeiFranzoesischerBestaetigung` und `…TraegtDenInteressentenAlsReplyTo` |
| **AK-16** | ✅ bestanden | Mailpit gestoppt, dann bestätigt → **200**, `status=confirmed`. Der Nutzer merkt vom gescheiterten Versand nichts |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-17 | ✅ bestanden | Spalten: `id, restaurant_id, restaurant_name, contact_name, email, phone, locality, message, status, confirmation_token, confirmed_at, consent_at, locale, source, created_at, updated_at` — **keine IP-Adresse** |
| AK-20 | ✅ bestanden | `consent_at` wird beim Anlegen gesetzt (siehe AK-02) |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-21** ⚠ | ✅ bestätigt | `DESCRIBE partner_waitlist_entry` → **0** Spalten mit „expires". Kein Ablauf → BF-36 |
| **AK-22** ⚠ | ✅ bestätigt | `debug:router` → **0** passende Routen; **0** Abmeldelinks in den Mailvorlagen → BF-37 |
| **AK-23** ⚠ | ✅ bestätigt | Nach fünf Partner-Submits liefert das **Organisations**formular **429** mit der Meldung aus `flash.partner_rate_limited`. Beide Controller: `#[Autowire(service: 'limiter.partner_waitlist')]` → BF-38 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `#[ORM\HasLifecycleCallbacks]` (Zeile 13), `#[ORM\PreUpdate]` (Zeile 89), Initialisierung im Konstruktor mit Kommentar |
| EC-02 | ✅ bestanden | nach der Bestätigung: `token=steht noch` — nur so ist AK-12 von AK-13 unterscheidbar |
| EC-03 | ✅ bestanden | siehe AK-05 und AK-07 — Erfolgsfall turbo-stream, Fehlerfall `text/html` |
| EC-04 | ✅ bestanden | siehe AK-10 |
| EC-05 | ✅ bestanden | `when@test`-Override auf 10000 vorhanden; die Suite läuft grün trotz elf Submit-Tests |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Rate Limit überrannt** | greift exakt an der Grenze (5/6), GET verbraucht nichts |
| **Honeypot** | gefüllt → nichts gespeichert, nichts versandt, Antwort byteweise identisch |
| **Token raten** | 64 Hex aus `random_bytes(32)`; unbekannt → 404, falsches Format → Route greift nicht |
| **XSS über den Freitext** | `<script>alert(1)</script>` als Restaurantname, `<img src=x onerror=alert(1)>` als Nachricht → in **beiden** Verwaltungsansichten (`/de/admin/warteliste` und die Detailseite) **maskiert**, nicht ausführbar |
| **Längengrenzen** | 20.000 Zeichen → 422 (siehe die Spec-Korrektur oben) |
| **Personenbezogene Daten** | keine IP-Adresse, kein User-Agent |
| **Mailfehler** | Eintrag bleibt erhalten, Meldung verständlich, Bestätigung funktioniert trotzdem |

## Fehler

### BF-36 · Der Bestätigungstoken läuft nie ab — niedrig

**Betrifft:** AK-21 · FB-03 der Spec

**Nachweis:** `DESCRIBE partner_waitlist_entry` → keine Spalte mit „expires".
`PartnerWaitlistEntry::generateConfirmationToken()` setzt keinen Ablaufzeitpunkt —
anders als `User::generateVerificationToken()`, das 24 Stunden vergibt.

**Folge:** Ein Token, der einmal in einem fremden Postfach, in einem weitergeleiteten
Mailverlauf oder in einem Server-Log landet, bleibt dauerhaft einlösbar. Der Schaden ist
gering — eingelöst wird eine Wartelisten-Bestätigung, keine Kontoübernahme — aber es ist
ein Muster, das im selben Projekt an anderer Stelle bereits richtig gelöst ist.

**Vorschlag:** Dieselben 24 Stunden wie bei `User`, mit derselben Mechanik. Wichtig
dabei: **Der Token darf trotzdem stehen bleiben** — sonst fällt AK-12 („bereits
bestätigt") mit AK-13 („Link ungültig") zusammen. Die Frist ist ein zusätzliches Feld,
kein Ersatz für den Token.

### BF-37 · Die Einwilligung lässt sich nicht widerrufen — mittel

**Betrifft:** AK-22 · FB-01 der Spec

**Nachweis:**
- `debug:router` → **0** Routen, die auf Abmelden, Austragen oder Widerruf passen
- **0** Abmeldelinks in den Mailvorlagen unter `templates/email/`
- Auch die Verwaltung (B22) kennt keine Löschfunktion

**Warum das über einen Komfortmangel hinausgeht:** Die Anmeldung erfasst `consentAt`
sorgfältig — Zeitpunkt, Sprache, Herkunftsquelle — und macht damit sichtbar, dass das
Verarbeiten auf einer **Einwilligung** beruht. Art. 7 Abs. 3 DSGVO verlangt, dass der
Widerruf so einfach ist wie die Erteilung. Erteilt wird er mit einem Klick auf einen
Link in einer Mail; widerrufen lässt er sich gar nicht.

**Verstärkt durch die fehlende Löschfrist** (FB-02): Nicht bestätigte Anmeldungen
bleiben unbefristet gespeichert. `PartnerWaitlistEntryRepository::findPendingOlderThan()`
ist genau dafür geschrieben — und wird **nur im Test** aufgerufen:
```
src/Repository/PartnerWaitlistEntryRepository.php:31   public function findPendingOlderThan(...)
tests/Integration/Repository/PartnerWaitlistEntryRepositoryTest.php:29   $found = $this->repository->findPendingOlderThan(...)
```
Das ist die unangenehmere Hälfte: Der tote Code *sieht aus*, als gäbe es eine
Aufräumroutine. Wer die Datenhaltung prüft und die Methode findet, hakt den Punkt ab.

**Vorschlag:** Ein signierter Abmeldelink in jeder Mail — er deckt zugleich FB-05
(Auskunft) teilweise ab, weil er einen Einstiegspunkt ohne Konto schafft. Dazu ein
Konsolenbefehl auf `findPendingOlderThan()`, an denselben Cron gehängt wie
`app:metrics:snapshot`. Das Muster steht im Projekt bereits (`src/Command/`,
`src/Schedule.php`).

**Verwandt mit BF-04** (Betroffenenrechte, Feature `01`), aber nicht identisch: Dort geht
es um Konten, hier um Wartelisten-Daten ohne Konto. Ein Widerrufsweg über einen
signierten Link erreicht Menschen, die nie ein Konto hatten — Feature `01` erreicht sie
nicht.

### BF-38 · Beide Wartelisten teilen sich ein Kontingent — niedrig

**Betrifft:** AK-23 · OF-03 der Spec

**Reproduktion:**
1. Fünfmal `POST /de/partner` (Kontingent erschöpft)
2. Einmal `POST /de/organisationen` mit vollständigen Pflichtfeldern

**Erwartet:** eigenes Kontingent je Warteliste
**Tatsächlich:** **HTTP 429**, Meldung aus `flash.partner_rate_limited`

**Ort:** `PartnerController.php:33` und `OrganisationController.php:85` — beide
`#[Autowire(service: 'limiter.partner_waitlist')]`

**Folge:** Hinter einer geteilten IP — einer Gemeindeverwaltung, einem Coworking-Raum —
blockieren sich Interessenten gegenseitig, obwohl sie nichts miteinander zu tun haben.
Und ein Gemeindesekretär, der sich auf der Organisationsliste einträgt, verbraucht
Kontingent, das für Restaurants gedacht war.

Der Meldungstext selbst ist unauffällig („Sie haben in kurzer Zeit mehrere Anmeldungen
abgeschickt") — der Schlüsselname `flash.partner_rate_limited` ist irreführender als das,
was der Nutzer liest.

**Vorschlag:** Ein zweiter Limiter `organisation_waitlist` mit denselben Werten. Zwei
Zeilen in `framework.yaml`, eine im Controller, plus der `when@test`-Override.

## Hinweise ohne Fehlerstatus

- **FB-04 (kein `trusted_hosts`, Bestätigungslink aus dem Request-Host)** ist derselbe
  Befund wie B23/BF-29 und B01/FB-09 — dort bereits erfasst und als **Serveraufgabe**
  gekennzeichnet. Kein eigener Eintrag, sonst steht dieselbe Sache dreimal im Register.
- **FB-05 (keine Auskunftsfunktion)** — verwandt mit BF-37 und Feature `01`. Ein
  signierter Link deckt beides zusammen ab; getrennt gebaut wird es doppelt so teuer.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Drei in `tests/Functional/Controller/PartnerControllerTest.php`:
`testAk15InterneMeldungBleibtDeutschBeiFranzoesischerBestaetigung`,
`testAk15InterneMeldungTraegtDenInteressentenAlsReplyTo`,
`testAk23BeideWartelistenTeilenSichDenLimiter` (hält BF-38 fest; er fällt, sobald die
Limiter getrennt werden).

Die vorhandene Abdeckung war bereits die beste im Projekt — elf Tests, die AK-01 bis
AK-14 weitgehend abdecken. Ergänzt habe ich nur, was fehlte.

**Suite: 349 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B15`. B14 geht auf `approved`; die vier Befunde stehen in
`features/befunde.md`.

BF-37 ist der, den ich als nächstes bauen würde — nicht weil er technisch drängt,
sondern weil er zusammen mit BF-04 und FB-05 aus B14 dasselbe Loch beschreibt: **Es gibt
keinen Weg, gespeicherte Daten wieder loszuwerden.** Drei Features, ein Bauvorgang.

---

# Zweiter Durchlauf — 2026-09-11

Stand: 2026-09-11 · Vorstufe: `building` · Branch `fix/bf-119-email-validierung`

## Fazit

**Production-ready: ja** — die Reparatur zu BF-119 hält, nachgemessen am laufenden
Server. Offen bleiben **drei mittlere und zwei niedrige** Befunde, keiner davon
blockierend. 18 von 23 Kriterien bestanden, 1 durchgefallen, 2 nicht prüfbar, 3 nicht
mehr zutreffend.

Anlass des Durchlaufs war BF-119 (E-Mail-Adressen, die RFC 2822 verletzen). Der Befund
ist behoben und belegt: Am laufenden Server antwortet `/de/partner` auf
`../../etc/passwd@example.lu` mit **422 statt 500**, und der Bestand bleibt bei
**5 → 5 Zeilen** — vorher entstand eine Leiche. Auch die Kehrseite stimmt: Sechs
realistische Adressformen gehen weiterhin durch, `jean-luc@télécom.lu` sogar erstmals.

⚠ **Gefunden wurden dabei drei Dinge, die nichts mit BF-119 zu tun haben** — und eines
davon ist eine stille Regression, die seit dem 2026-09-02 unbemerkt läuft.

⚠ **Dieser Durchlauf prüft die Arbeit desselben Agenten, der sie gebaut hat.** Das ist
die Konstellation, vor der der Skill warnt. Gegengesteuert wurde mit dem
`code-reviewer`-Agenten auf den geänderten Dateien und damit, dass jeder Nachweis am
**laufenden Server** entstand, nicht am Quelltext.

## Akzeptanzkriterien im Einzelnen

### Anmeldung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `curl /de/partner` → **200**; `testLandingPageRendersWithSingleH1` |
| AK-02 | ✅ bestanden | `testValidSubmissionCreatesPendingEntryAndSendsMail`, `testUtmSourceIsStored`; DB: 5 Zeilen, alle `status=pending`, alle mit `consent_at` |
| AK-03 | ✅ bestanden | `testValidSubmissionCreatesPendingEntryAndSendsMail` |
| **AK-04** | ❌ **durchgefallen** | Mailpit gestoppt, dann abgeschickt: **HTTP 302**, kein `flash.partner_email_failed`. Eintrag entsteht (5 → 6), Nachricht liegt in `messenger_messages` (7). Die **erste** Hälfte des Kriteriums hält, die zweite nicht |
| AK-05 | ✅ bestanden | `testTurboRequestReturnsStream` |
| AK-06 | ✅ bestanden | `curl` ohne JavaScript, nur Platzhalter-Token + Same-Origin-Referer → **302** und Eintrag |
| AK-07 | ✅ bestanden | `curl` mit ungültigem Formular → **422**; `testInvalidSubmissionReturns422AndFocusesFirstError` |

### Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-08 | ✅ bestanden | `testHoneypotIsSilentlyDiscarded` |
| **AK-09** | ✅ bestanden | Am Grenzwert gemessen: POST 1–5 → **302**, POST 6, 7, 8 → **429** |
| AK-10 | ✅ bestanden | Drei GET vor den POST; der 429 kam trotzdem erst beim sechsten POST — GET verbraucht nichts |
| AK-18 | ✅ bestanden | Honeypot gefüllt → Antwort identisch zum Erfolgsfall, kein Validierungsfehler (AK-08) |
| AK-19 | ✅ bestanden | Markup: `type="text"` (nicht `hidden`), `tabindex="-1"`, Elternelement `aria-hidden="true"` + `class="absolute w-px h-px -left-[9999px]"` |

### Bestätigung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-11 | ✅ bestanden | `testConfirmationActivatesEntryAndNotifiesTeam` |
| AK-12 | ✅ bestanden | `testSecondConfirmationIsGracefulAndSendsNoSecondMail` |
| AK-13 | ✅ bestanden | `testUnknownTokenReturns404NotServerError`; `curl` mit erfundenem Token → **404**, kein 500 |
| AK-14 | ✅ bestanden | `testMalformedTokenDoesNotMatchRoute` |
| AK-15 | ✅ bestanden | `testAk15InterneMeldungBleibtDeutschBeiFranzoesischerBestaetigung`, `…TraegtDenInteressentenAlsReplyTo` |
| **AK-16** | ⚠️ nicht prüfbar | Setzt einen Transportfehler voraus, der den Controller erreicht. Seit `SendEmailMessage: async` kommt dort keiner mehr an — dieselbe Ursache wie AK-04. Im Test-Env (`sync`) wäre es prüfbar, das entspräche aber nicht der Produktion |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-17** | ⚠️ nicht prüfbar | `SHOW COLUMNS`: **keine IP-Spalte** — insoweit erfüllt. Die Liste des Kriteriums ist aber nicht mehr vollständig: `marketing_consent_at`, `self_confirmed_at` und `restaurant_id` kamen über Feature 04 und BF-89 dazu. Ein Kriterium, das abschließend aufzählt, lässt sich gegen einen erweiterten Bestand nicht mit „bestanden" beantworten |
| AK-20 | ✅ bestanden | DB: `COUNT(*) = 5`, `COUNT(consent_at) = 5` |

### Fragwürdiges Verhalten — **alle drei überholt**

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-21** | ❌ trifft nicht mehr zu | Die Spec sagt „keine Ablauffrist". Es gibt `TOKEN_LIFETIME_DAYS = 7` und `RESULT_EXPIRED` (BF-36, live seit `v2026.08.29`) |
| **AK-22** | ❌ trifft nicht mehr zu | Die Spec sagt „kein Widerrufsweg". Route `app_partner_revoke` existiert; am Server: **HTTP 200**, Zeilen **6 → 5** — der Widerruf **löscht** (BF-37, live seit `v2026.08.29`) |
| **AK-23** | ❌ trifft nicht mehr zu | Die Spec sagt „teilen sich ein Kontingent". `OrganisationController` nutzt `limiter.organisation_waitlist` (BF-38, live seit `v2026.08.29`) |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Rate Limit tatsächlich überrannt | ✅ greift | 5 × 302, dann 3 × **429** |
| GET verbraucht Kontingent | ✅ nein | drei GET ohne Wirkung auf den Zähler |
| Erfundener Widerrufs-Token | ✅ 404 | kein 500, keine Exception |
| Unbekannter Bestätigungs-Token | ✅ 404 | AK-13 |
| Bestätigungstoken im Log | ✅ kein Befund | steht im **`doctrine`**-Kanal; `when@prod` schließt `!doctrine` aus (BF-06/BF-12, dev bewusst offen) |
| IP-Adresse gespeichert | ✅ nein | `SHOW COLUMNS` ohne IP-Spalte |
| BF-119 am laufenden Server | ✅ behoben | 422 statt 500, Bestand **5 → 5** |

## Fehler

### BF-124 · Bei gescheiterter Zustellung bekommt der Nutzer keine Warnung mehr — mittel

**Betrifft:** AK-04, AK-16

⚠ **Das ist eine stille Regression, kein veraltetes Kriterium.** Der erste Durchlauf am
2026-08-24 hat AK-04 ausdrücklich als bestanden belegt: *„Bei gestopptem Transport blieb
der Eintrag gespeichert und der Nutzer bekam eine verständliche Meldung."* Damals lief
der Mailer synchron. Seit der Umstellung auf das Container-Image (2026-09-02) gilt
`SendEmailMessage: async`.

**Reproduktion:**
1. `docker stop endlech-mailer-1` — Zustellung unmöglich machen
2. `/de/partner` gültig abschicken

**Erwartet:** 302 und `flash.partner_email_failed`
**Tatsächlich:** **HTTP 302 ohne Warnung.** Der Eintrag entsteht (5 → 6), die Nachricht
liegt in `messenger_messages` (7 Stück). Der Interessent sieht eine reine Erfolgsmeldung.

**Ort:** `src/Controller/PartnerController.php:104` — `if (!$sent)` hängt am Rückgabewert
von `WaitlistConfirmationService::register()`, der nur `false` wird, wenn dort eine
`TransportExceptionInterface` gefangen wurde. Bei `async` wirft `MailerInterface::send()`
keine — es stellt nur in die Warteschlange.

⚠ **Die Ursache ist in `CLAUDE.md` bereits beschrieben** („Diese zwölf Blöcke sind seit
der Umstellung toter Code"), die **Folge für AK-04 hat niemand gezogen**. Genau deshalb
steht das hier als Befund und nicht als Hinweis.

**Bewertung:** Kein Datenverlust — die Anmeldung ist gespeichert, die Mail geht hinaus,
sobald der Worker läuft. Eine Warnung wäre heute sogar irreführend. **Der Befund ist
nicht der fehlende Flash, sondern dass zwei Kriterien seit neun Tagen etwas anderes
behaupten als der Code tut.**

**Vorschlag:** AK-04 und AK-16 an die Async-Wirklichkeit anpassen — die Zusage „der
Nutzer erfährt von einem Zustellproblem" ist nicht mehr haltbar und wurde durch
`app:messenger:watch` auf der Betreiberseite ersetzt. Alternativ den Flash an einen
Dispatch-Fehler hängen; das wäre aber eine neue Anforderung.

---

### BF-125 · Ein Prüflauf ist grün, weil er einen Kommentar liest — mittel

**Betrifft:** AK-23

**Reproduktion:**
1. `grep -n 'limiter\.' src/Controller/OrganisationController.php`
   → Zeile 40: `#[Autowire(service: 'limiter.organisation_waitlist')]` — **eigener Limiter**
   → Zeile 37: `// ⚠ BF-38: Eigener Zähler statt des geteilten 'limiter.partner_waitlist'.`
2. `php bin/phpunit --filter testAk23` → **grün**

**Erwartet:** Der Test müsste rot sein oder gar nicht mehr existieren — BF-38 ist behoben
und ging mit `v2026.08.29` hinaus. Sein eigener Kommentar sagt das:
*„Sobald die Organisationsliste einen eigenen Limiter hat, ist BF-38 behoben — dieser
Test darf dann fallen."*
**Tatsächlich:** Er bleibt grün, weil er den Quelltext als **String** durchsucht und der
gesuchte Ausdruck `limiter.partner_waitlist` im **Kommentar** von Zeile 37 steht.

**Ort:** `tests/Functional/Controller/PartnerControllerTest.php:250-261`

⚠ Der Test schützt nichts und behauptet zugleich das Gegenteil des tatsächlichen
Verhaltens. Er bliebe auch dann grün, wenn jemand den eigenen Limiter zurückbaut —
in **beide** Richtungen blind. Dasselbe Muster wie BF-64 (ein Test, der die Begrenzung
nie ausreizte) und BF-81 (ein Prüflauf, der nur die halbe Darstellung ansah).

**Vorschlag:** Test entfernen (BF-38 ist erledigt) oder auf den Container umstellen,
statt Quelltext zu lesen — `$container->get('limiter.organisation_waitlist')`.

---

### BF-126 · Die Spec von B14 beschreibt an fünf Stellen einen überholten Stand — mittel

**Betrifft:** AK-04, AK-17, AK-21, AK-22, AK-23

Die Rekonstruktion ist vom 2026-08-24. Seither sind BF-36, BF-37, BF-38 behoben und mit
`v2026.08.29` ausgeliefert, Feature 04 hat Spalten ergänzt, und der Mailer läuft
asynchron. **Fünf Kriterien behaupten den alten Zustand.**

| AK | Spec behauptet | Nachgemessen |
|---|---|---|
| AK-04 | Nutzer sieht `flash.partner_email_failed` | HTTP 302 ohne Warnung (BF-124) |
| AK-17 | Liste der erfassten Daten ist abschließend | drei Spalten mehr: `marketing_consent_at`, `self_confirmed_at`, `restaurant_id` |
| AK-21 | „es gibt keine Ablauffrist" | `TOKEN_LIFETIME_DAYS = 7`, `RESULT_EXPIRED` |
| AK-22 | „es gibt keinen Widerrufsweg" | `/de/partner/abmelden/{token}` → 200, Zeile gelöscht |
| AK-23 | „beides zählt auf dasselbe Kontingent" | eigener `limiter.organisation_waitlist` |

⚠ **Warum das zählt:** Drei dieser Kriterien tragen ein ⚠ und waren als *fragwürdiges
Verhalten zur Klärung* aufgenommen. Sie sind geklärt — repariert und live. Wer die Spec
heute liest, hält drei behobene DSGVO-relevante Mängel für offen und einen erfassten
Datenbestand für kleiner, als er ist. Das ist genau die Falle, vor der `CLAUDE.md` bei
`B`-Features warnt: *„Die spec.md eines B-Features ist eine Rekonstruktion und kann selbst
falsch sein."*

**Vorschlag:** AK-21 bis AK-23 in erfüllte Kriterien überführen (mit Verweis auf
BF-36/37/38), AK-17 um die drei Spalten ergänzen, AK-04 nach BF-124 entscheiden.
Zuständig ist `sdd-erfassen` bzw. eine Spec-Fortschreibung — **nicht dieser Skill**.

---

### BF-127 · Zwei von drei Angriffsadressen sichern nichts ab — niedrig

**Betrifft:** die Absicherung von BF-119 (kein Akzeptanzkriterium)

Gefunden vom `code-reviewer`, **nachgemessen und bestätigt**. Der Docblock von
`rfcWidrigeAdressen()` behauptet, alle drei Werte seien Adressen, „die der HTML5-Default
durchließ und `Mime\Address` ablehnt". Gegen `PATTERN_HTML5` geprüft:

| Adresse | HTML5-Default | Wirkung |
|---|---|---|
| `../../etc/passwd@example.lu` | **akzeptiert** | belegt BF-119 |
| `a"b(c)d@example.lu` | abgelehnt | sichert nichts ab |
| `jemand@@example.lu` | abgelehnt | sichert nichts ab |

**Unabhängig bestätigt durch die Rückbau-Gegenprobe beim Bauen:** Mit entferntem STRICT
wurde **1 von 3** Datensätzen rot, nicht 3 von 3. Das war sichtbar und wurde als
Beobachtung notiert, aber nicht als Mangel gewertet — der Reviewer hat daraus den Befund
gemacht.

⚠ Der Test bleibt fachlich richtig: Er belegt, dass STRICT diese Adressen abweist und
keine Zeile entsteht. Er **behauptet aber mehr, als er zeigt** — gegen ein versehentliches
Zurückdrehen auf den HTML5-Default schützt nur ein Drittel seiner Fälle.

**Ort:** `tests/Functional/Controller/Bf119EmailValidierungTest.php:36-41` (Docblock
Z. 16-27)
**Vorschlag:** Docblock präzisieren und zwei weitere Local-Part-Varianten ergänzen, die
`.` oder `/` enthalten und dadurch tatsächlich durch HTML5 rutschen.

---

### BF-128 · `CLAUDE.md` behauptet weiterhin, BF-119 sei für B14/B15/B01 offen — niedrig

**Betrifft:** Projektdokumentation

**Reproduktion:** `sed -n '751,752p' CLAUDE.md`
**Tatsächlich:** *„⚠️ **B14, B15 und B01 nutzen weiterhin den Default** und haben
denselben Fehler (nachgestellt am Partner-Formular); BF-119 steht dafür offen."*

Genau diese drei Formulare sind der Inhalt des Branches. Der Satz ist eine
⚠-Warnung — also die Textsorte, auf die sich in diesem Projekt jeder verlässt — und
führt ab dem Merge in die Irre.

⚠ Der Reviewer nannte zusätzlich `features/befunde.md:149`. **Dort trifft es nicht mehr
zu**: Die Zeile wurde während seines Laufs auf „behoben 2026-09-11" fortgeschrieben.
Geprüft, verworfen, hier vermerkt — damit niemand zweimal danach sucht.

**Vorschlag:** Beim Merge mitziehen. `sdd-qa` schreibt `befunde.md` fort, aber nicht
`CLAUDE.md`.

## Neue Prüfläufe dieses Durchlaufs

Keine. Die Absicherung entstand beim Bauen (`Bf119EmailValidierungTest`,
`Bf119RegisterReihenfolgeTest`); dieser Durchlauf hat sie **gegengeprüft** statt ergänzt —
durch Rückbau beider Reparaturen und durch Messung am laufenden Server. Der einzige
offene Testbedarf steht als BF-127.

## Nächster Schritt

**`/sdd-deploy B14`** — mit dem ausdrücklichen Hinweis, dass hier **tatsächlich
ausgeliefert wird** und nicht nur ein Status gesetzt: Anders als im Normalfall eines
Bestandsfeatures liegt eine echte Reparatur vor (BF-119, Grad *hoch*), die auf Produktion
noch nicht wirkt. Bis zum Ausrollen erzeugt eine RFC-widrige Adresse dort weiterhin einen
500er samt bleibender Zeile.

⚠ Vor dem Merge: **BF-128** (`CLAUDE.md`) mitziehen. BF-124 bis BF-127 blockieren nicht.
