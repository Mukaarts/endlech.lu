# Sicherungsproben (BE-03)

Hier liegen die Zeugnisse von `bin/sicherung-pruefen.sh` — je Lauf eine Datei
`JJJJ-MM-TT-HHMM-sicherungsprobe.md`. Sie werden **erzeugt, nicht geschrieben**: Wer
den Inhalt von Hand anfasst, macht aus einem Nachweis eine Behauptung.

⚠ **Ein leeres Verzeichnis ist die Antwort „niemand hat es je versucht".** Genau das
war der Zustand, den BE-03 festgehalten hat: Ob Coolify sichert, in welchem Takt und ob
sich die Sicherung einspielen lässt, war nirgends belegt. Eine Sicherung, die noch
niemand eingespielt hat, ist eine Annahme.

## Ablauf

```bash
# 1 · Sicherung beschaffen — der Weg steht in docs/datenschutz.md unter BE-03
# 2 · Prüfen
make sicherung-pruefen DATEI=~/Downloads/endlech-2026-09-12.sql.gz

# mit Abgleich gegen das Original, wenn es erreichbar ist (stärkere Aussage):
bin/sicherung-pruefen.sh --datei sicherung.sql.gz \
    --quelle 'mysql://nutzer:passwort@127.0.0.1:3307/endlech'
```

Rückgabewert 0 heißt brauchbar, 1 heißt nicht brauchbar. Das Zeugnis landet hier.

## Was die Zeugnisse nicht sind

Kein Ersatz für die Frage, **ob** überhaupt gesichert wird. Das Skript prüft eine Datei,
die ihm jemand gibt — es kann nicht wissen, ob diese Datei von gestern ist oder aus dem
Frühjahr. Der Takt und die Aufbewahrung gehören in der Oberfläche des Hosters
nachgesehen und unter BE-03 eingetragen.
