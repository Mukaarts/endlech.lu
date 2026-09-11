# B01 · Registrierung & E-Mail-Bestätigung — Testbericht

Stand: 2026-08-23 · Geprüft gegen `spec.md` vom 2026-08-23 (Rückerfassung)

> **Dieser Bericht hat drei Durchläufe.** Der erste fand 8 Befunde, `/sdd-build B01`
> behob 6 davon, der zweite bestätigte die Reparatur und fand einen neuen. Der
> **dritte Durchlauf** steht direkt hier darunter und ist der maßgebliche Stand;
> die beiden früheren bleiben zur Nachvollziehbarkeit erhalten.
Umgebung: lokal, `symfony server` auf `:8000`, MySQL 8.0 in Docker, Mailpit als SMTP-Senke

## Fazit — dritter Durchlauf (2026-08-23)

**Production-ready: ja**

Anlass war keine Codeänderung, sondern zwei Ereignisse: BF-04 (Betroffenenrechte) wurde
aus B01 herausgelöst und läuft als reguläres Feature `01` durch die Kette, und die
Reparatur wurde committet. Beides musste geprüft werden — das Erste, weil es die
Bewertung verschiebt, das Zweite, weil beim Committen selektiv `git add` benutzt wurde
und etwas hätte fehlen können.

**Der committete Stand entspricht dem geprüften.** Alle sieben reparierten Dateien sind
gegen `HEAD` diff-frei, und die fünf Kernänderungen sind in `HEAD` nachweisbar
(`git show HEAD:… | grep`). Im Arbeitsverzeichnis bleibt nur `public/build` — ein
Dev-Build-Artefakt mit unhashten Dateinamen, das nicht zur Reparatur gehört.

**Alle 20 Kriterien wurden erneut ausgeführt**, nicht aus dem zweiten Durchlauf
übernommen. Ergebnis unverändert: 17 bestanden, 3 durchgefallen. Ebenso die fünf Edge
Cases und der vollständige Angriffsdurchlauf.

**Damit sind für B01 nur noch Befunde mit Grad *mittel* offen** — BF-09 (Enumeration)
und BF-11 (Kontingent bei Tippfehlern). Nach den Regeln der Kette blockiert das eine
Auslieferung nicht. Die drei durchgefallenen Kriterien haben keinen offenen Befund mehr
hinter sich: AK-13 und AK-17 sind als bewusste Entscheidungen unter *Akzeptiert*
verbucht, AK-14 entspricht BF-09.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 20 von 20 |
| davon bestanden | 17 |
| davon durchgefallen | 3 (AK-13, AK-14, AK-17) |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 5 von 5 |
| Tests | 317 grün, 0 übersprungen |
| Offene Befunde | 2, beide *mittel* |

### Nachweise dieses Durchlaufs

| Prüfung | Beleg |
|---|---|
| AK-01…AK-08 | Formular 200; angemeldet `302 → /de/`; Name 1 Zeichen 422; Passwort 7 → 422, **Grenzwert 8 → 302**; ungleich → 422 **mit lesbarer Meldung**; gültig → 302, DB `is_verified=0`, Token 64, `$2y$13$`; danach `/de/profile` → 302 |
| AK-09…AK-12 | Bestätigen `302 → /de/login`, DB `is_verified=1`, Token `NULL`; zweiter Aufruf `302 → /de/`; unbekannter Token `302 → /de/`; abgelaufen `302 → /de/verify`, bleibt 0; Mailpit gestoppt → 302 + Warnung, Konto gespeichert |
| AK-15 | Rettungsweg vollständig: abgelaufen → resend → neue Mail → `is_verified=1` |
| AK-13, AK-14 | unverändert reproduziert: Login gelingt, `/de/profile` 200; `/fr/register` zeigt „déjà utilisée", die Auskunft selbst bleibt |
| AK-16…AK-20 | 11 Spalten; Klartextpasswort 0 Treffer, Token im dev-Log 1; `prod`: `channels: exclusive [deprecation, doctrine]`; 3 Token à 64 Hex, verschieden, Präfix 0; Payload ohne Passwort und Hash, Inhalt in der Sprache der Registrierung |
| EC-01…EC-05 | `Duplicate entry`; `router:match` → `app_verify_resend`; Ablauf `NULL` → nicht verifiziert; 5000 → 422 / 4096 → 302; Konto bleibt bei Versandfehler |
| Angriff 1/2 | `/de/register` 200/302, `/de/verify` 200, `/de/verify/resend` anonym 302 |
| Angriff 3 | Registrierung `302×5 429 429`; resend 5 Aufrufe → **3 Mails**; kein Kontingentverbrauch durch GET |
| Angriff 5 | Mail-Payload ohne Passwort, ohne Hash |
| Angriff 6 | 0 getrackte Geheimnisse |
| Angriff 7 | XSS und SQL-Einschleusung → 302, Tabelle intakt (18 Konten) |
| Angriff 8 | weiterhin 0 Routen für Löschung/Export — jetzt Feature `01` |
| Verifikation | `lint:yaml` 41 OK, `lint:twig` 77 OK, `prod`-Container baubar, 317 Tests / 1094 Assertions |

### Was das „ja" nicht bedeutet

- **Zwei Befunde bleiben offen.** BF-09: Das Registrierformular verrät weiterhin, ob
  eine Adresse existiert — die Anti-Enumeration der API läuft dadurch ins Leere. BF-11:
  Fünf Tippfehler sperren einen Nutzer eine Stunde aus, ohne dass ein Konto entsteht.
  Beide gehören in einen nächsten Reparaturlauf, nicht in den Papierkorb.
- **AK-17 ist formal durchgefallen.** In `prod` ist der Weg geschlossen, im `dev`-Log
  steht der Token bewusst weiter (BF-12, akzeptiert). Der Laufzeitnachweis für `prod`
  konnte im zweiten Durchlauf nicht erbracht werden und wurde hier nicht erneut versucht
  — belegt ist die Konfiguration, nicht das Verhalten unter Last.
- **Die Reparatur ist nicht ausgeliefert.** Sie liegt committet auf
  `fix/b01-registrierung-qa`. Für Nutzer ist die Sackgasse weiterhin offen, bis das
  gemerged ist.

---

# Zweiter Durchlauf (2026-08-23)

## Fazit — zweiter Durchlauf

**Production-ready: nein**

Die Reparatur hält, was sie zusagt: **AK-05 und AK-15 sind jetzt bestanden**, die
Sackgasse ist geschlossen (abgelaufener Link → neue Mail anfordern → bestätigen läuft
durch), und die Registrierung ist gedrosselt. Von 20 Kriterien bestehen jetzt **17
statt 15**. Alle sechs Behebungen wurden einzeln nachvollzogen, keine davon nur
konfigurativ geglaubt.

**Ein neuer Befund kam durch die Reparatur hinzu** (BF-11, mittel): Der Limiter
verbraucht Kontingent auch bei **ungültigen** Formularen. Fünf Tippfehler sperren einen
Nutzer eine Stunde aus, ohne dass je ein Konto oder eine Mail entstand — belegt. Auf
einer Plattform für Menschen mit Behinderungen wiegt das schwerer als anderswo. Die
Implementierung folgt dabei exakt dem bestehenden `PartnerController`, das Muster ist
also älter als die Reparatur.

Offen bleiben zwei bewusst zurückgestellte Punkte: AK-13 (kein `user_checker` — vom
Betreiber entschieden, jetzt unter *Akzeptiert*) und AK-14/BF-09 (Enumeration, hängt an
einem Passwort-Vergessen-Weg). AK-17 ist weiterhin durchgefallen: In `prod` ist der Weg
geschlossen, in `dev` steht der Token bewusst weiter im Log — und den Laufzeitnachweis
für `prod` konnte ich nicht erbringen.

