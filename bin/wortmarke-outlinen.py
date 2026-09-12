"""Setzt den Schriftzug der Wort-Bildmarken als Pfade (BF-99).

    python3 bin/wortmarke-outlinen.py <Inter-Bold.ttf> <ziel.tsv> '["#0891b2","#9333ea"]'

Warum ein Skript und nicht „einmal in Illustrator outlinen": Dort friert die
Schrift ein, die der jeweilige Rechner gerade zeigt — und das war bis zum
2026-09-12 die Systemschrift, auf einem Mac also SF Pro, das für Markenzwecke
nicht lizenziert ist. Hier wird ausdrücklich Inter Bold verwendet, geshaped mit
HarfBuzz (also mit dem Kerning der Schrift) und mit fontTools in
SVG-Koordinaten gerechnet. Der Lauf ist wiederholbar, und sein Ergebnis ist
nachprüfbar: Die BBox der Pfade muss sich von der des früheren <text>-Elements
genau um die Seitenlagen der Randglyphen unterscheiden (gemessen 33 links,
18 rechts).

⚠ Braucht `fonttools` und `uharfbuzz` — bewusst NICHT in `composer.json` oder
`package.json`: Das ist ein Werkzeug für einen Einmalvorgang, keine
Abhängigkeit der Anwendung. Einrichten in einer eigenen Umgebung:

    python3 -m venv /tmp/marke && /tmp/marke/bin/pip install fonttools uharfbuzz

⚠ Die Ausgabe ist eine TSV-Datei (Farbe, Pfaddaten); das Einsetzen in die SVG
geschieht von Hand oder mit einem Einzeiler. Bewusst kein Schreiben in die
SVG: Ein Skript, das eine Markendatei überschreibt, ist ein Skript, das eine
Markendatei zerstören kann.
"""
import sys, pathlib, re
import uharfbuzz as hb
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.misc.transform import Transform

TTF = sys.argv[1]
SCHRIFTGRAD = 500.0          # font-size des bisherigen <text>
X_START = 1555.0             # x des bisherigen <text>
Y_MITTE = 500.0              # y des bisherigen <text>, dominant-baseline: central
SPERRUNG = -13.0             # letter-spacing des bisherigen <text>

font = TTFont(TTF)
upem = font["head"].unitsPerEm
ascent = font["hhea"].ascent
descent = font["hhea"].descent
glyphSet = font.getGlyphSet()

# dominant-baseline="central": Die Mitte zwischen Ascender und Descender liegt
# auf y. Die Baseline sitzt also darunter.
mitte_ueber_baseline = (ascent + descent) / 2 * SCHRIFTGRAD / upem
BASELINE = Y_MITTE + mitte_ueber_baseline

daten = pathlib.Path(TTF).read_bytes()
hb_font = hb.Font(hb.Face(daten))

def pfade(text: str, cursor: float) -> tuple[str, float]:
    """Gibt den Pfad-d-String für `text` ab `cursor` zurück, samt neuem Cursor."""
    buf = hb.Buffer()
    buf.add_str(text)
    buf.guess_segment_properties()
    hb.shape(hb_font, buf, {"kern": True, "liga": True, "calt": True})

    skala = SCHRIFTGRAD / upem
    teile = []

    for info, pos in zip(buf.glyph_infos, buf.glyph_positions):
        name = font.getGlyphName(info.codepoint)
        stift = SVGPathPen(glyphSet, ntos=lambda v: f"{v:.2f}".rstrip("0").rstrip("."))
        # y wird gespiegelt: Schriftkoordinaten zeigen nach oben, SVG nach unten.
        matrix = Transform(skala, 0, 0, -skala, cursor + pos.x_offset * skala, BASELINE - pos.y_offset * skala)
        glyphSet[name].draw(TransformPen(stift, matrix))
        d = stift.getCommands()
        if d:
            teile.append(d)
        # ⚠ Die Sperrung gehört mitgerechnet — sie stand als `letter-spacing="-13"`
        # am <text> und ist Teil der Anmutung, nicht der Schrift.
        cursor += pos.x_advance * skala + SPERRUNG

    return " ".join(teile), cursor

cursor = X_START
gruppen = []
import json
FARBEN = json.loads(sys.argv[3])
for text, farbe, bezeichnung in (("Endlech", FARBEN[0], "Endlech"), (".lu", FARBEN[1], ".lu")):
    d, cursor = pfade(text, cursor)
    gruppen.append((bezeichnung, farbe, d))

print(f"# Baseline y={BASELINE:.2f} (Mitte {mitte_ueber_baseline:.2f} über der Grundlinie)")
print(f"# Schriftzug endet bei x={cursor:.1f}")
for bezeichnung, farbe, d in gruppen:
    print(f"# {bezeichnung}: {len(d)} Zeichen Pfaddaten")

ziel = pathlib.Path(sys.argv[2])
ziel.write_text("\n".join(f"{farbe}\t{d}" for _, farbe, d in gruppen), encoding="utf-8")
