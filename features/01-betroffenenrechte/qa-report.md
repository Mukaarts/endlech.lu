# 01 · Betroffenenrechte — Testbericht

Stand: 2026-09-11 · Vorstufe: **keine gültige** (Status `roadmap`) · Branch `main`

## Fazit

**Production-ready: nein** — ein Befund vom Grad *hoch*.

**Die Kontolöschung prüft das Passwort ohne jede Bremse.** Zwanzig Fehlversuche in Folge
gemessen, kein einziger abgewiesen (BF-136). Dieselbe Geheimnisprüfung ist beim
Passwortwechsel seit BF-20 gedeckelt — dort greift sie nachweislich nach fünf. Die Folge
ist hier schwerer: Das Löschen ist **unumkehrbar** und kennt laut Decision Log keine
Karenzzeit.

⚠ **Der Code läuft bereits in Produktion.** `https://endlech.lu/de/passwort-vergessen`
antwortet mit 200. Dieser Befund betrifft also keinen Entwurf, sondern eine offene
Schwachstelle an laufendem Code.

21 von 24 Kriterien bestanden, 2 nicht prüfbar, 1 bestanden mit Einschränkung. Alles
andere ist solide — die Löschkaskade, der Export und der Passwort-Reset halten jeder
Prüfung stand, die ich gefahren habe.

⚠ **Dieses Feature hatte nie eine Vorstufe.** Es steht auf `roadmap`, besitzt weder
`design.md` noch `tasks.md` noch einen Bauabschlussbericht — und ist trotzdem gebaut,
gemerged und live (BF-133). Geprüft wurde deshalb gegen `spec.md` allein, ohne die
Hinweise, die ein Baubericht sonst gibt.

## Akzeptanzkriterien im Einzelnen

### Konto löschen (US-01)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Abschnitt „Konto löschen" im Profil-Markup gefunden |
| AK-02 | ✅ bestanden | Das Formular führt ein Pflichtfeld `password` neben `_token` |
| AK-03 | ✅ bestanden | Mit falschem Passwort: **HTTP 302**, Konto bleibt (`COUNT(*) = 1`) |
| **AK-04** | ✅ bestanden | Mit korrektem Passwort: Konto **weg** (`COUNT(*) = 0`) |
| **AK-05** | ✅ bestanden | Vorher 3 Restaurants mit `submitted_by_id = 248`; nachher **11 Restaurants insgesamt, davon 0 mit dieser ID** — die Häuser bleiben, die Zuordnung wird `NULL` |
| **AK-06** | ✅ bestanden | `public/uploads/avatars/qa-probe.png` vor der Löschung angelegt, danach **nicht mehr vorhanden** |
| AK-07 | ⚠️ nicht prüfbar | Passkeys lassen sich ohne virtuellen Authenticator nicht anlegen (wie bei B03). Der Datenbank-Kaskade nach (`ON DELETE CASCADE` auf `webauthn_credential.user_id`) greift sie — das ist aber Code-Betrachtung, kein Nachweis |
| AK-08 | ✅ bestanden | `testLoeschungSchicktEineBestaetigung` |

### Daten exportieren (US-02)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-09 | ✅ bestanden | `Content-Type: application/json`, `Content-Disposition: attachment; filename=endlech-meine-daten.json` |
| **AK-10** | ✅ bestanden | Export durchsucht: **kein** Feld mit `password`/`token`/`hash`/`secret`, **kein** bcrypt- oder argon2-Muster, **kein** 64-stelliger Hex-Wert. Angriff mit `?id=1`, `?user=admin@endlech.lu`, `?email=admin@endlech.lu` → **jedes Mal die eigenen Daten** (`user@endlech.lu`); die Identität kommt aus der Sitzung |
| AK-11 | ✅ bestanden | Überrannt: 6 × **200**, danach **302** — der Limiter greift (`account_export`) |

