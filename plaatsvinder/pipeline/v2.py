"""Nieuwe kolommen voor het criteria-onderzoek: risico, levendigheid, expats, toerisme, veiligheid,
fiscaal, natuur, cultuur, dagelijkse basis, LEA, 7%-regime, trends en rijtijden naar huis."""
import json

import geopandas as gpd
import numpy as np
import pandas as pd
from scipy.spatial import cKDTree

import bronnen_v2 as bv
import osm
from config import RAW

M_800 = 800


def _meters(lat, lon):
    return np.c_[np.radians(lon) * 6371000 * np.cos(np.radians(42)), np.radians(lat) * 6371000]


def _merge(t, d, naam, harmoniseer, how="left", som=()):
    d = harmoniseer(d, naam, som=som)
    return t.merge(d, on="istat", how=how)


def voeg_toe(t: pd.DataFrame, grens: gpd.GeoDataFrame, basis: pd.DataFrame, poi: pd.DataFrame,
             harmoniseer) -> pd.DataFrame:
    """harmoniseer(df, naam): zet oude ISTAT-codes om naar de huidige indeling (uit build.py)."""
    print("Bronnen criteria-onderzoek")
    t = _merge(t, bv.seismische_zone(), "seismische zones", harmoniseer)
    t = _merge(t, bv.idrogeo(), "IdroGEO", harmoniseer)
    t = t.merge(bv.bosbrand(grens), on="istat", how="left")
    zr = bv.zona_rossa(basis)
    t = t.merge(zr, on="istat", how="left")
    t["vulkaan_rode_zone"] = t["vulkaan_deel"].map({"geheel": 2, "deels": 1}).fillna(0).astype(int)

    t = _merge(t, bv.snai(), "SNAI", harmoniseer)
    t = _merge(t, bv.leeftijd_2026(), "leeftijd", harmoniseer)
    b16 = bv.bevolking_2016()
    t = _merge(t, b16, "bevolking 2016", harmoniseer, som=("inwoners_2016",))
    t["groei_10j_pct"] = ((t["inwoners"] - t["inwoners_2016"]) / t["inwoners_2016"] * 100).round(1)

    ex = bv.expats()
    t = _merge(t, ex, "nationaliteit", harmoniseer, som=("inw_nl", "inw_be", "inw_de", "inw_gb"))
    for c in ["inw_nl", "inw_be", "inw_de", "inw_gb"]:
        t[c] = t[c].fillna(0).astype(int)            # niet in het bestand = geen inwoners met die nationaliteit
    t["expats_per_1000"] = ((t[["inw_nl", "inw_be", "inw_de", "inw_gb"]].sum(axis=1)) / t["inwoners"] * 1000).round(2)
    t["nl_per_1000"] = (t["inw_nl"] / t["inwoners"] * 1000).round(2)
    t["be_per_1000"] = (t["inw_be"] / t["inwoners"] * 1000).round(2)
    t["de_per_1000"] = (t["inw_de"] / t["inwoners"] * 1000).round(2)
    t["gb_per_1000"] = (t["inw_gb"] / t["inwoners"] * 1000).round(2)

    t = _merge(t, bv.bedden(), "bedden", harmoniseer, som=("bedden",))
    t["bedden"] = t["bedden"].fillna(0)               # niet in de lijst = geen geregistreerde accommodatie
    t["bedden_per_inw"] = (t["bedden"] / t["inwoners"]).round(3)
    koppel, per_comune = bv.provincie_koppeling(RAW / "elenco.xlsx")
    t = t.merge(per_comune, on="istat", how="left")
    t = t.merge(bv.seizoensindex_provincie(koppel), on="prov_oud", how="left")
    t = t.merge(bv.delitti_provincie(koppel), on="prov_oud", how="left")
    t = _merge(t, bv.woningen(), "woningen", harmoniseer, som=("woningen",))

    t = t.merge(bv.verkopen(basis), on="istat", how="left")
    t["ntn_per_100"] = (t["ntn"] / t["woningen"] * 100).round(2)
    t = t.merge(bv.addizionale(basis), on="istat", how="left")
    t = t.merge(bv.lea_per_comune(basis), on="istat", how="left")
    t = t.merge(bv.kratergemeenten(basis), on="istat", how="left")

    # 7%-regime: inwoners op 1 januari 2026 (= 31-12-2025), dus voor een eerste optiejaar 2027
    regels = bv.cfg("regime7")
    tab = t[["istat", "regio_code", "inwoners", "krater"]]
    uit = [bv.in_aanmerking_7procent(i, 2027, tab, regels) for i in t["istat"]]
    t["regime7_status"] = [u["uitkomst"] for u in uit]
    t["regime7_reden"] = [u["reden"] for u in uit]
    t["regime7"] = t["regime7_status"].map({"ja": 1, "nee": 0})     # te_verifieren = null

    sm = {g["istat"]: g["jaar"] for g in bv.cfg("sciolti_mafia")["gemeenten"]}
    t["sciolto_mafia_jaar"] = t["istat"].map(sm)
    tr = bv.cfg("trends")
    t["trend"] = t["regio_code"].isin(tr["regio_codes"]) | t["prov"].isin(tr["provincies"])

    # natuur: aandeel beschermd gebied binnen 5 km van het centrum
    ctr = gpd.GeoDataFrame(t[["istat"]], geometry=gpd.points_from_xy(t.lon, t.lat), crs=4326).to_crs(grens.crs)
    buf = ctr.copy()
    buf["geometry"] = ctr.buffer(5000)
    nat = gpd.read_file(osm.NATUUR_GPKG).to_crs(grens.crs)
    nat = gpd.GeoDataFrame(geometry=[nat.union_all()], crs=grens.crs).explode(index_parts=False)
    snede = gpd.overlay(buf, nat, how="intersection", keep_geom_type=True)
    opp = snede.assign(a=snede.area).groupby("istat")["a"].sum()
    t["natuur_5km_pct"] = (t["istat"].map(opp).fillna(0) / (np.pi * 5000 ** 2) * 100).clip(upper=100).round(1)
    t["dichtheid"] = (t["inwoners"] / t["opp_km2"]).round(1)

    # cultuur en dagelijkse basis rond het centrum
    c_xy = _meters(t.lat.values, t.lon.values)
    cu = poi[poi.cat == "cultuur"]
    t["cultuur_5km"] = [len(x) for x in cKDTree(_meters(cu.lat.values, cu.lon.values)).query_ball_point(c_xy, 5000)]
    vz = poi[poi.cat == "voorziening"]
    soorten = {"supermarkt": ["supermarket", "convenience"], "apotheek": ["pharmacy"], "bar": ["bar", "cafe"],
               "huisarts": ["doctors"]}
    for naam, subs in soorten.items():
        p = vz[vz["sub"].isin(subs)]
        boom = cKDTree(_meters(p.lat.values, p.lon.values))
        t[f"{naam}_800m"] = [len(x) > 0 for x in boom.query_ball_point(c_xy, M_800)]
    t["basis_800m"] = t[["supermarkt_800m", "apotheek_800m", "bar_800m"]].sum(axis=1)

    # klimaat (ERA5-Land), alleen als de bestanden er zijn
    import era5
    if era5.beschikbaar():
        print("  Copernicus: klimaat per comune")
        t = t.merge(era5.per_comune(t), on="istat", how="left")
    else:
        print("  ! klimaatdata ontbreekt (python pipeline/era5.py download): klimaat blijft leeg")
        t["klimaat_geschat"] = False
        for c in ["dagen_boven_30", "vorstdagen", "trend_hete_dagen", "neerslag_mm", "neerslag_trend_pct",
                  "temp_jan", "temp_jul", "zon_kwh"]:
            t[c] = np.nan

    # scholen binnen ca. 20 minuten (15 km hemelsbreed), zoals scholen binnen 5 km in versie 1
    return t


