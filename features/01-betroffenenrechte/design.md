# Entwurf · Feature 01 · Betroffenenrechte

> **Nachträglich angelegt am 2026-09-12 (BF-133).** Dieses Feature ist gebaut,
> gemergt und live — der Ordner enthielt aber nur `spec.md`. Weil der Bau nie
> gebucht wurde, blieb auch der Fehlbestand von B01 stehen und behauptete drei
> DSGVO-Lücken, die dieses Feature längst geschlossen hatte (BF-132).
>
> ⚠ **Diese Datei ist deshalb eine Rekonstruktion, kein Vorentwurf.** Sie hält
> fest, was der Code tut, und begründet es dort, wo die Begründung im Code
> steht. Wer sie für die Vorgabe hält, sucht Fehler an der falschen Stelle —
> dasselbe gilt sonst nur für `B`-Features.
>
> ⚠ **Es gibt bewusst kein `tasks.md`.** Ein nachträglich erfundener
> Aufgabenplan für fertigen Code belegt nichts und täuscht eine Kette vor, die
> hier nie gelaufen ist. Der Weg war: Spezifikation, Bau von Hand, QA am
> 2026-09-12 (`qa-report.md`, zwei Durchläufe).

## Struktur

| Weg | Route | Ort |
|---|---|---|
| Profilseite mit allen drei Bereichen | `app_profile` | `ProfileController::index()`, `templates/profile/index.html.twig` |
| Konto löschen | `app_profile_delete` (`POST /profile/loeschen`) | `ProfileController::deleteAccount()` + `App\Account\AccountDeleter` |
| Daten exportieren | `app_profile_export` (`GET /profile/daten`) | `ProfileController::exportData()` + `App\Account\AccountDataExporter` |
| Passwort vergessen | `app_password_reset_request` (`/passwort-vergessen`) | `PasswordResetController::request()`, `PasswordResetRequestType` |
| Neues Passwort setzen | `app_password_reset` (`/passwort-zuruecksetzen/{token}`) | `PasswordResetController::reset()`, `PasswordResetType` |
| Einwilligung widerrufen | `app_partner_revoke`, `app_organisations_revoke`, App-Warteliste | `WaitlistConfirmationService::revoke()` (Feature entstand mit BF-37) |

Felder auf `User`: `passwordResetToken` (VARCHAR, nullable) und
`passwordResetTokenExpiresAt`. Erzeugt in `generatePasswordResetToken()`
(`bin2hex(random_bytes(32))`, Frist eine Stunde), geräumt in
`clearPasswordResetToken()`.

## Zugriffsregeln

| Weg | Regel | Warum |
|---|---|---|
| Profil, Export, Löschung | `#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]` auf Klassenebene | AK-24: ohne Anmeldung führt der Weg zur Anmeldeseite, nicht zu einer 403-Seite |
| Löschung | zusätzlich CSRF-Token `delete-account` **und** Passwort | AK-02. Eine gekaperte Sitzung soll ein Konto nicht vernichten können, und einen Rückweg gibt es naturgemäß nicht |
| Passwort-Reset | **keine** Anmeldepflicht | Der Token *ist* der Nachweis. Eine Anmeldepflicht machte den Weg für den unbenutzbar, für den er gebaut ist |
| Reset-Token | `requirements: ['token' => '[a-f0-9]{64}']` | Ein Token in falschem Format erreicht den Controller nicht; AK-23 folgt aus der Suche über die Spalte, nicht aus einer Prüfung im Code |

## Missbrauchsschutz

| Weg | Limiter | Zählt an | Verbrauch |
|---|---|---|---|
| Passwort-Reset anfordern | `password_reset` | IP | **vor** der Verarbeitung, sobald das Formular gültig ist — jeder Aufruf verschickt eine Mail an eine frei wählbare Adresse (AK-17) |
| Datenexport | `account_export` | Konto | vor dem Lesen (AK-11) — der Export liest den halben Bestand eines Kontos zusammen |
| Kontolöschung | `account_delete` (3 je 15 Min.) | **Konto** | **vor** der Passwortprüfung, **hinter** der CSRF-Prüfung (BF-136) |

