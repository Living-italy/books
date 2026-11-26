"""Ververst alles: python pipeline/run.py

Opties:
  --provincie PV     alleen één provincie (sigla), om te controleren
  --geen-download    gebruik wat al in data/raw staat
  --opnieuw          download alles opnieuw en bouw OSRM en OSM-extract opnieuw
  --zonder-osrm      sla de rijtijden over (kolommen blijven leeg)
"""
import argparse
import shutil
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

import build      # noqa: E402
import download   # noqa: E402
import bronnen_v2   # noqa: E402
import dekking   # noqa: E402
import config_check   # noqa: E402
import export     # noqa: E402
from config import SOURCES, OSRM_DIR, WORK, OMI_DIR, ROOT   # noqa: E402


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--provincie")
    ap.add_argument("--geen-download", action="store_true")
    ap.add_argument("--opnieuw", action="store_true")
    ap.add_argument("--zonder-osrm", action="store_true")
    a = ap.parse_args()
    t0 = time.time()

    if a.opnieuw:
        shutil.rmtree(OSRM_DIR, ignore_errors=True)
        for f in WORK.glob("osm-poi.*"):
            f.unlink()
    fouten = config_check.controleer()
    if fouten:
        raise SystemExit("Configuratie ongeldig:\n" + "\n".join(fouten))
    if not a.geen_download:
        download.download_all(force=a.opnieuw)
        bronnen_v2.download_v2(force=a.opnieuw)

    tabel = build.bouw(provincie=a.provincie, met_osrm=not a.zonder_osrm)
    bronnen = dict(SOURCES)
    bronnen["omi"] = f"handmatig: {OMI_DIR.relative_to(ROOT)}"
    export.schrijf(tabel, bronnen, a.provincie)
    web_data = ROOT / "web" / "data"
    web_data.mkdir(exist_ok=True)
    shutil.copy2(ROOT / "data" / "comuni-lite.json", web_data / "comuni-lite.json")
    import kaart
    kaart.web_kaart()                     # gemeentegrenzen voor de kaart bij de uitslag
    (web_data / "comuni.json").unlink(missing_ok=True)   # de site gebruikt score.json en detail/<prov>.json
    if not a.provincie:
        dekking.schrijf()
    print(f"Klaar in {(time.time() - t0) / 60:.1f} min")


if __name__ == "__main__":
    main()