### Passwort zurücksetzen (US-03)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-12 | ✅ bestanden | `templates/security/login.html.twig:62` verlinkt `app_password_reset_request` |
| **AK-13** | ⚠️ bestanden mit Einschränkung | Die **Antwort** ist identisch (beide **302 → `/de/login`**). Die **Laufzeit** nicht — siehe BF-137 |
| **AK-14** | ✅ bestanden | Datenbank: `password_reset_token_expires_at = 21:49:58` bei `NOW() = 20:49:58` — **exakt eine Stunde** |
| AK-15 | ✅ bestanden | `testTokenSetztDasPasswortUndIstDanachVerbraucht` |
| AK-16 | ✅ bestanden | `testAbgelaufenerTokenSagtDassErAbgelaufenIst` |
| **AK-17** | ✅ bestanden | Überrannt: 5 × **302**, danach 3 × **429** |
| AK-18 | ✅ bestanden | `testOffenerAdresswechselWirdAbgeraeumt` |

### Widerruf der Einwilligung (US-04)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-19 | ✅ bestanden | Abmeldelink in der Wartelisten-Mail (bei B14 im Mailtext belegt) |
| AK-20 | ✅ bestanden | Bei der QA von B14 am Server gemessen: **HTTP 200**, Zeilen **6 → 5** — der Eintrag wird **gelöscht**, nicht markiert |
| AK-21 | ✅ bestanden | Zweiter Aufruf: **404** statt Fehlerseite (B14) |

### Datenschutz und Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-22 | ✅ bestanden | Token aus der Datenbank: **64 Zeichen Hex** = 32 Byte |
| AK-23 | ⚠️ nicht prüfbar | Erfordert zwei Konten mit je einem gültigen Reset-Token zur selben Zeit; der Limiter (5/Stunde) und die Einmaligkeit des Tokens machten das im Rahmen dieses Durchlaufs nicht messbar. Strukturell adressiert `findOneBy(['passwordResetToken' => …])` genau ein Konto |
| AK-24 | ✅ bestanden | Ohne Anmeldung: `/de/profile/daten` → **302** zur Anmeldung, `/de/profile/loeschen` → **405** (nur POST) |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| **Passwort-Raten gegen die Kontolöschung** | ❌ **ungedeckelt** | **20 Fehlversuche**, alle 302, kein 429, keine Meldung — BF-136 |
| Gegenprobe: dieselbe Prüfung beim Passwortwechsel | ✅ gedeckelt | „Zu viele Versuche. Bitte warte 15 Minuten" ab dem sechsten |
| Fremde Daten über den Export | ✅ nicht erreichbar | drei Parametervarianten, jedes Mal die eigenen Daten |
| Geheimnisse im Export | ✅ keine | Hash-, Token- und Schlüsselsuche ohne Treffer |
| Anti-Enumeration beim Reset (Antwort) | ✅ identisch | beide 302 → `/de/login` |
| Anti-Enumeration beim Reset (Laufzeit) | ❌ **unterscheidbar** | trennscharf, BF-137 |
| Limiter auf Reset und Export | ✅ beide greifen | 429 bzw. 302 am Grenzwert |

## Fehler

### BF-136 · Die Kontolöschung prüft das Passwort ohne Rate Limit — hoch

**Betrifft:** US-01, die Projektkonvention „jeder Weg, der ein Geheimnis prüft, braucht
einen Limiter"

**Reproduktion:**
1. Als `user@endlech.lu` anmelden
2. `POST /de/profile/loeschen` mit gültigem CSRF-Token und **falschem** Passwort
3. Zwanzigmal wiederholen

**Erwartet:** Ab dem sechsten Versuch eine Drosselung — so verhält sich der
Passwortwechsel seit BF-20.
**Tatsächlich:** **20 × HTTP 302**, kein 429, keine Drosselungsmeldung. Gegenprobe an
derselben Prüfung beim Passwortwechsel im selben Durchlauf: *„Zu viele Versuche. Bitte
warte 15 Minuten"* ab dem sechsten.

**Ort:** `src/Controller/ProfileController.php:210-252` (`deleteAccount()`) — CSRF wird
geprüft (`:220`), das Passwort ebenfalls (`:226`), ein Limiter ist weder injiziert noch
konfiguriert. `config/packages/framework.yaml` führt `password_change`, `email_change`,
`password_reset` und `account_export`, aber **kein** `account_delete`;
`RouteRateLimitSubscriber` deckt `/profile/loeschen` nicht ab (nur `/passkey/`,
`/open*` und `admin_*`).

