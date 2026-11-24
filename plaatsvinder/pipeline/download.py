"""Download van alle open bronnen naar data/raw. OMI wordt NIET gedownload (login nodig)."""
import re
import shutil
import subprocess
import zipfile
from pathlib import Path

import requests

from config import RAW, SOURCES, FARMACIE_PAGE, OSM_URLS, OSM_PBF

UA = {"User-Agent": "plaatsvinder-pipeline/1.0"}


def _get(url: str, dest: Path, force: bool) -> None:
    if dest.exists() and dest.stat().st_size > 0 and not force:
        print(f"  = {dest.relative_to(RAW)} (al aanwezig)")
        return
    dest.parent.mkdir(parents=True, exist_ok=True)
    tmp = dest.with_suffix(dest.suffix + ".part")
    print(f"  ↓ {dest.relative_to(RAW)}")
    with requests.get(url, headers=UA, stream=True, timeout=120) as r:
        r.raise_for_status()
        with open(tmp, "wb") as f:
            for chunk in r.iter_content(1 << 20):
                f.write(chunk)
    tmp.replace(dest)


def _unzip(zpath: Path, target: Path) -> None:
    if target.exists():
        return
    with zipfile.ZipFile(zpath) as z:
        z.extractall(target)


def farmacie_url() -> str:
    html = requests.get(FARMACIE_PAGE, headers=UA, timeout=60).text
    m = re.search(r'href="(/sites/default/files/opendata/FRM_FARMA_5_\d+\.csv)"', html)
    if not m:
        raise RuntimeError("Link naar apothekenbestand niet gevonden op " + FARMACIE_PAGE)
    return "https://www.dati.salute.gov.it" + m.group(1)


def download_osm(force: bool = False) -> None:
    if OSM_PBF.exists() and not force:
        print(f"  = osm/{OSM_PBF.name} (al aanwezig)")
        return
    OSM_PBF.parent.mkdir(parents=True, exist_ok=True)
    for url in OSM_URLS:
        print(f"  ↓ {url}")
        # curl kan hervatten; prettig bij 2,5 GB
        res = subprocess.run(["curl", "-fsSL", "--retry", "5", "-C", "-", "-o", str(OSM_PBF), url])
        if res.returncode == 0:
            return
        print(f"    mislukt (code {res.returncode}), volgende bron proberen")
        OSM_PBF.unlink(missing_ok=True)
    raise RuntimeError("OSM-bestand kon niet worden gedownload")


def download_all(force: bool = False) -> None:
    print("Bronnen downloaden")
    for name, url in SOURCES.items():
        _get(url, RAW / name, force)
    _get(farmacie_url(), RAW / "salute" / "farmacie.csv", force)
    download_osm(force)

    x = RAW / "x"
    if force and x.exists():
        shutil.rmtree(x)
    _unzip(RAW / "p2.zip", x / "p2")
    _unzip(RAW / "strasa.zip", x / "strasa")
    _unzip(RAW / "limiti.zip", x / "limiti")
    _unzip(RAW / "irpef.zip", x / "irpef")
    _unzip(RAW / "eea" / "bwd.zip", x / "bwd")
    _unzip(RAW / "eea" / "pm25.zip", x / "pm25")
