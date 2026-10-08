#!/usr/bin/env python3
"""Fotozoeker: doorzoek je fotomappen op inhoud, in gewone taal.

De app (app.py) gebruikt de functies hieronder. Je kunt ook de terminal gebruiken:
    python fotozoeker.py index ~/Pictures "D:/Foto's"
    python fotozoeker.py zoek "mensen die aan tafel eten"
    python fotozoeker.py zoek "hond op het strand" --top 50 --kopieer ~/Desktop/honden

Alles draait lokaal op je eigen computer. Er gaan geen foto's naar internet;
alleen de eerste keer worden de AI-modellen (ongeveer 1 GB) gedownload.
"""

import argparse
import base64
import html
import io
import os
import shutil
import sqlite3
import sys
import time
import webbrowser
from pathlib import Path

import numpy as np
from PIL import Image, ImageOps

try:
    from pillow_heif import register_heif_opener

    register_heif_opener()
    HEIC = True
except ImportError:
    HEIC = False

# Het beeldmodel en het meertalige tekstmodel delen dezelfde vectorruimte,
# daardoor kun je in het Nederlands (of Italiaans, Engels, ...) zoeken.
BEELDMODEL = "clip-ViT-B-32"
TEKSTMODEL = "sentence-transformers/clip-ViT-B-32-multilingual-v1"

EXTENSIES = {".jpg", ".jpeg", ".png", ".webp", ".bmp", ".gif", ".tif", ".tiff"}
if HEIC:
    EXTENSIES |= {".heic", ".heif"}

MAP = Path.home() / ".fotozoeker"
STANDAARD_INDEX = MAP / "index.sqlite"

_modellen = {}


def open_db(pad=STANDAARD_INDEX):
    pad = Path(pad)
    pad.parent.mkdir(parents=True, exist_ok=True)
    db = sqlite3.connect(pad)
    db.execute(
        "CREATE TABLE IF NOT EXISTS fotos ("
        " pad TEXT PRIMARY KEY, mtime REAL, grootte INTEGER, vector BLOB)"
    )
    return db


def laad_model(naam, melding=print):
    if naam not in _modellen:
        from sentence_transformers import SentenceTransformer

        melding("AI-model laden (de eerste keer wordt het gedownload)...")
        _modellen[naam] = SentenceTransformer(naam)
    return _modellen[naam]


def vind_fotos(mappen, melding=print):
    for map_ in mappen:
        map_ = Path(map_).expanduser()
        if not map_.is_dir():
            melding(f"Let op: map bestaat niet: {map_}")
            continue
        for root, dirs, files in os.walk(map_):
            dirs[:] = [d for d in dirs if not d.startswith(".")]
            for f in files:
                if Path(f).suffix.lower() in EXTENSIES and not f.startswith("."):
                    yield Path(root) / f


def open_foto(pad, max_zijde=None):
    img = Image.open(pad)
    if max_zijde:
        # Bij JPEG direct op lagere resolutie decoderen, dat is veel sneller.
        img.draft("RGB", (max_zijde, max_zijde))
    img = ImageOps.exif_transpose(img).convert("RGB")
    if max_zijde:
        img.thumbnail((max_zijde, max_zijde))
    return img


