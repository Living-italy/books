"""Schrijft data/comuni.json (volledig), data/comuni-lite.json (filterkolommen), data/comuni.csv en
kopieert de configuratie die de site nodig heeft naar web/config/."""
import json
import math
import shutil
from datetime import date

import numpy as np
import pandas as pd

from build import INDICATOREN
from config import OUT_FULL, OUT_LITE, OUT_CSV, ROOT
from scores import WINTERTEST, TACHTIGTEST

LIGGING = ["kust", "meer", "heuvel", "berg", "vlakte"]   # bit 0..4 in de lite-versie
GROOTTE = [1000, 5000, 20000, 100000]                     # klassen 0..4

BASIS = ["istat", "naam", "naam_alt", "prov", "provincie", "regio", "regio_code", "lat", "lon",
         "inwoners", "hoogte_m"]
# waarden die de site toont (naast de indicatoren uit criteria.json)
EXTRA = ["koop_m2", "huur_m2", "koop_laagste_zone", "omi_semester", "glasvezel_pct",
         "min_ziekenhuis", "ziekenhuis_naam", "min_luchthaven", "luchthaven_naam", "min_luchthaven_jaarrond",
         "luchthaven_jaarrond_naam", "min_station", "station_naam", "min_apotheek",
         "uur_utrecht", "uur_brussel", "uur_keulen", "uur_munchen",
         "seismische_zone", "seismische_zone_detail", "frane_pop_pct", "alluvioni_pop_pct", "bosbrand_pct",
         "vulkaan", "vulkaan_deel", "snai_klasse", "groei_10j_pct", "aandeel_65plus",
         "inw_nl", "inw_be", "inw_de", "inw_gb", "nl_per_1000", "be_per_1000", "de_per_1000", "gb_per_1000",
         "expats_per_1000", "bedden", "bedden_per_inw", "seizoen_pct", "delitti_100k", "delitti_jaar",
         "addizionale_irpef_pct", "lea", "regime7_status", "regime7_reden", "krater",
         "natuur_5km_pct", "dichtheid", "hoogteverschil_m", "cultuur_5km", "voorzieningen_800m",
         "supermarkt_800m", "apotheek_800m", "bar_800m", "huisarts_800m", "basis_800m",
         "scholen_basis_5km", "scholen_midden_5km", "scholen_hoog_5km", "scholen_basis_20min", "scholen_hoog_20min",
         "woningen", "apotheken_n", "ntn", "ntn_jaar", "neerslag_mm"]
# waarden die score.php nodig heeft voor filters en korte teksten
SCORE_W = ["koop_m2", "huur_m2", "min_luchthaven_jaarrond", "luchthaven_jaarrond_naam", "min_ziekenhuis", "min_station",
           "glasvezel_pct", "uur_utrecht", "uur_brussel", "uur_keulen", "uur_munchen", "seismische_zone", "vulkaan_deel",
           "regime7_status", "lea"]
AFRONDEN = {"hoogte_m": 0, "hoogteverschil_m": 0, "glasvezel_pct": 1, "dichtheid": 1, "natuur_5km_pct": 1}


def _schoon(v):
    if v is None:
        return None
    if hasattr(v, "item"):
        v = v.item()
    if isinstance(v, float):
        if math.isnan(v):
            return None
        if v.is_integer():
            return int(v)
    return v


def _int(v):
    v = _schoon(v)
    return None if v is None else int(round(v))