⚠ **Das Bedrohungsmodell ist dasselbe, das `password_change` seinen Limiter gab (BF-20):**
das Raten des Passworts aus einer **gekaperten Sitzung** heraus. Die Folge ist hier
schwerer — der Passwortwechsel ist rückgängig zu machen, die Löschung nicht. `spec.md`
Decision Log #1 hält ausdrücklich fest, dass es keine Karenzzeit gibt.

⚠ **Warum es niemand bemerkt hat:** `LimiterCoverageTest` prüft, ob jeder **deklarierte**
Limiter verdrahtet ist — nicht, ob an einer Stelle einer **fehlt**. Ein Limiter, den
niemand angelegt hat, ist für diesen Prüflauf unsichtbar. Zusammen damit, dass dieses
Feature nie eine QA durchlaufen hat (BF-133), blieb die Lücke offen.

**Vorschlag:** `account_delete` analog zu `password_change` anlegen — **am Konto gezählt**,
nicht an der IP (die wechselt bei Session-Hijacking mühelos), Verbrauch **vor** der
Prüfung (der Fehlversuch ist hier der Angriff, kein Tippfehler), mit `when@test`-Override.

---

### BF-137 · Die Anti-Enumeration des Passwort-Resets ist über die Laufzeit umgehbar — mittel

**Betrifft:** AK-13

**Reproduktion:** `POST /de/passwort-vergessen` je siebenmal mit einer bekannten und einer
unbekannten Adresse, Limiter vor jedem Aufruf zurückgesetzt:

```
bekannt    Median  35 ms   [31, 33, 33, 35, 35, 36, 36]
unbekannt  Median  23 ms   [23, 23, 23, 23, 23, 23, 24]
```