def indexeer(db, mappen, voortgang=None, melding=print, batch=16, stoppen=None):
    """Analyseer nieuwe of gewijzigde foto's. Geeft het aantal verwerkte foto's terug.

    voortgang(klaar, totaal, foto's per seconde) wordt na elke batch aangeroepen;
    stoppen() mag True teruggeven om netjes af te breken.
    """
    bekend = {
        r[0]: (r[1], r[2]) for r in db.execute("SELECT pad, mtime, grootte FROM fotos")
    }

    melding("Fotomappen doorlopen...")
    te_doen = []
    gezien = 0
    for pad in vind_fotos(mappen, melding):
        gezien += 1
        try:
            st = pad.stat()
        except OSError:
            continue
        sleutel = str(pad.resolve())
        if bekend.get(sleutel) == (st.st_mtime, st.st_size):
            continue
        te_doen.append((sleutel, st.st_mtime, st.st_size))

    melding(f"{gezien} foto's gevonden, {len(te_doen)} nieuw of gewijzigd.")
    if not te_doen:
        return 0

    model = laad_model(BEELDMODEL, melding)
    start = time.time()
    klaar = 0
    for i in range(0, len(te_doen), batch):
        if stoppen and stoppen():
            break
        blok = te_doen[i : i + batch]
        beelden, geldig = [], []
        for item in blok:
            try:
                # Verkleinen scheelt veel geheugen; CLIP gebruikt toch maar 224 px.
                beelden.append(open_foto(item[0], max_zijde=448))
                geldig.append(item)
            except Exception as e:
                melding(f"Overgeslagen ({e.__class__.__name__}): {item[0]}")
                # Zonder vector opslaan, zodat het niet elke keer opnieuw geprobeerd wordt.
                db.execute("INSERT OR REPLACE INTO fotos VALUES (?, ?, ?, NULL)", item)
        if beelden:
            vectoren = model.encode(beelden, batch_size=batch, normalize_embeddings=True)
            db.executemany(
                "INSERT OR REPLACE INTO fotos VALUES (?, ?, ?, ?)",
                [
                    (p, m, g, v.astype(np.float32).tobytes())
                    for (p, m, g), v in zip(geldig, vectoren)
                ],
            )
        db.commit()
        klaar += len(blok)
        if voortgang:
            voortgang(klaar, len(te_doen), klaar / max(time.time() - start, 1e-6))
    return klaar


def opruimen(db):
    """Haal foto's die niet meer bestaan uit de index."""
    weg = [r[0] for r in db.execute("SELECT pad FROM fotos") if not Path(r[0]).exists()]
    db.executemany("DELETE FROM fotos WHERE pad = ?", [(p,) for p in weg])
    db.commit()
    return len(weg)


def aantal_in_index(db):
    return db.execute("SELECT COUNT(*) FROM fotos WHERE vector IS NOT NULL").fetchone()[0]


def zoek(db, vraag, top=40, niet=None, binnen_map=None, min_score=-1.0, melding=print):
    """Geeft een lijst van (score, pad) terug, beste treffer eerst."""
    rijen = db.execute(
        "SELECT pad, vector FROM fotos WHERE vector IS NOT NULL"
    ).fetchall()
    if binnen_map:
        prefix = str(Path(binnen_map).expanduser().resolve())
        rijen = [r for r in rijen if r[0].startswith(prefix)]
    if not rijen:
        return []

    paden = [r[0] for r in rijen]
    matrix = np.frombuffer(b"".join(r[1] for r in rijen), dtype=np.float32)
    matrix = matrix.reshape(len(rijen), -1)

    model = laad_model(TEKSTMODEL, melding)
    scores = matrix @ model.encode([vraag], normalize_embeddings=True)[0]
    if niet:
        scores = scores - 0.5 * (matrix @ model.encode([niet], normalize_embeddings=True)[0])

    volgorde = np.argsort(-scores)[:top]
    return [(float(scores[i]), paden[i]) for i in volgorde if scores[i] >= min_score]


def kopieer(resultaten, doel):
    doel = Path(doel).expanduser()
    doel.mkdir(parents=True, exist_ok=True)
    for n, (_, pad) in enumerate(resultaten, 1):
        shutil.copy2(pad, doel / f"{n:03d}_{Path(pad).name}")
    return doel


# ---------------------------------------------------------------- terminal


def thumbnail_data_uri(pad):
    try:
        img = open_foto(pad, max_zijde=360)
        buf = io.BytesIO()
        img.save(buf, "JPEG", quality=80)
        return "data:image/jpeg;base64," + base64.b64encode(buf.getvalue()).decode()
    except Exception:
        return ""


