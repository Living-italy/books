"""Schrijft data/comuni.json (volledig), data/comuni-lite.json (filterkolommen) en data/comuni.csv."""
import json
import math
from datetime import date

import numpy as np
import pandas as pd

from build import INDICATOREN
from config import OUT_FULL, OUT_LITE, OUT_CSV

LIGGING = ["kust", "meer", "heuvel", "berg", "vlakte"]   # bit 0..4 in de lite-versie
GROOTTE = [1000, 5000, 20000, 100000]                     # klassen 0..4: tiny, small, medium, large, metro

VOLLEDIG = (["istat", "naam", "naam_alt", "prov", "provincie", "regio", "regio_code", "capoluogo", "lat", "lon",
             "centrum_bron", "inwoners", "hoogte_m", "hoogteverschil_m", "opp_km2", "apotheken_n", "buitenlanders_n", "omi_semester",
             "zee_naam", "meer_naam", "ski_naam", "station_naam", "luchthaven_naam", "ziekenhuis_naam"]
            + LIGGING + list(INDICATOREN) + ["provinciecijfer"])


def _schoon(v):
    if v is None:
        return None
    if isinstance(v, float) and math.isnan(v):
        return None
    if hasattr(v, "item"):
        v = v.item()
        if isinstance(v, float) and math.isnan(v):
            return None
    if isinstance(v, float) and v.is_integer():
        return int(v)
    return v


def _int(v):
    v = _schoon(v)
    return None if v is None else int(round(v))


AFRONDEN = {"hoogte_m": 0, "hoogteverschil_m": 0, "inkomen_eur": 0, "opp_km2": 2, "groei_pct": 2, "afval_gescheiden_pct": 1,
            "glasvezel_pct": 1, "buitenlanders_pct": 1, "apotheken_per_1000": 2, "pm25": 1}


def schrijf(t: pd.DataFrame, bronnen: dict, provincie: str | None) -> None:
    t = t.sort_values("istat").copy()
    for c, d in AFRONDEN.items():
        if c in t:
            t[c] = pd.to_numeric(t[c], errors="coerce").round(d)
    pcols = [c for c in t.columns if c.startswith("p_")]
    rijen = []
    for _, r in t.iterrows():
        o = {c: _schoon(r.get(c)) for c in VOLLEDIG if c in t}
        for c in LIGGING:
            o[c] = bool(r[c]) if c in r else False
        o["p"] = {c[2:]: _schoon(r[c]) for c in pcols}
        rijen.append(o)
    meta = {"gemaakt": date.today().isoformat(), "aantal": len(rijen), "provincie": provincie,
            "percentielen_over": f"provincie {provincie}" if provincie else "heel Italië", "bronnen": bronnen}
    OUT_FULL.write_text(json.dumps({"meta": meta, "comuni": rijen}, ensure_ascii=False, separators=(",", ":")),
                        encoding="utf-8")

    # provincies: [sigla, naam, regiocode], op naam; "pv" is per comune de index in die lijst
    pr = t[["prov", "provincie", "regio_code"]].drop_duplicates("prov").sort_values("provincie")
    PROVS = [[r.prov, r.provincie, int(r.regio_code)] for r in pr.itertuples()]
    PROV_IDX = {p[0]: i for i, p in enumerate(PROVS)}
    # lite: kolomsgewijs en zo klein mogelijk; positie i = i-de comune in comuni.json (op ISTAT-code)
    def kol(c, f=_int):
        return [f(v) for v in t[c]] if c in t else [None] * len(t)
    lite = {
        "versie": meta["gemaakt"], "n": len(t), "ligging": LIGGING, "grootte": GROOTTE,
        "regio": kol("regio_code"),
        "provincies": PROVS,
        "pv": [PROV_IDX[p] for p in t.prov],
        "gr": [None if pd.isna(v) else int(np.searchsorted(GROOTTE, v, side="right")) for v in t.inwoners],
        "lig": [sum(1 << i for i, c in enumerate(LIGGING) if bool(r.get(c))) for _, r in t.iterrows()],
        "lh": kol("min_luchthaven"), "st": kol("min_station"),
        "koop": kol("koop_m2"), "huur": kol("huur_m2", lambda v: None if pd.isna(v) else round(float(v), 1)),
        "lat": kol("lat", lambda v: int(round(v * 1000))), "lon": kol("lon", lambda v: int(round(v * 1000))),
    }
    OUT_LITE.write_text(json.dumps(lite, separators=(",", ":")), encoding="utf-8")

    t.drop(columns=["geometry"], errors="ignore").to_csv(OUT_CSV, index=False)
    print(f"  {OUT_FULL.name}: {OUT_FULL.stat().st_size/1e6:.2f} MB, {OUT_LITE.name}: "
          f"{OUT_LITE.stat().st_size/1e3:.0f} kB, {len(rijen)} comuni")
