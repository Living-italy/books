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
    if not a.geen_download:
        download.download_all(force=a.opnieuw)

    tabel = build.bouw(provincie=a.provincie, met_osrm=not a.zonder_osrm)
    bronnen = dict(SOURCES)
    bronnen["omi"] = f"handmatig: {OMI_DIR.relative_to(ROOT)}"
    export.schrijf(tabel, bronnen, a.provincie)
    web_data = ROOT / "web" / "data"
    web_data.mkdir(exist_ok=True)
    for f in ["comuni.json", "comuni-lite.json"]:
        shutil.copy2(ROOT / "data" / f, web_data / f)
    print(f"Klaar in {(time.time() - t0) / 60:.1f} min")


if __name__ == "__main__":
    main()