def schrijf_html(vraag, resultaten, uitvoer):
    kaarten = []
    for score, pad in resultaten:
        uri = Path(pad).as_uri()
        kaarten.append(
            f'<a class="k" href="{html.escape(uri)}" target="_blank">'
            f'<img loading="lazy" src="{thumbnail_data_uri(pad)}">'
            f"<span>{score:.3f} &middot; {html.escape(Path(pad).name)}</span>"
            f'<small>{html.escape(str(Path(pad).parent))}</small></a>'
        )
    pagina = f"""<!doctype html><meta charset="utf-8">
<title>Fotozoeker: {html.escape(vraag)}</title>
<style>
 body{{font-family:system-ui,sans-serif;margin:16px;background:#f6f6f4;color:#222}}
 h1{{font-size:20px;font-weight:600}}
 .g{{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}}
 .k{{background:#fff;border-radius:8px;padding:8px;text-decoration:none;color:inherit;
     box-shadow:0 1px 3px rgba(0,0,0,.12);display:flex;flex-direction:column;gap:4px}}
 .k img{{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:4px;background:#ddd}}
 small{{color:#777;overflow-wrap:anywhere}}
</style>
<h1>&ldquo;{html.escape(vraag)}&rdquo;: {len(resultaten)} beste treffers</h1>
<div class="g">{''.join(kaarten)}</div>
"""
    Path(uitvoer).write_text(pagina, encoding="utf-8")


def cmd_index(args):
    def voortgang(klaar, totaal, tempo):
        rest = (totaal - klaar) / max(tempo, 1e-6)
        print(f"  {klaar}/{totaal}  ({tempo:.1f} foto's/s, nog ~{rest / 60:.0f} min)", end="\r")

    indexeer(open_db(args.index), args.mappen, voortgang, batch=args.batch)
    print(f"\nKlaar. Index: {args.index}")


def cmd_opruimen(args):
    print(f"{opruimen(open_db(args.index))} verdwenen foto's uit de index gehaald.")


def cmd_zoek(args):
    db = open_db(args.index)
    if not aantal_in_index(db):
        sys.exit("De index is leeg. Draai eerst: python fotozoeker.py index <map>")
    resultaten = zoek(db, args.vraag, args.top, args.niet, args.map, args.min_score)

    for score, pad in resultaten:
        print(f"{score:.3f}  {pad}")

    if args.kopieer:
        doel = kopieer(resultaten, args.kopieer)
        print(f"{len(resultaten)} foto's gekopieerd naar {doel}")

    if not args.geen_html:
        uitvoer = Path(args.index).parent / "resultaten.html"
        print("Overzichtspagina maken...")
        schrijf_html(args.vraag, resultaten, uitvoer)
        webbrowser.open(uitvoer.resolve().as_uri())
        print(f"Geopend in je browser: {uitvoer}")


def main():
    p = argparse.ArgumentParser(
        description="Doorzoek je fotomappen op inhoud, in gewone taal."
    )
    p.add_argument(
        "--index",
        default=str(STANDAARD_INDEX),
        help=f"waar de index staat (standaard {STANDAARD_INDEX})",
    )
    sub = p.add_subparsers(dest="cmd", required=True)

    pi = sub.add_parser("index", help="fotomappen scannen (alleen nieuwe foto's)")
    pi.add_argument("mappen", nargs="+", help="een of meer fotomappen")
    pi.add_argument("--batch", type=int, default=16, help="foto's per stap")
    pi.set_defaults(func=cmd_index)

    pz = sub.add_parser("zoek", help="foto's zoeken met een beschrijving")
    pz.add_argument("vraag", help='bijv. "mensen die aan tafel eten"')
    pz.add_argument("--top", type=int, default=40, help="aantal treffers (40)")
    pz.add_argument("--niet", help='wat je juist NIET wilt zien, bijv. "restaurant"')
    pz.add_argument("--map", help="alleen zoeken binnen deze map")
    pz.add_argument("--min-score", type=float, default=-1.0, help="minimale score")
    pz.add_argument("--kopieer", help="kopieer de treffers naar deze map")
    pz.add_argument("--geen-html", action="store_true", help="geen browserpagina")
    pz.set_defaults(func=cmd_zoek)

    po = sub.add_parser("opruimen", help="verwijderde foto's uit de index halen")
    po.set_defaults(func=cmd_opruimen)

    args = p.parse_args()
    args.func(args)


if __name__ == "__main__":
    main()
