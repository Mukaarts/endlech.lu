# Checkliste: was in den Konten zu tun ist

Stand: 2026-09-05 · Alles hier braucht eine Anmeldung, die außerhalb dieses Projekts
liegt. Nach jedem Häkchen gehört das Datum in `docs/datenschutz.md` — ohne Datum ist
später nicht feststellbar, ab wann etwas galt.

---

## 1 · Brevo: anonyme Nachverfolgung einschalten (DS-04)

**Das dringendste**, weil es bereits laufende Bestätigungsmails betrifft — nicht erst
künftige Kampagnen.

- [ ] In Brevo anmelden
- [ ] Oben rechts auf den **Kontonamen** klicken → **Settings**
- [ ] Reiter **Default settings** → Abschnitt **Tracking**
- [ ] **Anonymous email tracking** auf **Yes**
- [ ] Den Hinweistext lesen → **Activate**
- [ ] Oben rechts **Save** (ohne diesen Schritt greift nichts)
- [ ] Datum in `docs/datenschutz.md` eintragen, Abschnitt „Öffnungs- und Klickverfolgung"

⚠ **Wirkt nur für künftige und geplante Mails.** Was bis dahin an personenbezogenen
Öffnungsdaten entstanden ist, bleibt in Brevo liegen. Wer es entfernen will, muss es
dort löschen — das ist ein eigener Vorgang.

⚠ **Danach ist keine Segmentierung nach Öffnern mehr möglich.** Für dieses Projekt kein
Verlust; falls doch einmal eine Kampagne darauf aufbauen soll, ist die Einstellung
umkehrbar — dann aber mit der Einwilligungsfrage von vorn.

---

## 2 · Brevo: Anfrage abschicken (DS-01b, DS-01c, DS-01d)

- [ ] `docs/anfragen/2026-09-brevo-dpa.md` öffnen, Text an `dpo@brevo.com` senden
- [ ] Datum des Versands hier vermerken: ______________
- [ ] Antwort in `docs/datenschutz.md` eintragen (die Tabelle steht am Ende der Anfrage)

---

## 3 · Hostinger: erst nachsehen, dann fragen (DS-02b, DS-02c)

- [ ] hPanel → **Rechnungen**: Welche Gesellschaft stellt aus? → DS-02b
      Notiert: ______________________________
- [ ] hPanel → **VPS** → Übersicht: Welche Region ist eingestellt?
      Notiert: ______________________________
- [ ] Bleibt offen, ob der Standort zugesichert ist → `docs/anfragen/2026-09-hostinger-standort.md` senden
- [ ] Antworten in `docs/datenschutz.md`, Abschnitt Hostinger, eintragen

---

## 4 · Sicherungen prüfen (BE-03)

Kein Datenschutzpunkt, aber der mit dem größten Schaden, wenn er unbeantwortet bleibt:
Seit Feature 08 liegen E-Mail-Adressen Dritter mit Einwilligungszeitpunkt in der
Datenbank. Die lassen sich nicht rekonstruieren.

- [ ] **Hostinger:** Gibt es automatische VPS-Snapshots? Takt? Aufbewahrung?
- [ ] **Coolify:** Ist eine Datenbanksicherung eingerichtet? Wohin schreibt sie?
- [ ] ⚠ **Liegt die Sicherung auf demselben Rechner wie die Datenbank?** Dann ist sie
      keine — ein Ausfall des Rechners nimmt beide mit
- [ ] **Eine Sicherung einmal einspielen.** Ein Rückweg, den niemand gegangen ist, ist
      eine Annahme, keine Sicherung.
      **Seit 2026-09-12 ist das ein Befehl:** Sicherung herunterladen, dann
      `make sicherung-pruefen DATEI=~/Downloads/endlech-JJJJ-MM-TT.sql.gz`.
      Rückgabewert 0 = brauchbar, 1 = nicht brauchbar; das Zeugnis landet in
      `qa/sicherungen/`. Sieben Prüfungen inklusive der stillen Fälle (Struktur-Dump ohne
      Daten, fehlende Fremdschlüssel, verlorene `consent_at`-Bedingung) — Einzelheiten in
      `docs/datenschutz.md` unter BE-03
- [ ] Ergebnis in `docs/datenschutz.md` unter BE-03 eintragen

---

## 5 · Uptime-Prüfung einrichten (BE-01) — Uptime Kuma, zweiter VPS

> **Eingerichtet am 2026-09-12.** Kein Konto nötig, kein Fremddienst, kein zusätzlicher
> Auftragsverarbeiter: Der Wächter läuft selbst betrieben auf einem **zweiten VPS** —
> nicht neben dem Bewachten, und das ist der Punkt. Die Klickwege für UptimeRobot und
> Better Stack, die hier vorher standen, sind damit gegenstandslos.