def schrijf(t: pd.DataFrame, bronnen_downloads: dict, provincie: str | None) -> None:
    t = t.sort_values("istat").reset_index(drop=True).copy()
    for c, d in AFRONDEN.items():
        if c in t:
            t[c] = pd.to_numeric(t[c], errors="coerce").round(d)
    crit = json.loads((ROOT / "config/criteria.json").read_text(encoding="utf-8"))
    ind_kol = [i["kolom"] for c in crit["criteria"] for i in c["indicatoren"]]
    waarden = list(dict.fromkeys(ind_kol + EXTRA))
    s_cols = [f"s_{c['id']}" for c in crit["criteria"]]
    rijen = []
    for _, r in t.iterrows():
        o = {c: _schoon(r.get(c)) for c in BASIS}
        o.update({c: bool(r.get(c)) for c in LIGGING})
        o["trend"] = bool(r.get("trend"))
        o["w"] = {c: _schoon(r.get(c)) for c in waarden if c in t and _schoon(r.get(c)) is not None}
        o["s"] = {c[2:]: _schoon(r.get(c)) for c in s_cols}
        o["t"] = {"winter": _schoon(r.get("test_winter")), "tachtig": _schoon(r.get("test_tachtig")),
                  "winter_delen": {k: _schoon(r.get(f"winter_{k}")) for k in WINTERTEST},
                  "tachtig_delen": {k: _schoon(r.get(f"tachtig_{k}")) for k in TACHTIGTEST}}
        o["provinciecijfer"] = r.get("provinciecijfer") or []
        rijen.append(o)
    # bron per waarde: uit criteria.json, aangevuld voor de extra waarden
    bron_van = {i["kolom"]: i["bron"] for c in crit["criteria"] for i in c["indicatoren"]}
    bron_van.update({k: "omi" for k in ["koop_m2", "huur_m2", "koop_laagste_zone"]})
    bron_van.update({k: "osrm" for k in EXTRA if k.startswith(("min_", "uur_"))})
    bron_van.update({"seismische_zone_detail": "dpc_sismica", "vulkaan": "zona_rossa", "vulkaan_deel": "zona_rossa",
                     "snai_klasse": "snai", "glasvezel_pct": "agcom", "regime7_status": "regime7", "krater": "regime7",
                     "inw_nl": "istat_cittadinanza", "inw_be": "istat_cittadinanza", "inw_de": "istat_cittadinanza",
                     "inw_gb": "istat_cittadinanza", "bedden": "istat_ricettivi", "seizoen_pct": "istat_turismo_maand",
                     "woningen": "istat_abitazioni", "ntn": "omi_ntn", "ntn_jaar": "omi_ntn", "neerslag_mm": "era5", "apotheken_n": "salute_farmacie", "voorzieningen_800m": "osm",
                     "supermarkt_800m": "osm", "apotheek_800m": "osm", "bar_800m": "osm", "huisarts_800m": "osm",
                     "scholen_basis_5km": "mim_scuole", "scholen_midden_5km": "mim_scuole", "scholen_hoog_5km": "mim_scuole",
                     "scholen_basis_20min": "mim_scuole", "scholen_hoog_20min": "mim_scuole",
                     "uur_utrecht": "osrm_publiek", "uur_brussel": "osrm_publiek", "uur_keulen": "osrm_publiek",
                     "uur_munchen": "osrm_publiek"})
    zonder_data = [c["id"] for c in crit["criteria"] if t[f"s_{c['id']}"].isna().all()]
    meta = {"gemaakt": date.today().isoformat(), "zonder_data": zonder_data, "aantal": len(rijen), "provincie": provincie,
            "percentielen_over": f"provincie {provincie}" if provincie else "heel Italië",
            "bron_van": bron_van, "downloads": bronnen_downloads}
    OUT_FULL.write_text(json.dumps({"meta": meta, "comuni": rijen}, ensure_ascii=False, separators=(",", ":")),
                        encoding="utf-8")

    # lite: kolomsgewijs; positie i = i-de comune in comuni.json
    pr = t[["prov", "provincie", "regio_code"]].drop_duplicates("prov").sort_values("provincie")
    provs = [[r.prov, r.provincie, int(r.regio_code)] for r in pr.itertuples()]
    pidx = {p[0]: i for i, p in enumerate(provs)}

    def kol(c, f=_int):
        return [f(v) for v in t[c]] if c in t else [None] * len(t)

    def halfuur(v):
        v = _schoon(v)
        return None if v is None else int(round(v * 2))

    r7 = t["regime7_status"].map({"nee": 0, "ja": 1, "te_verifieren": 2}).fillna(0).astype(int)
    sz = t["seismische_zone"].fillna(4).astype(int).clip(1, 4) - 1
    vk = t["vulkaan_rode_zone"].fillna(0).astype(int)
    vlag = (sz + vk * 4 + r7 * 16).tolist()
    lite = {
        "versie": meta["gemaakt"], "n": len(t), "ligging": LIGGING, "grootte": GROOTTE,
        "vlag": "bits 0-1: seismische zone - 1; 2-3: rode zone (0 nee, 1 deels, 2 geheel); 4-5: 7%-regime (0 nee, 1 ja, 2 te verifiëren)",
        "provincies": provs, "pv": [pidx[p] for p in t.prov],
        "gr": [None if pd.isna(v) else int(np.searchsorted(GROOTTE, v, side="right")) for v in t.inwoners],
        "lig": [sum(1 << i for i, c in enumerate(LIGGING) if bool(r.get(c))) for _, r in t.iterrows()],
        "f": vlag,
        "lh": kol("min_luchthaven_jaarrond"), "st": kol("min_station"), "zh": kol("min_ziekenhuis"),
        "gv": kol("glasvezel_pct"),
        "uu": kol("uur_utrecht", halfuur), "ub": kol("uur_brussel", halfuur),
        "uk": kol("uur_keulen", halfuur), "um": kol("uur_munchen", halfuur),
        "koop": kol("koop_m2", lambda v: None if pd.isna(v) else int(round(v / 10))),
        "huur": kol("huur_m2", lambda v: None if pd.isna(v) else int(round(v * 10))),
        "lat": kol("lat", lambda v: int(round(v * 100))), "lon": kol("lon", lambda v: int(round(v * 100))),
        "eenheden": "uu/ub/uk/um: halve uren; lat/lon: graden x 100; koop: euro/m2 gedeeld door 10; huur: euro/m2 x 10; regio = provincies[pv][2]",
    }
    OUT_LITE.write_text(json.dumps(lite, separators=(",", ":")), encoding="utf-8")
    t.drop(columns=["geometry"], errors="ignore").to_csv(OUT_CSV, index=False)

    # site: compact bestand voor score.php en details per provincie voor de gemeentepagina
    web = ROOT / "web" / "data"
    (web / "detail").mkdir(parents=True, exist_ok=True)
    for f in (web / "detail").glob("*.json"):
        f.unlink()
    compact = []
    for o in rijen:
        compact.append({k: o[k] for k in ["istat", "naam", "prov", "regio", "regio_code", "lat", "lon", "inwoners",
                                          "hoogte_m", "trend"] + LIGGING} | {"s": o["s"], "tw": o["t"]["winter"], "tt": o["t"]["tachtig"],
                       "w": {k: o["w"].get(k) for k in SCORE_W if o["w"].get(k) is not None}})
    (web / "score.json").write_text(json.dumps({"meta": {"gemaakt": meta["gemaakt"], "aantal": len(compact)},
                                                "comuni": compact}, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
    per_prov = {}
    for o in rijen:
        per_prov.setdefault(o["prov"], {})[o["istat"]] = o
    for p, d in per_prov.items():
        (web / "detail" / f"{p}.json").write_text(json.dumps(d, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
    (web / "meta.json").write_text(json.dumps(meta, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")

    # configuratie voor de site
    doel = ROOT / "web" / "config"
    doel.mkdir(exist_ok=True)
    for naam in ["criteria", "profielen", "bronnen", "lea_2023", "regime7", "trends", "aandachtspunten", "luchthavens"]:
        shutil.copy2(ROOT / "config" / f"{naam}.json", doel / f"{naam}.json")
    print(f"  {OUT_FULL.name}: {OUT_FULL.stat().st_size/1e6:.2f} MB, {OUT_LITE.name}: "
          f"{OUT_LITE.stat().st_size/1e3:.0f} kB, {len(rijen)} comuni")