**Erwartet:** Kein Unterschied, der die Existenz der Adresse verrät (AK-13: „die Antwort
ist **immer dieselbe**").
**Tatsächlich:** Die Antwort ist es — beide 302 → `/de/login`. Die **Wertebereiche
überlappen nicht**: 31–36 ms gegen 23–24 ms, Differenz am Median 12 ms. Eine einzige
Messung genügt zur Unterscheidung.

**Ursache:** Bei bekannter Adresse entstehen Token, `flush()` und ein Mail-Dispatch; bei
unbekannter passiert nichts. Die Registrierung löst genau dieses Problem, indem sie das
Passwort **in beiden Zweigen** hasht (BF-09) — beim Reset gibt es kein Gegenstück.

⚠ **Praktisch gebremst, nicht geschlossen:** Der `password_reset`-Limiter lässt fünf
Anfragen je Stunde zu, ein Massenabgleich scheitert daran. Für die gezielte Frage „hat
diese eine Person hier ein Konto?" reicht **ein** Versuch. Über das Internet kommt
Netzwerk-Jitter hinzu; gemessen wurde lokal, ohne Netzwerk.

**Vorschlag:** Im unbekannten Zweig dieselbe Arbeit leisten (Token erzeugen und
verwerfen) oder den Versand in beiden Zweigen hinter dieselbe Verzögerung legen. Der
Registrierungsweg zeigt das Muster.

## Nächster Schritt

**`/sdd-build 01`** mit BF-136 — und der Hinweis, dass es **laufenden Code** betrifft:
Die Lücke ist auf Produktion offen, seit das Feature ausgeliefert wurde.

BF-137 gehört in denselben Auftrag; beide sitzen im selben Themenfeld und BF-137
blockiert für sich genommen nicht.

⚠ **Danach ist der Status geradezuziehen** (BF-133): Feature `01` steht auf `roadmap`,
hat weder `design.md` noch `tasks.md`. Nach der Reparatur gehört es über `review` auf
`approved` und `deployed` — sonst bleibt ein live laufendes Feature dauerhaft als
„geplant" verzeichnet.

---

# Zweiter Durchlauf — 2026-09-12

Stand: 2026-09-12 · Vorstufe: `building` · Branch `fix/bf-136-kontoloeschung-limiter`

## Fazit

**Production-ready: ja** — beide Befunde des ersten Durchlaufs sind behoben und am
laufenden Server belegt. Ein neuer Befund vom Grad *mittel*, keiner blockierend.

| | vor der Reparatur | jetzt |
|---|---|---|
| **BF-136** Fehlversuche bis zur Bremse | **20, keine** | 3, ab dem 4. gesperrt |
| … auch mit **richtigem** Passwort? | — | ✅ gesperrt (Konto bleibt) |
| **BF-137** Laufzeit bekannt / unbekannt | 31–36 / 23–24 ms, **trennscharf** | 141–146 / 144–145 ms, **überlappend** |

**AK-07 ist erstmals belegt** — im ersten Durchlauf noch „nicht prüfbar": Ein Konto mit
Passkey über die Anwendung gelöscht, danach Konto weg **und** Passkey weg.

⚠ **Der neue Befund BF-138 kommt vom `code-reviewer`, nicht von mir** — und er ist
gewichtiger als der, den er ersetzt: Wo BF-137 die Existenz eines Kontos über 12 ms
verriet, verrät BF-138 sie über **HTTP 500 gegen 302**.

## Verifikation der Reparaturen

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Regression: Löschung funktioniert | ✅ | Konten **3 → 2**, HTTP 302 |
| BF-136 am Grenzwert | ✅ | Versuche 1–3 „Das aktuelle Passwort ist nicht korrekt", ab 4 „Zu viele Löschversuche" |
| BF-136 mit **korrektem** Passwort nach Erschöpfung | ✅ | Konto **1 → 1** — die Sperre hält |
| **DoS gegen fremdes Kontingent** | ✅ nicht möglich | Nach 6 Versuchen mit ungültigem CSRF-Token gehen weiterhin **3** echte durch — der Deckel hinter der CSRF-Prüfung trägt |
| Ohne Anmeldung | ✅ | 5 × 302 zur Anmeldung, kein Verbrauch |
| BF-137 gültiger Submit | ✅ | 5 Paare gemessen, Bereiche überlappen vollständig |
| BF-137 ungültiger Submit | ✅ kein Leck | 422 in ~20 ms — anderer Statuscode, und es läuft keine Kontoabfrage |
| **AK-07** Passkey-Kaskade | ✅ **neu belegt** | Passkey vorher 1, Konto gelöscht → Konto weg, Passkeys **0** |
| Volle Suite, zweimal in Folge | ✅ | **1040 Tests**, 4317 Assertions |

## Fehler

### BF-138 · Der Passwort-Reset scheitert mit HTTP 500, wenn die gespeicherte Adresse RFC 2822 verletzt — mittel

**Betrifft:** AK-13, AK-14 bis AK-18

Gefunden vom `code-reviewer`, am laufenden Server nachgestellt.

**Reproduktion:**
1. Eine bestehende Adresse in der Datenbank auf `../../etc/passwd@example.lu` setzen
   (Altbestand aus der Zeit vor BF-119)
2. `/de/passwort-vergessen` für genau diese Adresse abschicken

**Gemessen:**

| Eingabe | Antwort |
|---|---|
| Altbestand, RFC-widrig, **existiert** | **HTTP 500** |
| unbekannte Adresse | HTTP 302 |
| bekannte gültige Adresse | HTTP 302 |

Die Ausnahme ist `Symfony\Component\Mime\Exception\RfcComplianceException` (aus der
Fehlerseite gelesen).

**Ort:** `src/Controller/PasswordResetController.php` — `sendeLink()` fängt nur
`TransportExceptionInterface`; `RfcComplianceException` erbt von
`\InvalidArgumentException` und wird nicht erfasst. Der Wurf passiert **vor**
`gleicheLaufzeitAn()`, womit der Angleich aus BF-137 übersprungen wird.

⚠ **Zwei Schäden, und der zweite ist der schwerere:**
1. **Enumeration über den Statuscode.** 500 gegen 302 ist ein stärkeres und billigeres
   Signal als die 12 ms, die BF-137 beseitigt hat.
2. **Zugangsverlust.** Für ein betroffenes Konto ist der Passwort-Reset **vollständig
   kaputt** — es kommt keine Mail, sondern ein Serverfehler. AK-14 bis AK-18 sind für
   diesen Nutzer nicht erfüllt, und `spec.md` nennt den Reset ausdrücklich als den Weg
   gegen den endgültigen Zugangsverlust (FB-05 von B01).

⚠ **Warum *mittel* und nicht *hoch*:** Der Befund setzt ein Konto mit RFC-widriger
**gespeicherter** Adresse voraus. Seit BF-119 können solche nicht mehr neu entstehen —
ob auf Produktion noch welche liegen, ist von hier aus **nicht feststellbar**: Für den
Hostinger-VPS liegt lokal kein SSH-Zugang (der vorhandene `endlech_ci`-Schlüssel gehört
zum abgelösten Cloudways-Setup; drei User wurden abgewiesen).

**Findet die Prüfung unten ein Konto, ist der Befund *hoch*** — dann ist ein realer
Nutzer ohne Rückweg. Findet sie keines, bleibt es ein Risiko für künftige Importe.

#### Die Prüfung — im Container auszuführen

⚠ **Eine SQL-Heuristik taugt dafür nicht.** Der erste Entwurf dieses Berichts nannte
`email REGEXP '^[.]|[.]{2}|…'`. Gegen `Mime\Address` gemessen übersieht dieses Muster
**sechs von sieben** Verstößen — darunter `endet.mit.punkt.@example.lu`,
`doppelt@@example.lu`, `@example.lu` und ein Tabulator im Local-Part. Es hätte auf
Produktion „keine Treffer" gemeldet und das Problem für erledigt erklärt. Maßgeblich ist
die Bibliothek, die den Fehler auslöst, nicht ein nachgebautes Muster.

```bash
docker exec <app-container> php -r '
require "/app/vendor/autoload.php";
$u = parse_url((string) getenv("DATABASE_URL"));
$pdo = new PDO(sprintf("mysql:host=%s;port=%d;dbname=%s", $u["host"], $u["port"] ?? 3306, ltrim($u["path"], "/")), $u["user"], $u["pass"] ?? "");
$n = 0;
foreach ($pdo->query("SELECT id, email FROM user") as $z) {
    try { new Symfony\Component\Mime\Address((string) $z["email"]); }
    catch (Throwable) { printf("BETROFFEN id=%d %s\n", $z["id"], $z["email"]); ++$n; }
}
printf("Ergebnis: %d betroffene Konten\n", $n);
'
```

**Lokal validiert**, beide Richtungen: Mit sieben eingesetzten RFC-widrigen Adressen
meldet er **7 betroffene Konten** und benennt jede einzeln; gegen den unveränderten
Fixture-Stand meldet er **0**. Er liest ausschließlich (`SELECT`) und ändert nichts.

#### Ergebnis auf Produktion — am 2026-09-12 ausgeführt

```
Ergebnis: 0 betroffene Konten
```

**Kein Konto ist betroffen.** Damit bleibt der Befund bei *mittel*: Es gibt keinen
Nutzer, dem der Passwort-Reset heute den Zugang verwehrt. Was bleibt, ist das Risiko für
**künftige** Zuflüsse — ein Bestandsimport, eine Migration oder ein zweiter Schreibweg
auf `user.email`, der die strikte Prüfung aus BF-119 nicht durchläuft. Der Zweig bleibt
ungeschützt, bis der Block um Token, `flush()` und `sendeLink()` geklammert ist.

⚠ **Der Nachweis ist ein Stichtag, keine Zusage.** Er gilt für den 2026-09-12; ein
Import am Tag danach kann ihn entwerten. Deshalb gehört die Reparatur trotzdem gemacht —
sie kostet eine Klammer, und der Befund verliert damit seine Abhängigkeit vom Bestand.

⚠ **`PasswordResetRequestType` nutzt weiterhin den nicht-strikten `Email`-Constraint** —
er ist der fünfte Weg neben den vier, die BF-119 auf STRICT gezogen hat. Das schließt
die Lücke aber ohnehin nicht: Versendet wird an die **gespeicherte**, nicht an die
eingegebene Adresse.

**Vorschlag:** Den Block mit Token, `flush()` und `sendeLink()` so klammern, dass ein
`\Throwable` protokolliert wird, der Angleich aber läuft und die generische Antwort
zurückkommt. Damit sind beide Schäden zugleich weg: kein 500er und keine Enumeration.
Zusätzlich die oben genannte Abfrage auf Produktion ausführen.

## Was der `code-reviewer` beigetragen hat

- **BF-138** — der einzige neue Befund, und er stammt vollständig von ihm. Meine
  Verhaltensprüfung konnte ihn nicht finden: Sie testet mit den Fixtures, und die
  enthalten keine RFC-widrige Adresse. Erst der Hinweis auf den ungefangenen
  Exception-Typ führte zu dem Szenario, das ich dann nachstellen konnte.
- **Eine Lücke im Prüflauf**, die ich geschlossen habe: `Bf136KontoloeschungLimiterTest`
  belegte den Verbrauch und das Überleben des Kontos, **nicht** aber die Kernzusage —
  dass die Sperre nach Erschöpfung auch das **richtige** Passwort abweist. Der Test
  steht jetzt da (`testNachErschoepfungBlocktAuchDasRichtigePasswort`) und wird bei
  entfernter Sperre rot (gegengeprüft).
- Für die Limiter-Platzierung, die DoS-Frage und die Projektkonventionen meldete er
  ausdrücklich **keinen** Befund; meine eigenen Messungen am Server deckten sich damit.

## Drei Fehlalarme dieses Durchlaufs — und was sie gekostet hätten

Dokumentiert, weil sie erklären, warum in diesem Bericht kein Nachweis ohne Gegenprobe
steht.

**1 · „Die Kontolöschung ist mit Passkey defekt."** Reproduzierbar über zwei Läufe:
Konto mit Passkey → Löschung scheitert, HTTP 302 auf `/de/profile`, Konto bleibt. Ohne
Passkey → funktioniert. Die Gegenprobe isolierte den Passkey als Ursache, und der Befund
wäre **kritisch** gewesen (Art. 17 DSGVO, stillschweigend fehlschlagend).

**Tatsächlich mein Messfehler.** Mein Regex griff das **erste** Formular mit „loeschen"
in der action — und bei vorhandenem Passkey ist das
`/de/profile/passkeys/8/loeschen`, nicht `/de/profile/loeschen`. Der CSRF-Token stammte
damit aus dem falschen Formular. Aufgefallen ist es erst, als ich **alle** Flashes
ausgelesen habe statt gezielt zu greppen: „Ungültiges CSRF-Token."

⚠ Der Hinweis lag vorher offen und ich hatte ihn übersehen: Der Erfolgsfall leitet auf
`app_home` um, mein Fehlversuch aber auf `/de/profile` — der Code war also in einem
frühen Zweig zurückgekehrt, und jeder davon setzt einen Flash.

**2 · „Nur zwei Fehlversuche gehen durch, nicht drei."** Sah nach einer
Off-by-one-Zählung im Limiter aus. Ursache: Sechs vorherige Versuche mit ungültigem
CSRF-Token hatten **Flash-Meldungen angestaut**, und mein `grep | head -1` griff einen
alten Treffer. Isoliert nachgemessen: exakt 3 durch, ab dem 4. gesperrt.

**3 · Mein eigener neuer Prüflauf färbte zwei fremde Tests rot.** Der
Erschöpfungstest leerte das Kontingent von `user@endlech.lu` — und der Zähler liegt im
Cache-Pool, **nicht** in der DAMA-Transaktion. `AccountDeletionTest` benutzt dasselbe
Konto und scheiterte zweimal. Der erste Reparaturversuch (`tearDown()`) machte es
schlimmer (4 Errors), weil der Kernel dort schon heruntergefahren ist. Gelöst durch
Aufräumen **im Test selbst**, vor der Zusicherung.

⚠ **Der Fall wird erst beim zweiten Aufruf der Suite sichtbar** — beim ersten ist das
Kontingent noch voll. Ein Prüflauf, der sich selbst nur alle zwei Läufe widerspricht,
ist die unangenehmste Sorte. Der Kommentar im Test hält das fest.

## Nächster Schritt

**`/sdd-deploy 01`** — zusammen mit dem noch ausstehenden Deploy von `v2026.09.11.2`
(BF-119). Beide liegen auf `master` bzw. in Arbeit, und keiner wirkt auf Produktion.

⚠ **Vorher die SQL-Abfrage aus BF-138 auf Produktion ausführen.** Findet sie ein Konto
mit RFC-widriger Adresse, springt der Befund auf *hoch* und gehört vor dem Ausrollen
behoben — dann ist ein realer Nutzer ohne Rückweg zu seinem Konto.

BF-138 selbst gehört in einen Auftrag an `/sdd-build 01`; er blockiert nicht, ist aber
die letzte offene Stelle an einem Feature, das die Betroffenenrechte trägt.