Zwei Monitore, vollständige Vorgaben in `docs/datenschutz.md` unter BE-01:

- [x] **HTTP(s)** auf `https://endlech.lu/health`, 60 s, Retries 2 — **eingerichtet und
      Alarm ausgelöst am 2026-09-12**, „down" und „up" angekommen
- ~~**HTTP(s) – Json Query** auf `https://endlech.lu/open.json`~~ — **bewusst nicht**
      (Entscheidung 2026-09-12). ⚠ Folge: `/health` fragt die Datenbank nicht ab, ein
      Datenbankausfall bleibt in Kuma grün und fällt nur über Sentry auf, wenn jemand die
      Seite aufruft
- [ ] **Push-Monitor** für den Messenger-Consumer, **360 s**, Retries 2 — Kuma erzeugt die
      Adresse. ⚠ Nicht 300 s: Der Puls teilt sich die Minute mit dem Brevo-Abgleich im
      selben Consumer und kommt dadurch Sekunden zu spät; bei exakt 300 s verpasste Kuma
      gelegentlich ein Fenster
- [ ] ⚠⚠ Diese Adresse als `APP_UPTIME_PUSH_URL` auf der **Worker**-Ressource in Coolify
      eintragen, **nicht** auf der Anwendung. Zwei Ressourcen, zwei Variablenlisten; steht
      sie am falschen Ort, läuft der Puls nie und Kuma meldet Dauer-Alarm über einen
      gesunden Worker
- [ ] ⚠ **Benachrichtigungskanal an jedem einzelnen Monitor anhaken.** Kuma hängt ihn nur
      automatisch an, wenn er als „Default enabled" angelegt wurde. Sonst wird der Monitor
      brav rot und **niemand erfährt es** — das ist hier der wahrscheinlichste Fehler
- [ ] Zertifikatswarnung: Kuma warnt von sich aus 21/14/7 Tage vorher und erfüllt die
      Zusage damit; nachzusehen ist nur, dass sie an einem Kanal hängt
- [ ] ⚠ **Jeden Alarm einmal auslösen.** HTTP: Ziel kurz verbiegen. Push: Worker kurz
      anhalten. Ein Alarm, der nie ausgelöst hat, hat nie funktioniert — bei Push doppelt,
      weil dort das *Ausbleiben* das Signal ist und ein falsch gesetzter Takt sich nicht
      von Ruhe unterscheidet
- [ ] Ergebnis in `docs/datenschutz.md` unter BE-01 eintragen

⚠ **Die Reihenfolge ist hier nicht beliebig.** Der Puls ist Code und läuft erst nach
einem Rollout (`main` → Release → `master` → Coolify). Ein aktiver Push-Monitor meldet
vorher vom ersten Takt an „ausgefallen" — zu Recht, denn es ruft niemand an. Das ist der
Fehlalarm, mit dem eine neue Überwachung ihr Vertrauen verliert, bevor sie einmal
gearbeitet hat. Die Adresse entsteht aber erst mit dem Monitor. Deshalb:

1. Monitor 1 sofort anlegen — **erledigt**.
2. Monitor 3 anlegen und **sofort pausieren**; die Push-Adresse kopieren.
3. `APP_UPTIME_PUSH_URL` auf der **Worker**-Ressource eintragen.
4. Ausrollen — **beide** Ressourcen. Der Puls läuft im Worker; wer nur die Anwendung neu
   ausrollt, lässt den Worker auf dem alten Stand ohne Puls.
5. `php bin/console app:worker:pulse` im Worker-Container einmal von Hand aufrufen:
   „Puls angekommen (HTTP 200)" ist der Nachweis, dass Adresse und Weg stimmen.
6. Monitor 3 fortsetzen.

⚠ **Offen und benannt: Wer bewacht den Wächter?** Stirbt der Kuma-VPS, kommen keine
Alarme mehr, und das fällt nicht auf — dieselbe Bauartgrenze wie beim
`app:messenger:watch`, eine Ebene höher.

---

## Reihenfolge, wenn die Zeit knapp ist

1. **Punkt 1** — läuft gegen bereits verschickte Mails, jeder Tag zählt
2. **Punkt 4** — ein Datenverlust ist der einzige Schaden hier, der endgültig ist.
   Der Prüfteil kostet jetzt einen Befehl; offen ist nur noch die Frage, **ob** gesichert
   wird, und die beantwortet allein die Oberfläche des Hosters
3. **Punkt 5** — die Alarmprobe ist der kleinste Handgriff auf dieser Liste und der
   einzige, der die Zusage „der Weg funktioniert" von einer Behauptung in einen Nachweis
   verwandelt
4. **Punkte 2 und 3** — wichtig für die Rechenschaftslage, aber nichts läuft schief,
   solange sie offen sind