⚠ **Die Reihenfolge beim Löschen ist beides Absicht.** Vor der Passwortprüfung,
weil ein Fehlversuch hier nicht der Tippfehler, sondern der Angriff ist — anders
als bei Registrierung und Wartelisten (BF-11). Hinter der CSRF-Prüfung, damit
niemand von aussen das Kontingent des Opfers leerlaufen lässt; das wäre ein
Denial-of-Service gegen die eigene Löschfunktion. Am Konto gezählt, nicht an der
IP: Der Angriff läuft aus einer gekaperten Sitzung heraus, und dort wechselt die
IP mühelos.

## Entscheidungen

| # | Entschieden | Alternative | Grund |
|---|---|---|---|
| 1 | Zufallstoken in der Datenbank mit Frist von einer Stunde | signierte URL ohne Spalte | dasselbe Muster wie die E-Mail-Bestätigung (B01) und der Adresswechsel (BF-19); eine Stunde statt sieben Tagen, weil dieser Token den **Zugang** verschiebt |
| 2 | Der Reset räumt einen offenen Adresswechsel ab | stehen lassen | AK-18: Wer ein Konto übernehmen will, stösst zuerst die Adressänderung an und wartet. Der rechtmässige Inhaber hat mit dem Reset bewiesen, dass ihm das Postfach gehört — alles davor Angefangene ist damit hinfällig |
| 3 | Anti-Enumeration über Antwort **und Laufzeit** | nur gleiche Antwort | Text und Statuscode waren immer gleich, die Dauer nicht: gemessen 31–36 ms mit Konto gegen 23–24 ms ohne, zwei Bereiche ohne Überlappung. Eine einzige Messung genügte für „hat diese Person hier ein Konto?" (BF-137) |
| 4 | Mindestdauer 120 ms statt gleicher Arbeit im leeren Zweig | Token erzeugen und verwerfen | Die Kosten stecken in `flush()` und im Mail-Dispatch, beides ist nicht folgenlos nachbaubar. Eine Untergrenze deckt zusätzlich den Fall ab, dass die Datenbank unter Last langsamer antwortet |
| 5 | Adresse als `Mime\Address` prüfen, **bevor** ein Token entsteht | erst beim `->to()` | BF-138: Sonst endet der Weg für eine RFC-widrige Altadresse in HTTP 500 — und weil der Wurf vor dem Laufzeit-Angleich lag, nahm er ihn mit (19,5 ms gegen 141–146 ms). Der Statuscode verriet das Konto deutlicher als die 12 ms aus BF-137 |
| 6 | Export als JSON-Download, `Cache-Control: no-store, private` | Mailversand, CSV | Ein Export gehört in keinen Zwischenspeicher — weder im Browser noch in einem Proxy davor. JSON, weil die Struktur verschachtelt ist (Einreichungen, Vorschläge, Ideen, Stimmen) |
| 7 | Die Löschbestätigung geht **vor** dem Löschen hinaus | danach | Danach gibt es die Adresse nicht mehr. Ein Versandfehler darf die Löschung nicht aufhalten: Sie ist das Recht, die Mail die Höflichkeit |
| 8 | Restaurants überleben die Löschung (`submittedBy` → `NULL`) | mitlöschen | AK-05. Die Einträge sind Gemeingut der Plattform, nicht Eigentum des Einreichers; ein Barrierefreiheitsdatensatz verschwindet nicht, weil jemand sein Konto aufgibt |
| 9 | Die Löschung des letzten Admins wird abgelehnt | zulassen | EC-02: Das Projekt hat genau ein Admin-Konto (B19/FB-01) — ohne diese Sperre wäre der Verwaltungsbereich danach unerreichbar |
| 10 | Widerruf **löscht** den Eintrag | Status auf `declined` setzen | AK-20. Ein widerrufener Eintrag, der weiterhin gespeichert ist, widerspricht dem Zweck des Widerrufs; für die Wartelisten ist der Abmeldelink zudem der einzige Weg hinaus (Feature 08, OF-01). ⚠ Der Löschauftrag an Brevo (`scheduleRemoval()`) steht **vor** dem `remove()` — danach gäbe es niemanden mehr, der ihn stellen könnte, und die Adresse bliebe beim Dritten stehen. Ein Widerruf, der bei einem Auftragsverarbeiter wirkungslos bleibt, ist keiner (BF-84) |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | `templates/profile/index.html.twig`, Abschnitt „Konto löschen" | |
| AK-02 | `deleteAccount()` — CSRF, Limiter, `isPasswordValid()` | |
| AK-03 | `flash.profile_wrong_password`, Weiterleitung aufs Profil | ⚠ derselbe Statuscode wie der Erfolgsfall (302) — deshalb ist der Prüfpunkt von BF-136 der **Verbrauch**, nicht der Code |
| AK-04 | `AccountDeleter::delete()`, `Session::invalidate()`, Token-Storage geleert | |
| AK-05 | `submittedBy` mit `SET NULL` (Migration `Version20260319000000`) | Datenbankebene, nicht Anwendungscode |
| AK-06 | `AvatarUploadService` im `AccountDeleter` | löscht die Datei, nicht nur die Spalte |
| AK-07 | Fremdschlüssel `ON DELETE CASCADE` auf `webauthn_credential` | erstmals im QA-Lauf vom 2026-09-12 geprüft |
| AK-08 | `sendeLoeschbestaetigung()` vor `delete()` | siehe Entscheidung 7 |
| AK-09 | `exportData()` mit `HeaderUtils::makeDisposition(ATTACHMENT, …)` | |
| AK-10 | `AccountDataExporter::export()` | gibt Stammdaten, Einreichungen, Vorschläge, Ideen, Stimmen, Passkey-**Namen** und die App-Vormerkung aus (Feature 08/AK-51); `password` und jeder Token stehen strukturell nicht im Array — ⚠ auch nicht der `confirmationToken` der Vormerkung, denn der ist ein Zugangsgeheimnis und kein Datum über die Person, und der Export landet am Ende in einem unverschlüsselten Postfach |
| AK-11 | Limiter `account_export`, am Konto | |
| AK-12 | Link auf `templates/security/login.html.twig` | |
| AK-13 | `request()` — identische Antwort in beiden Zweigen, dazu `gleicheLaufzeitAn()` | siehe Entscheidung 3 und 4; ⚠ BF-138 war die Ausnahme davon |
| AK-14 | `User::generatePasswordResetToken()`, `+1 hour` | |
| AK-15 | `reset()` — `clearPasswordResetToken()` nach dem Setzen | ein zweiter Aufruf findet nichts mehr → HTTP 404 |
| AK-16 | `isPasswordResetTokenExpired()` → HTTP 410 mit Hinweis | |
| AK-17 | Limiter `password_reset`, an der IP | |
| AK-18 | `clearPendingEmail()` im `reset()` | siehe Entscheidung 2 |
| AK-19 | `WaitlistConfirmationService::register()` setzt `revokeUrl` in jede Mail | BF-37 |
| AK-20 | `WaitlistConfirmationService::revoke()` — `remove()`, nicht Statuswechsel | |
| AK-21 | `revoke()` liefert einen Zustand statt einer Ausnahme | dieselbe Konstruktion wie `confirm()` in B14 |
| AK-22 | `bin2hex(random_bytes(32))` = 64 Hex-Zeichen | ⚠ nicht mit `webauthnHandle` verwechseln, der bewusst 16 Byte hat (Längengrenze des Bundles) |
| AK-23 | Suche über `passwordResetToken` | trifft strukturell nur das Konto, an dem der Token steht |
| AK-24 | `#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]` | |

## Was hier nicht gelöst ist

- **BF-138** (*mittel*, offen zum Zeitpunkt des Baus, behoben am 2026-09-12):
  Für eine gespeicherte Adresse, die RFC 2822 verletzt, ist der Reset
  wirkungslos — behebbar ist nur, dass er nicht mehr mit HTTP 500 endet und dass
  der Betreiber davon erfährt. Zustellen kann niemand.
- **Keine Sperre unbestätigter Konten an der Anmeldung** (B01/FB-03). Betrifft
  dieses Feature nur mittelbar: Ein unbestätigtes Konto kann sich löschen und
  exportieren.
- **Keine Selbstauskunft für Partner- und Organisations-Wartelisten** (B14/FB-05,
  B15/FB-05). Der Export deckt ein **Konto** ab und nimmt die App-Vormerkung mit
  (sie hängt über den Unique-Index an der Adresse); Partner- und
  Organisationseinträge hängen an keinem Konto und sind über diesen Weg nicht
  erreichbar.