**Der nächste Schritt ist die Auslieferung.** Die sechs Behebungen liegen auf einem
Branch und wirken für Nutzer erst danach — darunter zwei mit Grad *hoch* an Code, der
gerade läuft.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 20 von 20 |
| davon bestanden | **17** (erster Durchlauf: 15) |
| davon durchgefallen | 3 (AK-13, AK-14, AK-17) |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 5 von 5 |
| Tests | 317 grün, 0 übersprungen (vorher 315 mit 2 Skips) |
| Neue Befunde | 1 (BF-11, mittel) |

## Akzeptanzkriterien — zweiter Durchlauf

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `GET /de/register` → 200, vier Felder im Markup |
| AK-02 | ✅ bestanden | angemeldet → `302 → /de/` |
| AK-03 | ✅ bestanden | Name „A" → 422 |
| AK-04 | ✅ bestanden | 7 Zeichen → 422; **Grenzwert** 8 → 302 |
| AK-05 | ✅ **jetzt bestanden** | 422; kein roher Schlüssel mehr — „Die Passwörter stimmen nicht überein." (de) und „ne correspondent pas" (fr) |
| AK-06 | ✅ bestanden | 302 → `/de/verify`; DB: `is_verified=0`, Token 64, `$2y$13$`, Ablauf +24 h |
| AK-07 | ✅ bestanden | `pw_prefix = $2y$13$` |
| AK-08 | ✅ bestanden | `/de/profile` → 302 auf Login |
| AK-09 | ✅ bestanden | Link → `302 → /de/login`; DB: `is_verified=1`, Token `NULL` |
| AK-10 | ✅ bestanden | Ablauf 2020 → `302 → /de/verify`, Flash „…abgelaufen. Bitte fordere einen neuen an.", `is_verified` bleibt 0 |
| AK-11 | ✅ bestanden | 64×`a` → `302 → /de/` |
| AK-12 | ✅ bestanden | Mailpit gestoppt: 302, Warnung, Konto **gespeichert** |
| AK-13 | ❌ durchgefallen | unverändert reproduziert: unbestätigtes Konto meldet sich an, `/de/profile` → **200**. **Bewusst so belassen** (Betreiberentscheidung, OF-01) → BF-03, jetzt unter *Akzeptiert* |
| AK-14 | ❌ durchgefallen | Die Meldung folgt jetzt der Sprache („Cette adresse e-mail est déjà utilisée."), **die Auskunft bleibt**: ein Angreifer erfährt weiterhin, ob eine Adresse registriert ist → BF-09 |
| AK-15 | ✅ **jetzt bestanden** | `router:match /de/verify/resend` → `app_verify_resend`; kompletter Rettungsweg durchlaufen: abgelaufen → resend → neue Mail → `is_verified=1` |
| AK-16 | ✅ bestanden | 11 Spalten, unverändert |
| AK-17 | ❌ durchgefallen | Klartextpasswort **0 Treffer** ✓; Bestätigungstoken im `dev`-Log **1 Treffer**. Für `prod` ist `channels: exclusive [deprecation, doctrine]` belegt (`debug:config`), der **Laufzeitnachweis gelang nicht** — siehe Einschränkungen |
| AK-18 | ✅ bestanden | 4 frische Token: alle 64 Zeichen `[a-f0-9]`, verschieden, gemeinsames Präfix 0 |
| AK-19 | ✅ bestanden | zweiter Aufruf → `302 → /de/` |
| AK-20 | ✅ bestanden | Payload: Empfänger, Name, Link. Kein Passwort, kein Hash. Inhalt **in der Sprache der Registrierung** |

## Regressionsprüfung — B04 ist über zwei Wege mitbetroffen

Der Abschlussbericht des Bauvorgangs nannte beide; beide geprüft:

| Weg | Ergebnis | Nachweis |
|---|---|---|
| `form.password_mismatch` in `ChangePasswordType` | ✅ korrekt mitrepariert | Passwortwechsel mit ungleichen Feldern → 422, lesbare Meldung, kein roher Schlüssel |
| `user.email_unique` in `ProfileType` | ✅ korrekt mitrepariert | E-Mail auf `admin@endlech.lu` ändern → 422, „wird bereits verwendet", kein deutscher Klartext |
| Konto unbeschädigt | ✅ | `user@endlech.lu` unverändert in der Datenbank |

## Sicherheitsprüfung — zweiter Durchlauf

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | kein Objektzugriff über ID; fremder Token → 302, keine Daten |
| Zugriffsregeln serverseitig | bestanden | `/de/register` 200/302, `/de/verify` 200, `/de/verify/resend` anonym 302 |
| Rate Limit greift | bestanden | Registrierung: `302 302 302 302 302 429 429`; resend: 5 Aufrufe → **3 Mails**; API unverändert `400×5 429 429` |
| PII in Logs | **BF-06 offen für dev** | Klartextpasswort 0; Token im `dev`-Log 1 |
| PII an externe Dienste | bestanden | Mailpit-Payload geprüft: Empfänger, Name, Link — sonst nichts |
| Geheimnisse im Repository | bestanden | `.env.local` nicht getrackt; **0** neue Secret-Zeilen im Diff gegen `dev` |
| Eingaben | bestanden | XSS und SQL-Einschleusung → 302, Tabelle intakt (18 Konten), Ausgabe escaped |
| Löschen / Betroffenenrechte | **BF-04 offen** | unverändert: 0 Routen für Löschung, Export, Passwort-Reset |

## Neuer Fehler

### BUG-09 · Rate Limit verbraucht Kontingent auch bei ungültigen Formularen — mittel

**Betrifft:** die Reparatur von BUG-03; neu in diesem Durchlauf
**Reproduktion:**
1. Limiter-Speicher leeren
2. Fünfmal `/de/register` mit einem zu kurzen Passwort absenden (jedes Mal 422)
3. Ein sechstes Mal absenden — auch mit gültigen Daten
**Erwartet:** Ein Nutzer, der sich vertippt, bleibt handlungsfähig; gedeckelt wird, was
Konten anlegt und Mails verschickt
**Tatsächlich:** `422 422 422 422 422` → **429**. Angelegte Konten: **0**. Versandte
Mails: **0**. Der Nutzer ist eine Stunde ausgesperrt, ohne je etwas ausgelöst zu haben.
Zweite Beobachtung: Auch ein **Transportfehler** verbraucht Kontingent — dort entsteht
zwar ein Konto, aber keine Mail.
**Ort:** `src/Controller/RegistrationController.php:47-57` — `consume(1)` steht in
`if ($form->isSubmitted())`, also vor `isValid()`.
**Einordnung:** Die Implementierung folgt exakt dem bestehenden
`PartnerController::submit()` (Zeile 53), der ebenfalls vor der Gültigkeitsprüfung
konsumiert. Das Muster ist älter als diese Reparatur und betrifft B14/B15 gleichermaßen.
Auf einer Plattform, die sich ausdrücklich an Menschen mit Behinderungen richtet, wiegt
eine Sperre nach fünf Tippfehlern schwerer als anderswo.
**Vorschlag:** `consume(1)` in den `isValid()`-Zweig verschieben; wenn ein Schutz gegen
reines Formular-Fluten gewünscht ist, dafür ein zweites, großzügigeres Limit.

## Hinweise ohne Fehlerstatus

- **Die resend-Sperre antwortet mit 302, nicht mit 429.** Vom Bauvorgang als Annahme
  gemeldet und hier bestätigt: fünf Aufrufe, alle 302, aber nur drei Mails. Für den
  Nutzer stimmt es (Flash-Meldung), für ein Monitoring ist die Sperre unsichtbar.
- **Die 429-Antwort der Registrierung trägt keinen `Retry-After`-Header.** Der
  `ApiRateLimitSubscriber` setzt ihn für die API; hier fehlt er.
- **`doctrine:schema:validate` bleibt rot** — vier `RENAME INDEX`-Anweisungen aus
  Altlasten, unverändert und nicht von dieser Reparatur verursacht (im Bauvorgang per
  Stash-Vergleich gegen `dev` belegt).
- **Kein automatisierter Test für die Rate Limits.** Vom Bauvorgang gemeldet und hier
  bestätigt: Der Limiter ist per `#[Autowire]` fest im Controller-Service verdrahtet,
  `getContainer()->set()` erreicht ihn nicht. Beide Limits sind nur manuell belegt.

## Einschränkungen dieses Durchlaufs

- **AK-17, `prod`-Teil: Laufzeitnachweis nicht erbracht.** Drei Versuche, den
  `fingers_crossed`-Puffer in einer echten `prod`-Umgebung zu leeren, scheiterten am
  Bootstrap (`test.service_container`, fehlendes `SENTRY_DSN`, zuletzt: der
  `logger`-Service ist in `prod` inlined und nicht abrufbar). Belegt ist damit die
  **Konfiguration** (`debug:config monolog handlers.main --env=prod` →
  `channels: exclusive [deprecation, doctrine]`), nicht das Laufzeitverhalten. Wer es
  abschließend wissen will, braucht einen echten Fehler auf der Produktivumgebung und
  einen Blick ins Hoster-Log.
- Geprüft wurde auf dem Branch `fix/b01-registrierung-qa`, nicht auf `dev` und nicht auf
  der Produktivumgebung.

---

# Erster Durchlauf (2026-08-23, vor der Reparatur)

## Fazit des ersten Durchlaufs

**Production-ready: nein**

Der Hauptweg funktioniert: Registrieren, Konto anlegen, Mail versenden, Link einlösen —
alles belegt und mit Nachweis abgehakt. Vier von fünf durchgefallenen Kriterien betreffen
Wege **daneben**, und genau dort liegen die Fehler, die einen Nutzer aussperren können.

Der schwerste Fund ist eine Sackgasse: Wessen Bestätigungslink abläuft, bekommt die
Aufforderung „Bitte fordere einen neuen an." — und genau dieser Weg ist unerreichbar
(BUG-01). Das Konto ist damit dauerhaft unbestätigt und, weil es kein Passwort-Zurücksetzen
und keinen Löschweg gibt, auch nicht zu retten. Dazu kommt eine ungedrosselte
Registrierung (BUG-03): zwölf Konten und zwölf Mails in Folge, ohne jede Sperre — während
dieselbe Anwendung die API-Anmeldung ab dem sechsten Versuch blockt.

Nächster Schritt: `/sdd-build B01` mit BUG-01 bis BUG-08.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 20 von 20 |
| davon bestanden | 15 |
| davon durchgefallen | 5 |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 5 von 5 |
| Tests neu geschrieben | 10 (2 davon übersprungen bis zur Reparatur) |
| Tests grün | 315 von 315 (2 übersprungen) |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `GET /de/register` → 200; Felder `registration[name\|email\|plainPassword][first\|second]` im Markup |
| AK-02 | ✅ bestanden | angemeldet → `302 → http://localhost:8000/de/`; Test `testAk02AngemeldeterWirdVonDerRegistrierseiteWeggeleitet` |
| AK-03 | ✅ bestanden | Name „A" → **422**, Meldung „Der Name muss mindestens 2 Zeichen lang sein." |
| AK-04 | ✅ bestanden | Passwort 7 Zeichen → **422**; **Grenzwert** 8 Zeichen → **302** |
| AK-05 | ❌ durchgefallen | 422 und kein Mailversand stimmen (Test `testAk05UngleichePasswoerterWerdenAbgewiesen`), aber angezeigt wird der rohe Schlüssel `form.password_mismatch` → **BUG-02** |
| AK-06 | ✅ bestanden | `302 → /de/verify`; DB: `is_verified=0`, `tok_len=64`, `expires_at` = +24 h; Flash „Registrierung erfolgreich! …" |
| AK-07 | ✅ bestanden | DB: `pw_prefix = $2y$13$` (bcrypt), kein Klartext |
| AK-08 | ✅ bestanden | nach der Registrierung `GET /de/profile` → `302 → /de/login` |
| AK-09 | ✅ bestanden | Link aus der Mail → `302 → /de/login`; DB danach `is_verified=1`, Token und Ablauf `NULL`; Flash „…erfolgreich bestätigt!"; Test `testAk09GueltigerTokenVerifiziertUndLeertDenToken` |
| AK-10 | ✅ bestanden | Ablauf auf 2020-01-01 → `302 → /de/verify`, `is_verified` bleibt 0; Test `testAk10AbgelaufenerTokenVerifiziertNicht` |
| AK-11 | ✅ bestanden | Token aus 64 Nullen → `302 → /de/`, Flash „Ungültiger Bestätigungslink."; Test `testAk11UnbekannterTokenLeitetAufDieStartseite` |
| AK-12 | ✅ bestanden | Mailpit gestoppt, synchroner Versand: `302`, Flash „Registrierung erfolgreich, aber die Bestätigungs-E-Mail konnte nicht gesendet werden."; DB: Konto **gespeichert** (`gerda@qa.example`, tok 64) |
| AK-13 | ❌ durchgefallen | reproduziert: unbestätigtes Konto meldet sich an (`302 → /de/`), `GET /de/profile` → **200**. Verhalten wie in der Spec beschrieben, aber es ist eine Lücke → **BUG-05** |
| AK-14 | ❌ durchgefallen | reproduziert: „Diese E-Mail-Adresse ist bereits registriert." — auch auf `/fr/register` **auf Deutsch** → **BUG-06** |
| AK-15 | ❌ durchgefallen | `router:match /de/verify/resend` → `app_verify_email`; Aufruf als angemeldeter, unbestätigter Nutzer → `302 → /de/`, Flash „Ungültiger Bestätigungslink.", **Mailzähler unverändert bei 2** → **BUG-01** |
| AK-16 | ✅ bestanden | Spalten von `user`: `name`, `email`, `password`, `is_verified`, `verification_token`, `verification_token_expires_at`, `created_at` (+ `avatar_filename`, `webauthn_handle` aus B03/B04). Keine besonderen Kategorien nach Art. 9 |
| AK-17 | ❌ durchgefallen | Klartextpasswort: **0 Treffer** in `var/log/dev.log` ✓ — aber der Bestätigungstoken steht drin (1 Treffer, `doctrine.DEBUG: Executing statement: INSERT INTO user …`) → **BUG-04** |
| AK-18 | ✅ bestanden | 5 Token verglichen: alle 64 Zeichen, nur `[a-f0-9]`, alle verschieden, gemeinsames Präfix 0 Zeichen |
| AK-19 | ✅ bestanden | derselbe Link zweimal: erst `302 → /de/login`, dann `302 → /de/`; Test `testAk19EingeloesterTokenGreiftKeinZweitesMal` |
| AK-20 | ✅ bestanden | Mailpit-Payload: `From noreply@endlech.lu`, `To anna@qa.example`, Betreff, Inhalt mit Name und Bestätigungs-URL. Nichts darüber hinaus. ⚠ Sprachfehler siehe BUG-07 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ belegt | direkter Doppel-`INSERT` auf `anna@qa.example` → `Duplicate entry` (UNIQUE greift auf DB-Ebene) |
| EC-02 | ✅ belegt | nicht auslösbar — `resend()` ist unerreichbar (BUG-01). Das **ist** der Nachweis |
| EC-03 | ✅ belegt | `expires_at = NULL` → `302 → /de/verify`, `is_verified` bleibt 0; Test `testEc03TokenOhneAblaufzeitpunktGiltAlsAbgelaufen` |
| EC-04 | ✅ belegt | Passwort 5000 Zeichen → **422**; exakt 4096 → **302** |
| EC-05 | ✅ belegt | über AK-12: Konto bleibt gespeichert, ist aber ohne `resend`-Weg nie bestätigbar |

## Sicherheitsprüfung

Aktiv angegriffen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | B01 adressiert kein Objekt über eine ID. `/verify/{token}` ist tokenbasiert; der Token ist das Geheimnis und wird beim Einlösen geleert (AK-19) |
| Zugriffsregeln serverseitig | bestanden | `/de/register` anonym 200 / angemeldet 302; `/de/verify` 200; `access_control` `^/[a-z]{2}/(register\|verify)` = `PUBLIC_ACCESS` |
| Rate Limit greift | **BUG-03** | 12 Registrierungen in Folge: `302` ×12, **12 Konten**, **12 Mails**. Gegenprobe API: `POST /api/v1/auth/login` → 401,401,401,401,401,**429**,429,429 |
| PII in Logs | **BUG-04** | `grep "GutesPasswort1" var/log/dev.log` → **0** ✓; Bestätigungstoken → **1 Treffer** in einer `doctrine.DEBUG`-Zeile |
| PII an externe Dienste | bestanden | tatsächlicher Payload aus Mailpit geprüft: Empfänger, Anzeigename, Bestätigungs-URL. Kein Passwort, kein Hash, keine weiteren Felder |
| Geheimnisse im Repository | bestanden | `.env.local` nicht getrackt, **0** Commits; `config/jwt/` nicht getrackt. Hinweis: `APP_SECRET` in `.env.dev` ist der dokumentierte dev-Wert — er ist mit dem lokalen `.env.local` identisch (siehe Hinweise) |
| Eingaben | bestanden | `<script>alert(1)</script>` wird gespeichert, aber als `&lt;script&gt;` ausgegeben (Twig-Autoescaping); `'; DROP TABLE user; --` als Literal, Tabelle intakt (27 Konten); Emoji ✓; 10.000 Zeichen → 422 |
| Löschen / Betroffenenrechte | **BUG-08** | `debug:router` → **0** Routen für Kontolöschung, **0** für Datenexport, **0** für Passwort-Zurücksetzen |

## Fehler

### BUG-01 · „Bestätigungsmail erneut senden" ist unerreichbar — hoch

**Betrifft:** AK-15, EC-02, FB-02
**Reproduktion:**
1. `php bin/console router:match /de/verify/resend` → meldet `app_verify_email`
2. Konto registrieren, nicht bestätigen, anmelden
3. `/de/verify` öffnen, „Bestätigungsmail erneut senden" folgen
**Erwartet:** neue Mail, Weiterleitung auf `/de/verify` mit Bestätigung
**Tatsächlich:** `302 → /de/`, Flash „Ungültiger Bestätigungslink.", Mailzähler unverändert
**Ort:** `src/Controller/EmailVerificationController.php:35` (`/verify/{token}`) steht vor
Zeile 59 (`/verify/resend`); Symfony wertet in Deklarationsreihenfolge aus und `{token}`
hat kein Requirement. Der Link in `templates/email_verification/notice.html.twig:32`
führt damit ins Leere.
**Verschärfung:** AK-10 zeigt bei abgelaufenem Token die Meldung „Der Bestätigungslink
ist abgelaufen. **Bitte fordere einen neuen an.**" — der einzige Weg dafür ist dieser.
Zusammen mit dem fehlenden Passwort-Zurücksetzen (FB-05) und dem fehlenden Löschweg
(BUG-08) ist das Konto danach nicht mehr zu retten.
**Vorschlag:** `resend()` vor `verify()` deklarieren **oder** `requirements: ['token' => '[a-f0-9]{64}']`
setzen — und gleichzeitig BUG-03 beheben, sonst öffnet die Reparatur einen ungedrosselten
Mailversandweg.


**BEHOBEN am 2026-08-23** (Branch `fix/b01-registrierung-qa`): `app_verify_email` trägt jetzt
`requirements: ['token' => '[a-f0-9]{64}']`. Nachweis: `router:match /de/verify/resend` →
`app_verify_resend`; der Weg Hinweisseite → Link → neue Mail → Token einlösen wurde
vollständig durchlaufen (`is_verified` danach 1). Regressionstest
`testAk15ErneutSendenIstErreichbar` ist aktiv.

### BUG-03 · Registrierung ohne Rate Limit — hoch

**Betrifft:** FB-01, Sicherheitskatalog Abschnitt 4
**Reproduktion:** `/de/register` zwölfmal in Folge mit verschiedenen Adressen absenden
**Erwartet:** Sperre nach wenigen Versuchen (Katalog: 5 in 15 Minuten)
**Tatsächlich:** 12 × `302`, 12 Konten angelegt, 12 Mails versandt — keine Sperre
**Ort:** `config/packages/framework.yaml` definiert `api_anonymous`, `api_login`,
`partner_waitlist`; keiner greift auf `/{locale}/register`, und
`RegistrationController` bezieht keinen Limiter.
**Folge:** unbegrenzte Konto-Anlage; jede Anlage verbraucht Kontingent der
Brevo-Quota des Betreibers. Über `resend` (nach BUG-01) wäre zusätzlich ein fremdes
Postfach befüllbar.
**Vorschlag:** Limiter analog `partner_waitlist` einführen und im Controller beziehen.


**BEHOBEN am 2026-08-23**: Limiter `registration` (5/Stunde je IP) in `framework.yaml`,
bezogen im Controller nach `handleRequest` und nur für abgeschickte Formulare.
Nachweis: 12 Versuche → 5×302, dann 7×429; genau 5 Konten und 5 Mails; drei GET-Aufrufe
verbrauchten kein Kontingent. Zusätzlich `verify_resend` (3/Stunde), Nachweis im Selbsttest:
5 Aufrufe → 3 Mails.

### BUG-05 · Unbestätigte Konten haben vollen Zugang — hoch

**Betrifft:** AK-13, FB-03
**Reproduktion:** Konto registrieren, **nicht** bestätigen, unter `/de/login` anmelden
**Erwartet:** Anmeldung abgewiesen oder Zugang eingeschränkt
**Tatsächlich:** `302 → /de/`, `GET /de/profile` → **200**. Nur `/de/community/suggest`
leitet auf `/de/verify` um
**Ort:** `config/packages/security.yaml` konfiguriert keinen `user_checker`;
`App\Entity\User` implementiert kein `isEnabled()`. Die einzige Prüfung auf
`isVerified()` außerhalb von B01 steht in `src/Controller/CommunityController.php:29`.
**Folge:** Die E-Mail-Bestätigung ist bis auf den Vorschlags-Wizard folgenlos — eine
nicht existierende Adresse reicht für ein voll nutzbares Konto.
**Vorschlag:** `user_checker` ergänzen. ⚠ **Nicht vor BUG-01**: Solange `resend`
unerreichbar ist, sperrt das alle bestehenden unbestätigten Konten aus. Offene
Entscheidung OF-01 der Spec.


**ZURÜCKGESTELLT am 2026-08-23** — Produktentscheidung des Betreibers, nicht gebaut.
Ein `user_checker` sperrt bestehende unbestätigte Konten im Moment des Deployments aus;
wie viele das auf Produktion sind, ist von hier nicht einsehbar. Die Voraussetzung dafür
ist mit BUG-01 jetzt geschaffen (es gibt wieder einen Weg, eine neue Mail anzufordern).
Bleibt als BF-03 in `features/befunde.md` offen. Entspricht OF-01 der Spec.

### BUG-08 · Betroffenenrechte nicht bedienbar — hoch

**Betrifft:** FB-04, FB-05, FB-06; Sicherheitskatalog Abschnitt 5
**Reproduktion:** `php bin/console debug:router` nach Routen für Kontolöschung,
Datenexport und Passwort-Zurücksetzen durchsuchen
**Erwartet:** je ein Weg (Art. 15 und 17 DSGVO sind Pflicht, kein Ausbauwunsch)
**Tatsächlich:** **0 Treffer** für alle drei
**Ort:** `src/Controller/ProfileController.php` (dort wäre der Ort), keine Entsprechung
in `Api\V1`
**Bemerkenswert:** Die technischen Voraussetzungen sind vollständig vorhanden —
`webauthn_credential` kaskadiert, `restaurant.submitted_by` und
`restaurant_suggestion.suggested_by` stehen auf `SET NULL`,
`AvatarUploadService::delete()` räumt die Datei ab. Es fehlt allein der Auslöser.
**Vorschlag:** eigenes Feature durch die volle Kette; berührt B01, B04 und B19.


**NICHT HIER GEBAUT am 2026-08-23** — Kontolöschung, Datenexport und Passwort-Zurücksetzen
sind fehlende Funktionen, keine Reparaturen. Nach Regel 1 von `sdd-build` gehören sie als
eigenes Feature mit eigener Nummer durch die volle Kette; sie berühren B01, B04 und B19.
Bleibt als BF-04 in `features/befunde.md` offen.

**NACHTRAG 2026-08-23 — aus B01 herausgelöst.** Beim Preflight für die Auslieferung
wurde sichtbar, dass dieser Befund B01 dauerhaft auf `review` hält, obwohl dort nichts
mehr zu reparieren ist: Er ist keine Reparaturaufgabe, sondern eine fehlende Funktion
über B01, B04 und B19 hinweg. Er ist jetzt dem regulären Feature `01` zugeordnet und
läuft durch die volle Kette. **Für die Bewertung von B01 zählt er nicht mehr mit** —
damit sind dort nur noch Befunde mit Grad *mittel* offen.

### BUG-02 · Roher Übersetzungsschlüssel statt Meldung — mittel

**Betrifft:** AK-05
**Reproduktion:** Registrierformular mit zwei verschiedenen Passwörtern absenden
**Erwartet:** „Die Passwörter stimmen nicht überein."
**Tatsächlich:** im Markup steht `form.password_mismatch`
**Ort:** `src/Form/RegistrationType.php:49` — `RepeatedType::invalid_message` wird in
der Domäne **`validators`** übersetzt, der Schlüssel steht aber nur in
`translations/messages.*.yaml`. Geprüft: In **allen vier** `validators.{lb,de,fr,en}.yaml`
fehlt er.
**Betrifft ein zweites Feature:** `src/Form/ChangePasswordType.php:36` verwendet
denselben Schlüssel → B04, Passwortwechsel im Profil.
**Vorschlag:** Schlüssel in die vier `validators.*.yaml` aufnehmen.


**BEHOBEN am 2026-08-23**: `form.password_mismatch` in alle vier `validators.*.yaml`
aufgenommen. Nachweis: `/de/register` und `/fr/register` mit ungleichen Passwörtern —
kein roher Schlüssel mehr, Meldung in der jeweiligen Sprache. Regressionstest
`testAk05MeldungIstUebersetztNichtDerRoheSchluessel` ist aktiv. Wirkt zugleich für B04.

### BUG-04 · Bestätigungstoken im Anwendungsprotokoll — mittel

**Betrifft:** AK-17
**Reproduktion:** registrieren, dann `grep <token> var/log/dev.log`
**Erwartet:** kein Treffer
**Tatsächlich:** 1 Treffer in `doctrine.DEBUG: Executing statement: INSERT INTO user …`
**Ort:** `config/packages/monolog.yaml`. In `dev` schreibt `main` mit `level: debug`.
In **`prod`** ist `main` ein `fingers_crossed` (`action_level: error`, `buffer_size: 50`)
mit `nested: level: debug` auf `php://stderr` — bei einem Fehler im selben Request
werden die gepufferten Doctrine-DEBUG-Zeilen also mit ausgeschrieben. Der
`doctrine`-Channel ist dort nicht ausgeschlossen (`channels: ["!deprecation"]`).
**Nicht betroffen:** Sentry. Der `sentry_logs`-Handler greift ab `WARNING`, Doctrine
loggt auf `DEBUG`; dazu `send_default_pii: false` und `zend.exception_ignore_args = On`.
**Folge:** Der Token ist ein Anmelde-Äquivalent — wer ihn hat, bestätigt das Konto.
In prod nur bei gleichzeitigem Fehler, dann aber im Hoster-Log.
**Vorschlag:** `doctrine`-Channel in `prod` aus `main` ausschließen.


**BEHOBEN am 2026-08-23** (für `prod`): `channels: ["!deprecation", "!doctrine"]` am
`main`-Handler. Nachweis: `debug:config monolog handlers.main --env=prod` zeigt
`channels: type: exclusive, elements: [deprecation, doctrine]`. Der `dev`-Handler bleibt
bewusst unverändert — ein Entwicklungslog ohne SQL wäre für die Fehlersuche wertlos, und
es verlässt den Rechner nicht.

### BUG-06 · Registrierformular verrät bestehende Konten, Meldung nicht übersetzt — mittel

**Betrifft:** AK-14, FB-07
**Reproduktion:** `/fr/register` mit einer bereits vergebenen Adresse absenden
**Erwartet:** entweder generische Antwort (wie in der API) oder wenigstens eine
französische Meldung
**Tatsächlich:** „Diese E-Mail-Adresse ist bereits registriert." — auf Deutsch, in der
französischen Fassung
**Ort:** `src/Entity/User.php:15` — `#[UniqueEntity(message: 'Diese E-Mail-Adresse ist
bereits registriert.')]`, als einzige Validierungsmeldung des Features hartkodiert
statt als Übersetzungsschlüssel
**Zusammenhang:** `src/Controller/Api/V1/AuthController.php` baut für denselben Fall
ausdrücklich Anti-Enumeration auf (identische Antwort, Timing-Ausgleich, Hinweis-Mail).
Dieser Schutz ist wirkungslos, solange dieselbe Auskunft über das Web-Formular frei
abrufbar ist.
**Vorschlag:** Übersetzungsschlüssel einsetzen; die Enumerationsfrage ist OF-02 der Spec.


**TEILWEISE BEHOBEN am 2026-08-23**: Die hartkodierte deutsche Meldung ist ersetzt durch
`user.email_unique` — der Schlüssel war in allen vier `validators.*.yaml` bereits
vorhanden und wurde nur nicht benutzt. Nachweis: `/fr/register` zeigt jetzt
„Cette adresse e-mail est déjà utilisée." Regressionstest
`testAk14MeldungBeiVergebenerAdresseFolgtDerSprache`.
**Offen bleibt die Enumeration selbst** (OF-02 der Spec): Ob das Web-Formular wie die API
generisch antworten soll, ist eine Produktentscheidung — sie kostet die verständliche
Meldung und setzt einen Passwort-Vergessen-Weg voraus, den es nicht gibt (BF-04).

### BUG-07 · Mailinhalt fällt bei asynchronem Versand auf Luxemburgisch zurück — mittel

**Betrifft:** kein AK (Hinweis aus der Prüfung von AK-20)
**Reproduktion:**
1. Über `/fr/register` registrieren, `MESSENGER_TRANSPORT_DSN=doctrine://` (Vorgabe in `.env`)
2. `php bin/console messenger:consume async`
3. Mail ansehen
**Erwartet:** Betreff und Inhalt französisch
**Tatsächlich:** Betreff „Confirmez votre adresse e-mail" ✓, Inhalt „Moien Claire Test!
Merci fir deng Registréierung…" ✗
**Gegenprobe:** mit `MESSENGER_TRANSPORT_DSN=sync://` — Betreff *und* Inhalt französisch
**Ort:** `src/Controller/RegistrationController.php` — der Betreff wird im Controller
über `$this->translator->trans()` aufgelöst, das Twig-Template der `TemplatedEmail` erst
beim Versand. Im Worker fehlt die Request-Locale, es greift `default_locale: lb`.
**Einordnung:** Produktion läuft laut `CLAUDE.md` mit `sync://` — dort tritt es **nicht**
auf. Es tritt in dev auf und würde in prod auftreten, sobald ein Worker eingeführt wird
(für die Monats-Snapshots naheliegend, siehe B18/AK-17).
**Betrifft ebenso:** B14 und B15, die ihre Bestätigungsmails genauso bauen.
**Vorschlag:** `->locale($request->getLocale())` auf der `TemplatedEmail` setzen.


**BEHOBEN am 2026-08-23**: `->locale($request->getLocale())` auf beiden `TemplatedEmail`
in B01. Symfonys `BodyRenderer` wertet `getLocale()` aus und rendert über den
`LocaleSwitcher`. Nachweis: Registrierung über `/fr/` mit
`MESSENGER_TRANSPORT_DSN=doctrine://` und anschließendem Worker-Lauf — Betreff *und*
Inhalt französisch. Regressionstest `testAk20BestaetigungsmailTraegtDieLocaleDerRegistrierung`.
**Nicht mitgeändert:** `WaitlistConfirmationService` (B14/B15) und `Api\V1\AuthController`
(B23) bauen ihre Mails genauso und sind weiterhin betroffen — anderes Feature, eigener
QA-Durchlauf.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/EmailVerificationControllerTest.php` (neu) | 6 (1 übersprungen) | AK-09, AK-10, AK-11, AK-15 (übersprungen bis BUG-01), AK-19, EC-03 |
| `tests/Functional/Controller/RegistrationControllerTest.php` (ergänzt) | 4 (1 übersprungen) | AK-02, AK-05, AK-14 |

Der Bestätigungsweg — die Hälfte dieses Features — war zuvor **ohne jeden Test**.

Die beiden übersprungenen Tests prüfen das **gewünschte** Verhalten und tragen den
Bug-Verweis in `markTestSkipped()`. Nach der Reparatur genügt es, die eine Zeile zu
entfernen; der Test wird dann grün und sichert die Behebung ab.

Vollständige Suite nach der Ergänzung: **315 Tests, 1083 Assertions, 2 übersprungen,
0 Fehler.**

## Hinweise ohne Fehlerstatus

- **`APP_SECRET` lokal identisch mit dem committeten dev-Wert.** `.env.dev` trägt
  `APP_SECRET=dfe5df93…` (laut `CLAUDE.md` bewusst als dev-Wert), und die lokale
  `.env.local` enthält denselben. Für die Prüfumgebung folgenlos; ob auf Produktion ein
  eigener Wert gesetzt ist, war von hier nicht einsehbar. Er signiert `remember_me`
  (B02) und die CSRF-Token. **Offene Frage an den Betreiber.**
- **Die Docker-Konfiguration liefert nicht die Ports, die die Konfiguration erwartet.**
  `compose.override.yaml` setzt `database.ports: ["5432"]` und `mailer.ports: ["1025","8025"]`
  ohne Host-Bindung; damit ist MySQL **nicht** auf 3306 erreichbar und Mailpit landet auf
  Zufallsports — während `.env.local` `127.0.0.1:3306` und `.env.dev`
  `smtp://localhost:1025` erwarten. Für diese Prüfung mit einer eigenen Compose-Datei im
  Scratchpad überbrückt (`!override`, DB auf 3307). Betrifft B01 nicht fachlich, aber
  jeden, der `make start` benutzt.
- **Nicht prüfbar von hier:** ob auf Produktion `MESSENGER_TRANSPORT_DSN=sync://` steht
  (entscheidet über BUG-07) und ob der Webserver fremde `Host`-Header abweist
  (entscheidet über FB-09, Host-Header-Poisoning der Bestätigungs-URL).

## Nächster Schritt

`/sdd-build B01` mit dem Auftrag, **BUG-01 bis BUG-08** zu beheben, danach erneut
`/sdd-qa B01`.

**Reihenfolge ist hier nicht beliebig:**

1. **BUG-03 vor BUG-01** — die Reparatur der Routenkollision öffnet einen
   ungedrosselten Mailversandweg auf ein fremdes Postfach.
2. **BUG-01 vor BUG-05** — ein `user_checker` sperrt alle bestehenden unbestätigten
   Konten aus, solange sie keine neue Mail anfordern können.
3. BUG-02, BUG-04, BUG-06, BUG-07 sind unabhängig und einzeln auslieferbar.
4. BUG-08 ist ein eigenes Vorhaben und gehört durch die volle Kette.

---

# Vierter Durchlauf — 2026-09-11

Stand: 2026-09-11 · Vorstufe: `building` · Branch `fix/bf-119-email-validierung`

## Fazit

**Production-ready: ja** — BF-119 ist auch auf dem Registrierungsweg behoben und am
laufenden Server belegt: `../../etc/passwd@example.lu` → **422 statt 500**, Konten
**3 → 3**.

15 von 20 Kriterien bestanden, **1 durchgefallen**, **2 nicht mehr zutreffend**,
2 nicht prüfbar. **Fünf** Befunde, alle *mittel*, keiner blockierend.

⚠ **Die Spezifikation dieses Features ist zum größten Teil überholt.** Von neun
Fehlbestand-Punkten sind **sechs erledigt** — darunter drei DSGVO-Pflichten. Dazu sind
zwei der drei ⚠-Kriterien repariert. Wer diese Spec heute liest, hält eine Plattform
ohne Kontolöschung, ohne Datenexport, ohne Passwort-Zurücksetzen und mit offener
User-Enumeration für den Ist-Zustand. Nichts davon stimmt noch.

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `testRegisterPageLoads` |
| AK-02 | ✅ bestanden | `testAk02AngemeldeterWirdVonDerRegistrierseiteWeggeleitet` |
| AK-03 | ✅ bestanden | Am Server: `name=T` → **422**, Meldung „mindestens 2 Zeichen lang sein" |
| AK-04 | ✅ bestanden | `testValidationErrorsRerenderWithoutSendingEmail` |
| AK-05 | ✅ bestanden | `testAk05UngleichePasswoerterWerdenAbgewiesen`, `testAk05MeldungIstUebersetztNichtDerRoheSchluessel` |
| AK-06 | ✅ bestanden | Am Server: **302**, Konto 0 → 1; `testSuccessfulRegistrationCreatesUserAndSendsEmail` |
| AK-07 | ✅ bestanden | DB-Abfrage nach dem Anlegen: Token gesetzt, `is_verified = 0` |
| AK-08 | ✅ bestanden | Am Server: `Location: /de/verify` |
| AK-09 | ✅ bestanden | Am Server: Token eingelöst → **302**, danach `is_verified = 1`; `testAk09GueltigerTokenVerifiziertUndLeertDenToken` |
| AK-10 | ✅ bestanden | `testAk10AbgelaufenerTokenVerifiziertNicht`, `testEc03TokenOhneAblaufzeitpunktGiltAlsAbgelaufen` |
| AK-11 | ✅ bestanden | `testAk11UnbekannterTokenLeitetAufDieStartseite` |
| **AK-12** | ❌ **durchgefallen** | Siehe BF-131. In Produktion (`SendEmailMessage: async`) wirft `send()` keine `TransportExceptionInterface` — der Zweig ist unerreichbar. ⚠ Der Prüflauf `testMailerFailureShowsWarningAndStillRedirects` ist **grün**, weil das Test-Env `sync` fährt |
| **AK-13** ⚠ | ✅ bestanden | Am Server nachgestellt: Anmeldung mit `unverified@endlech.lu` → **302**, danach `/de/profile` → **200**. Das unbestätigte Konto hat vollen Zugang — die Bestätigung bleibt folgenlos. Deckt sich mit FB-03, beide weiterhin offen |
| **AK-14** ⚠ | ❌ trifft nicht mehr zu | Die Spec sagt „es erscheint: Diese E-Mail-Adresse ist bereits registriert." Gemessen: bestehende und neue Adresse liefern **identisch 302 → `/de/verify`**. Timing angeglichen (Median **458** vs. **461 ms** bei 410 ms Hash-Kosten) — BF-09, live seit `v2026.08.29` |
| **AK-15** ⚠ | ❌ trifft nicht mehr zu | Die Spec sagt, „erneut senden" laufe ins Leere. `router:match /de/verify/resend` → **`app_verify_resend`**; der Prüflauf heißt sogar `testAk15ErneutSendenIstErreichbar` — BF-01, live seit `v2026.08.29` |
| AK-16 | ✅ bestanden | `SHOW COLUMNS`: Name, E-Mail, Passwort-Hash, Anlagezeitpunkt — keine besonderen Kategorien |
| AK-17 | ✅ bestanden | `grep 'supersecret1' var/log/dev.log` → **0 Treffer**. Der Bestätigungstoken steht im `doctrine`-Kanal, den `prod` per `!doctrine` ausschließt (BF-06/BF-12) |
| AK-18 | ✅ bestanden | Token aus der DB: 64 Zeichen Hex |
| AK-19 | ✅ bestanden | Nach dem Einlösen: `verification_token = (NULL)`, `is_verified = 1`; zweiter Aufruf greift nicht |
| AK-20 | ✅ bestanden | `testAk20BestaetigungsmailTraegtDieLocaleDerRegistrierung`; genau eine Nachricht in der Warteschlange je Registrierung |

## Fehlbestand — sechs von neun erledigt

| FB | Spec sagt | Nachgemessen |
|---|---|---|
| **FB-01** | „Kein Rate Limit auf der Registrierung" | `limiter.registration` im Controller (BF-02) |
| **FB-02** | „Kein Rate Limit auf dem erneuten Versand" | `limiter.verify_resend` im Controller (BF-02) |
| FB-03 | „Die Bestätigung wird nirgends erzwungen" | **gilt weiter** — kein `user_checker`, am Server bestätigt (AK-13) |
| **FB-04** | „Kein Löschweg für das Konto" | Route `app_profile_delete` (`POST /{_locale}/profile/loeschen`) |
| **FB-05** | „Kein Weg, ein vergessenes Passwort zurückzusetzen" | Routen `app_password_reset_request`, `app_password_reset` — **auf Produktion: HTTP 200** |
| **FB-06** | „Kein Datenexport (Auskunftsrecht)" | Route `app_profile_export` (`/{_locale}/profile/daten`) |
| **FB-07** | „`UniqueEntity`-Meldung nicht übersetzt" | `message: 'user.email_unique'` — Schlüssel statt deutschem Klartext |
| FB-08 | „Keine Wegwerf-Adressen-Prüfung, keine Passwortqualität" | **gilt weiter** — `Length(min: 8)` ist unverändert die einzige Anforderung |
| FB-09 | „Kein `trusted_hosts`" | **gilt weiter** im Code — aber siehe BF-134 zur tatsächlichen Ausnutzbarkeit |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| **User-Enumeration über die Antwort** | ✅ abgewehrt | bestehende und neue Adresse: identisch **302 → `/de/verify`** |
| **User-Enumeration über die Laufzeit** | ✅ abgewehrt | Median **458 ms** (bestehend) vs. **461 ms** (neu), bei 410 ms Hash-Kosten — der Hash läuft nachweislich in **beiden** Zweigen |
| **Host-Header-Manipulation** | ⚠️ siehe BF-134 | lokal ausnutzbar, auf Produktion vom Proxy abgefangen |
| Token nach Einlösung | ✅ entwertet | `(NULL)`, zweiter Aufruf greift nicht |
| Klartextpasswort im Log | ✅ nein | 0 Treffer |
| BF-119 am laufenden Server | ✅ behoben | 422 statt 500, Konten **3 → 3** |

## Fehler

### BF-131 · AK-12 ist in Produktion unerreichbar — und ein grüner Prüflauf verdeckt es — mittel

**Betrifft:** AK-12

**Reproduktion:**
1. `grep -n -A3 SendEmailMessage config/packages/messenger.yaml`
   → Zeile 60: `SendEmailMessage: async` · Zeile 77 (`when@test`): `SendEmailMessage: sync`
2. `php bin/phpunit --filter testMailerFailureShowsWarningAndStillRedirects` → **grün**

**Erwartet:** Der Prüflauf belegt AK-12 für die Produktion.
**Tatsächlich:** Er belegt es für `sync`. In Produktion läuft `async`; dort stellt
`MailerInterface::send()` nur in die Warteschlange und wirft keine
`TransportExceptionInterface`. Der `catch`-Zweig und die Warnung sind unerreichbar.

⚠ **Das ist dieselbe Ursache wie BF-124 (B14/AK-04) — hier aber mit einer zusätzlichen
Schicht.** Bei B14 gab es keinen Test, das Kriterium war schlicht still durchgefallen.
Hier steht ein **grüner Prüflauf** über dem Kriterium, der seine Erfüllung behauptet,
weil er in einer Umgebung läuft, die es anders konfiguriert. Ein grüner Test über einem
Verhalten, das die Produktion nicht zeigt, ist schlechter als kein Test.

⚠ Nachgewiesen wurde die Async-Wirkung bereits bei B14: Mit gestopptem Mailpit kam
**302 ohne Warnung**, der Eintrag entstand, die Nachricht blieb in `messenger_messages`.

**Vorschlag:** AK-12 an die Async-Wirklichkeit anpassen (die Zusage „der Nutzer erfährt
von einem Zustellproblem" ist nicht mehr haltbar und wurde betreiberseitig durch
`app:messenger:watch` ersetzt) und den Prüflauf entweder streichen oder mit einem
ausdrücklichen Vermerk versehen, dass er nur die `sync`-Konfiguration abdeckt.

---

### BF-132 · Sechs von neun Fehlbestand-Punkten sind erledigt, drei DSGVO-Pflichten darunter — mittel

**Betrifft:** FB-01, FB-02, FB-04, FB-05, FB-06, FB-07 sowie AK-14, AK-15

Belege stehen vollständig in den beiden Tabellen oben; jede Zeile ist gegen den Code
oder den laufenden Server gemessen.

⚠ **Warum das schwerer wiegt als bei B14 (BF-126) und B15 (BF-130):** Dort war die
Drift auf einzelne Kriterien beschränkt. Hier betrifft sie **zwei Drittel des
Fehlbestands**, und darunter sind die drei Betroffenenrechte — Löschung (Art. 17),
Auskunft (Art. 15) und der Zugangsverlust ohne Passwort-Reset. Die Spec führt sie als
offene Mängel; **auf Produktion sind sie erfüllt** (`/de/passwort-vergessen` → HTTP 200).

Eine Datenschutz-Auskunft, die anhand dieser Spec erteilt würde, wäre falsch — und zwar
zuungunsten des Betreibers.

**Vorschlag:** Fehlbestand und Kriterien in einem Zug fortschreiben. FB-03, FB-08 und
FB-09 bleiben; alles andere ist erledigt.

---

### BF-133 · Feature `01` ist gebaut und live, steht aber auf `roadmap` — mittel

**Betrifft:** `features/index.md`, nicht B01 selbst

**Reproduktion:**
1. `grep '^| 01 ' features/index.md` → Status **`roadmap`**, „2026-08-23 · aus BF-04 herausgelöst"
2. `ls features/01-betroffenenrechte/` → **nur `spec.md`** (kein `design.md`, keine `tasks.md`, kein `qa-report.md`)
3. `php bin/console debug:router | grep -E 'password_reset|profile_export|profile_delete'` → **drei Routen vorhanden**
4. `git cat-file -e master:src/Controller/PasswordResetController.php` → **auf master**
5. `curl -o /dev/null -w '%{http_code}' https://endlech.lu/de/passwort-vergessen` → **200**

**Erwartet:** Ein Feature auf `roadmap` ist nicht gebaut.
**Tatsächlich:** Es ist gebaut, gemerged und **seit unbekanntem Zeitpunkt live** — ohne
`design.md`, ohne `tasks.md`, ohne QA-Bericht. Die Kette wurde für dieses Feature nie
durchlaufen, und der Index weist es bis heute als offen aus.

⚠ **Das ist der Grund für BF-132.** Die drei DSGVO-Punkte im Fehlbestand von B01
(FB-04, FB-05, FB-06) verweisen genau auf dieses Feature. Weil sein Bau nie gebucht
wurde, blieb auch der Fehlbestand stehen.

⚠ Der Befund gehört nicht zu B01 und wird hier nur berichtet — er betrifft die
Projektübersicht. **Kein Prüflauf kann ihn finden**: Es gibt nichts, was den Status im
Index gegen die vorhandenen Routen hält.

**Vorschlag:** Feature `01` durch `/sdd-qa 01` prüfen und den Status geradeziehen. Bis
dahin ist unklar, ob die drei Betroffenenrechte fachlich vollständig sind — sie sind nur
nachweislich *vorhanden*.

---

### BF-134 · Der Bestätigungslink folgt dem `Host`-Header — lokal ausnutzbar, auf Produktion vom Proxy aufgefangen — mittel

**Betrifft:** FB-09

**Reproduktion (lokal, `php -S`):**
```
curl -H "Host: boeser-server.example" -e "http://boeser-server.example/de/register" \
     -H "Origin: http://boeser-server.example" -d "…" http://127.0.0.1:8899/de/register
→ HTTP 302, Konto angelegt
```
Der Link in der ausgehenden Mail (aus `messenger_messages` gelesen):

> `http://boeser-server.example/de/verify/3ba088c2`

⚠ **Der erste Versuch schlug fehl** — mit bloß gefälschtem `Host` antwortet die Anwendung
mit **422**, weil der stateless-CSRF-Schutz die Herkunft prüft. Erst mit **passendem
`Referer` und `Origin`** geht der Angriff durch. Wer nur den Host fälscht, hält die Lücke
fälschlich für geschlossen.

**Der Schaden:** Ein Angreifer lässt die Plattform eine **authentische** Mail an eine
fremde Adresse schicken — mit dem Absender, der Gestaltung und den SPF/DKIM-Signaturen
des Betreibers — deren Bestätigungslink auf seinen eigenen Server zeigt.

**Auf Produktion greift der Angriff nicht:**

```
curl -o /dev/null -w '%{http_code}' -H "Host: boeser-server.example" https://endlech.lu/de/
→ 503        (mit Host: endlech.lu → 200)
```

Coolifys Proxy routet nach Host und weist einen fremden ab, bevor die Anwendung ihn
sieht. ⚠ **Diese Verteidigung steht nirgends geschrieben und gehört keinem Feature.**
Sie fällt weg, sobald jemand die Anwendung ohne diesen Proxy betreibt — etwa lokal, in
einer Vorschau-Umgebung oder nach einem Hosterwechsel. `framework.yaml` setzt
`trusted_hosts` bis heute nicht.

**Vorschlag:** `trusted_hosts` auf die eigene Domain setzen — eine Zeile, die die Lücke
unabhängig vom Betriebsmodell schließt. Betrifft laut FB-09 ebenso B14 und B15, die ihre
Bestätigungslinks genauso bauen.

---

### BF-135 · Bei Versandfehlern verrät die Antwort doch, ob die Adresse vergeben ist — mittel

**Betrifft:** AK-14, BF-09

Gefunden vom `code-reviewer`, am Code verifiziert. Die beiden Zweige reagieren auf
denselben Fehler **unterschiedlich**:

| Zweig | Bei `TransportExceptionInterface` | Antwort |
|---|---|---|
| Neuanlage (`RegistrationController.php:143-149`) | `addFlash('warning', 'flash.register_email_failed')` + Redirect | **warning** |
| Bestandskonto (`sendeKontoExistiertHinweis()`, `:174-178`) | Exception wird verschluckt, Ablauf fällt in Zeile 151 | **success** |

⚠ **Der Kommentar im Bestandszweig lautet „Die Antwort bleibt in jedem Fall dieselbe."**
Das gilt aber nur *innerhalb* dieses Zweigs — der andere weicht ab. Genau die
Formulierung, die beim Lesen Sicherheit erzeugt und die Asymmetrie verdeckt.

**Reproduktion (Bedingung, nicht ausgeführt):** Mailversand muss synchron scheitern.
Dann liefert eine **neue** Adresse `warning`, eine **bestehende** `success` — das
Unterscheidungsmerkmal, das BF-09 beseitigen sollte.

⚠ **Heute nicht auslösbar, und zwar aus dem Grund, der BF-131 ausmacht:**
`SendEmailMessage: async` — `send()` stellt nur in die Warteschlange und wirft keine
`TransportExceptionInterface`. Der Fehler passiert im Worker, lange nach der Antwort.
**Nicht am laufenden System nachgestellt**, deshalb als Bedingung formuliert statt als
Ablauf.

⚠ **Das Leck wird scharf, sobald jemand auf `sync://` zurückstellt** — wovor `CLAUDE.md`
aus anderen Gründen bereits warnt. Der Schutz hängt damit an einer Transport-Einstellung
und nicht am Code, der ihn leisten soll.

**Vorschlag:** Im Neuanlage-Zweig dieselbe Antwort geben wie im Bestandszweig — die
Warnung entfällt (sie ist nach BF-131 ohnehin unerreichbar), oder beide Zweige geben sie.
Entscheidend ist, dass sie sich nicht unterscheiden.

## Was der `code-reviewer` beigetragen hat

- **BF-135** — den einzigen Befund dieses Durchlaufs, den der prüfende Agent nicht
  gefunden hat. Er betrifft genau die Stelle, die vorher als bestanden gemessen worden
  war: Die Anti-Enumeration hält auf dem **Erfolgsweg** (am Server belegt, inklusive
  Timing), nicht aber im **Fehlerfall**. Eine Verhaltensprüfung findet das nicht, solange
  der Fehlerfall nicht auslösbar ist.
- **Zwei weitere Drift-Stellen**, verifiziert und in BF-132 aufgenommen:
  `design.md` Entscheidung #6 behauptet `MESSENGER_TRANSPORT_DSN=sync://` auf Produktion
  (seit dem 2026-09-02 falsch), und `spec.md:86` nennt `CommunityController.php:29` als
  **einzige** Prüfung auf `isVerified()` — es sind inzwischen **drei** Stellen
  (`CommunityController:39`, `BoardController:96`, `BoardController:198`), die Zeilennummer
  stimmt ebenfalls nicht mehr. Damit ist auch die Folgerung von AK-13 („praktisch
  folgenlos") zu scharf: Die Bestätigung wirkt beim Vorschlags-Wizard **und** im
  Ideen-Board.
- Er bestätigte zusätzlich, dass der neue `new Address()`-Check die Anti-Enumeration
  **nicht** bricht — `Email::to()` validiert intern über dieselbe RFC-Prüfung, der
  Bestandszweig war also nie ungeprüft.

## Neue Prüfläufe dieses Durchlaufs

Keine. Für BF-135 wäre einer sinnvoll, er gehört aber zur Reparatur: Solange der
Fehlerfall bei `async` nicht auslösbar ist, prüfte er die `sync`-Konfiguration — genau
der Fehler, der BF-131 ausmacht.

## Nächster Schritt

**`/sdd-deploy`** für B01, B14 und B15 gemeinsam — alle drei stehen jetzt auf `approved`,
und die BF-119-Reparatur wirkt auf Produktion noch nicht.

⚠ Danach **`/sdd-qa 01`**: Feature `01` ist gebaut und live, steht aber auf `roadmap` und
hat weder `design.md` noch einen Prüfbericht (BF-133). Drei Betroffenenrechte sind
nachweislich *vorhanden* — ob sie fachlich vollständig sind, hat nie jemand geprüft.