def scholen_20min(t: pd.DataFrame, grens: gpd.GeoDataFrame, sch: gpd.GeoDataFrame) -> pd.DataFrame:
    ctr = gpd.GeoDataFrame(t[["istat"]], geometry=gpd.points_from_xy(t.lon, t.lat), crs=4326).to_crs(grens.crs)
    ctr["geometry"] = ctr.buffer(15000)
    j = gpd.sjoin(ctr, sch, predicate="intersects")
    agg = j.groupby("istat_left")[["basis", "midden", "hoog"]].sum()
    for niv in ["basis", "hoog"]:
        t[f"scholen_{niv}_20min"] = t.istat.map(agg[niv]).fillna(0).astype(int)
    return t


def naar_huis(t: pd.DataFrame, min_grens: pd.DataFrame) -> pd.DataFrame:
    """Kortste rijtijd (uur) naar Utrecht, Brussel, Keulen of München via de beste grensovergang."""
    g = json.loads((bv.CONFIG / "grens_naar_huis.json").read_text(encoding="utf-8"))
    best = {}
    for stad in g["bestemmingen"]:
        kol = []
        for over, rij in g["minuten"].items():
            if f"min_grens_{over}" in min_grens and rij.get(stad) is not None:
                kol.append(min_grens[f"min_grens_{over}"] + rij[stad])
        best[stad] = pd.concat(kol, axis=1).min(axis=1)
    for stad, naam in [("Utrecht", "utrecht"), ("Brussel", "brussel"), ("Keulen", "keulen"), ("München", "munchen")]:
        t[f"uur_{naam}"] = (best[stad] / 60).round(1)
    t["uur_naar_huis"] = t[["uur_utrecht", "uur_brussel", "uur_keulen", "uur_munchen"]].min(axis=1)
    return t
