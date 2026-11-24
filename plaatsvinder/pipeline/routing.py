"""Rijtijden met OSRM (Docker). Per comune alleen de N hemelsbreed dichtstbijzijnde kandidaten per soort."""
import subprocess
import time
from concurrent.futures import ThreadPoolExecutor

import numpy as np
import pandas as pd
import requests
from scipy.spatial import cKDTree

from config import OSRM_DIR, OSRM_IMAGE, OSRM_PORT, OSRM_CANDIDATES, MAX_RIJTIJD

CONTAINER = "plaatsvinder-osrm"


def _xyz(lat, lon):
    la, lo = np.radians(lat), np.radians(lon)
    return np.c_[np.cos(la) * np.cos(lo), np.cos(la) * np.sin(lo), np.sin(la)]


def prepare(pbf, force=False):
    if (OSRM_DIR / "KLAAR").exists() and not force:
        return
    script = __file__.replace("routing.py", "osrm_prepare.sh")
    print("  OSRM-wegennet voorbereiden (duurt even)")
    subprocess.run([script, str(pbf), str(OSRM_DIR)], check=True)


class Server:
    def __enter__(self):
        if subprocess.run(["docker", "info"], capture_output=True).returncode != 0:
            raise RuntimeError("Docker draait niet. Start Docker en probeer opnieuw (of gebruik --zonder-osrm).")
        subprocess.run(["docker", "rm", "-f", CONTAINER], capture_output=True)
        subprocess.run(["docker", "run", "-d", "--name", CONTAINER, "-p", f"127.0.0.1:{OSRM_PORT}:5000",
                        "-v", f"{OSRM_DIR}:/data", OSRM_IMAGE, "osrm-routed", "--algorithm", "mld",
                        "--max-table-size", "1000", "/data/italy-roads.osrm"], check=True, capture_output=True)
        self.url = f"http://127.0.0.1:{OSRM_PORT}"
        s = requests.Session()
        s.trust_env = False   # lokaal verkeer niet via de proxy
        self.s = s
        for _ in range(180):
            try:
                if s.get(f"{self.url}/nearest/v1/driving/9.16,45.19", timeout=2).ok:
                    return self
            except requests.RequestException:
                pass
            time.sleep(1)
        raise RuntimeError("OSRM-server start niet")

    def __exit__(self, *a):
        subprocess.run(["docker", "rm", "-f", CONTAINER], capture_output=True)

    def table(self, src, dsts):
        coords = ";".join(f"{lo:.6f},{la:.6f}" for la, lo in [src, *dsts])
        dest_idx = ";".join(str(i) for i in range(1, len(dsts) + 1))
        r = self.s.get(f"{self.url}/table/v1/driving/{coords}",
                       params={"sources": "0", "destinations": dest_idx, "annotations": "duration"}, timeout=60)
        r.raise_for_status()
        return [np.nan if v is None else v for v in r.json()["durations"][0]]


def rijtijden(centra: pd.DataFrame, doelen: dict, workers: int = 4) -> pd.DataFrame:
    """centra: istat, lat, lon. doelen: soort -> DataFrame(lat, lon, naam).

    Geeft per comune per soort de kortste rijtijd in minuten en de naam van dat doel.
    """
    src_xyz = _xyz(centra.lat.values, centra.lon.values)
    kand = {}
    for soort, d in doelen.items():
        d = d.reset_index(drop=True)
        k = min(OSRM_CANDIDATES, len(d))
        _, idx = cKDTree(_xyz(d.lat.values, d.lon.values)).query(src_xyz, k=k)
        kand[soort] = (d, idx.reshape(len(centra), k))

    def een(i):
        row = centra.iloc[i]
        dsts, meta = [], []
        for soort, (d, idx) in kand.items():
            for j in idx[i]:
                dsts.append((d.lat[j], d.lon[j]))
                meta.append((soort, d.naam[j]))
        for poging in range(3):
            try:
                dur = srv.table((row.lat, row.lon), dsts)
                break
            except requests.RequestException:
                time.sleep(1 + poging)
        else:
            dur = [np.nan] * len(dsts)
        res = {"istat": row.istat}
        for soort in kand:
            best = min(((v, n) for v, (s, n) in zip(dur, meta) if s == soort and not np.isnan(v)),
                       default=(np.nan, None))
            res[f"min_{soort}"] = None if np.isnan(best[0]) else (round(best[0] / 60) if soort.startswith("grens_") else min(round(best[0] / 60), MAX_RIJTIJD))
            res[f"{soort}_naam"] = best[1]
        return res

    with Server() as srv:
        t = time.time()
        with ThreadPoolExecutor(workers) as ex:
            out = list(ex.map(een, range(len(centra))))
        print(f"  {len(centra)} comuni gerouteerd in {time.time() - t:.0f} s")
    return pd.DataFrame(out)
