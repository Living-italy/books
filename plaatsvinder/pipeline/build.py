"""Bouwt één tabel met één rij per comune (sleutel: ISTAT-code) en zet indicatoren om naar percentielen."""
import re
import unicodedata

import geopandas as gpd
import numpy as np
import pandas as pd
from scipy.spatial import cKDTree

import osm
import routing
import sources as src
from config import (FUSIES, HEUVEL, HEUVEL_LAAG_CENTRUM, HEUVEL_LAAG_VERSCHIL, KUST_MIN, MEER_MIN, SKI_MIN, SCHOOL_RADIUS_KM, WALK_RADIUS_M, OSM_PBF)

# Indicatoren: kolom -> (hoger is beter?, provinciecijfer gebruiken als het ontbreekt?, korte naam)
INDICATOREN = {
    "inkomen_eur":          (True,  True,  "inkomen"),
    "groei_pct":            (True,  False, "groei"),
    "afval_gescheiden_pct": (True,  True,  "afval"),
    "glasvezel_pct":        (True,  True,  "glasvezel"),
    "apotheken_per_1000":   (True,  False, "apotheken"),
    "pm25":                 (False, False, "pm25"),
    "buitenlanders_pct":    (True,  False, "buitenlanders"),
    "voorzieningen_800m":   (True,  False, "voorzieningen"),
    "scholen_basis_5km":    (True,  False, "school_basis"),
    "scholen_midden_5km":   (True,  False, "school_midden"),
    "scholen_hoog_5km":     (True,  False, "school_hoog"),
    "min_ziekenhuis":       (False, False, "ziekenhuis"),
    "min_station":          (False, False, "station"),
    "min_luchthaven":       (False, False, "luchthaven"),
    "min_zee":              (False, False, "zee"),
    "min_meer":             (False, False, "meer"),
    "min_ski":              (False, False, "ski"),
    "koop_m2":              (False, True,  "koop"),
    "huur_m2":              (False, True,  "huur"),
}

# Luchthavens met IATA-code maar zonder (vaste) lijnvluchten
LUCHTHAVEN_UIT = {"BLX", "FRL", "RAN", "FNU", "AOT", "VBS", "TAR"}


# ---------------------------------------------------------------- hulpfuncties
def norm(s: str) -> str:
    s = unicodedata.normalize("NFKD", str(s)).encode("ascii", "ignore").decode().lower()
    return re.sub(r"[^a-z0-9]+", " ", s).strip()


def codekaart(elenco_xlsx) -> dict:
    """Oude ISTAT-codes (o.a. Sardinië vóór 2026 en recente fusies) -> huidige code."""
    d = pd.read_excel(elenco_xlsx, dtype=str)
    oud_col = [c for c in d.columns if "2017 al 2025" in c][0]
    nieuw = d["Codice Comune formato alfanumerico"].str.zfill(6)
    oud = d[oud_col].str.replace(r"\.0$", "", regex=True).str.zfill(6)
    m = {o: n for o, n in zip(oud, nieuw) if o != n}
    m.update(FUSIES)
    return m


def harmoniseer(df: pd.DataFrame, kaart: dict, geldig: set, naam: str, som=(), gewicht=None) -> pd.DataFrame:
    """Zet codes om naar de huidige indeling en voegt gefuseerde comuni samen."""
    df = df.copy()
    df["istat"] = df["istat"].map(lambda c: kaart.get(c, c))
    los = sorted(set(df["istat"]) - geldig)
    if los:
        print(f"  ! {naam}: {len(los)} codes niet te koppelen (genegeerd): {los[:8]}")
        df = df[df["istat"].isin(geldig)]
    if not df["istat"].duplicated().any():
        return df
    cols = [c for c in df.columns if c != "istat"]
    agg = {}
    for c in cols:
        if c in som:
            agg[c] = "sum"
        elif gewicht is not None and pd.api.types.is_numeric_dtype(df[c]):
            agg[c] = lambda s, c=c: np.average(s.dropna(), weights=gewicht.reindex(s.dropna().index).fillna(1)) if s.notna().any() else np.nan
        else:
            agg[c] = "first"
    return df.groupby("istat", as_index=False).agg(agg)


def percentiel(s: pd.Series, hoger_beter: bool) -> pd.Series:
    """0-100; null blijft null. Laag-is-beter wordt omgedraaid."""
    r = s.rank(method="average", ascending=hoger_beter)
    n = s.notna().sum()
    if n <= 1:
        return pd.Series(np.where(s.notna(), 50.0, np.nan), index=s.index)
    return ((r - 1) / (n - 1) * 100).round(0)


# ---------------------------------------------------------------- centra
def centra(grens: gpd.GeoDataFrame, basis: pd.DataFrame, poi: pd.DataFrame) -> gpd.GeoDataFrame:
    """Middelpunt per comune: OSM-plaats met dezelfde naam, anders gemeentehuis, anders binnenpunt van de grens."""
    g = grens.merge(basis[["istat", "naam", "naam_alt"]], on="istat")
    pts = poi[poi.cat.isin(["plaats", "gemeentehuis"])].copy()
    pts = gpd.GeoDataFrame(pts, geometry=gpd.points_from_xy(pts.lon, pts.lat), crs=4326).to_crs(g.crs)
    j = gpd.sjoin(pts, g[["istat", "naam", "naam_alt", "geometry"]], predicate="within")
    rang = {"city": 0, "town": 1, "village": 2}
    keuze = {}
    for istat, grp in j.groupby("istat"):
        nm, alt = str(grp.naam_right.iloc[0]), str(grp.naam_alt.iloc[0] or "")
        namen = {norm(nm)} | {norm(x) for x in re.split(r"[/\-]", nm + "/" + alt) if x.strip()}
        pl = grp[grp.cat == "plaats"]
        same = pl[pl.naam_left.map(norm).isin(namen)]
        if len(same):
            r = same.assign(rk=same.place.map(rang).fillna(3)).sort_values("rk").iloc[0]
            keuze[istat] = (r.lat, r.lon, "osm-plaats", pd.to_numeric(r.get("ele"), errors="coerce"))
            continue
        th = grp[grp.cat == "gemeentehuis"]
        if len(th):
            r = th.iloc[0]
            keuze[istat] = (r.lat, r.lon, "osm-gemeentehuis", np.nan)
    rp = g.geometry.representative_point().to_crs(4326)
    out = pd.DataFrame({"istat": g.istat.values, "lat": rp.y.values, "lon": rp.x.values,
                        "centrum_bron": "grens", "osm_ele": np.nan})
    for i, istat in enumerate(out.istat):
        if istat in keuze:
            out.iloc[i, 1:5] = keuze[istat]
    out["lat"] = out.lat.astype(float).round(5)
    out["lon"] = out.lon.astype(float).round(5)
    return out


# ---------------------------------------------------------------- ziekenhuizen
ALGEMEEN = {"ospedale", "ospedali", "presidio", "ospedaliero", "civile", "di", "del", "della", "dei", "de", "e", "s",
            "san", "santa", "ss", "asst", "asl", "irccs", "azienda", "nuovo", "stabilimento", "p", "o", "po", "ao",
            "aou", "fondazione", "policlinico", "riuniti", "polo", "sede", "la", "il", "l"}


def _woorden(s) -> set:
    return {w for w in norm(s).split() if w not in ALGEMEEN and len(w) > 2}


ALLEEN_MET_PS = ("irccs privato", "irccs fondazione privata", "ente di ricerca", "istituto qualificato")
BESTUUR = re.compile(r"^(asst|asl|ausl|ulss|ats|azienda sanitaria)\b", re.I)


def ziekenhuizen(poi, grens, cen, kaart, geldig, basis) -> pd.DataFrame:
    """Officiële lijst (Ministero della Salute) met per vestiging een locatie uit OSM.

    Koppeling: het OSM-ziekenhuis in dezelfde comune met de meeste gedeelde naamwoorden (de naam van
    de eigen comune telt niet mee), anders het OSM-ziekenhuis het dichtst bij het centrum, anders het centrum.
    Weg: vestigingen waar OSM emergency=no zegt; private IRCCS en onderzoeksinstituten zonder
    emergency=yes; hoofdkantoren (ASST/ASL zonder eigen ziekenhuisnaam).
    """
    zl = src.ziekenhuizen_salute()
    zl["istat"] = zl["istat"].map(lambda c: kaart.get(c, c))
    zl = zl[zl["istat"].isin(geldig)].copy()
    comunenaam = dict(zip(basis.istat, basis.naam))
    osm_h = poi[poi.cat == "ziekenhuis"].copy()
    osm_h = gpd.GeoDataFrame(osm_h, geometry=gpd.points_from_xy(osm_h.lon, osm_h.lat), crs=4326).to_crs(grens.crs)
    osm_h = gpd.sjoin(osm_h, grens[["istat", "geometry"]], predicate="within")
    per_comune = {k: g for k, g in osm_h.groupby("istat")}
    centrum = cen.set_index("istat")
    uit = []
    for i, z in zl.iterrows():
        g = per_comune.get(z.istat)
        plaatswoorden = set(norm(comunenaam.get(z.istat, "")).split())
        wz = (_woorden(z.naam) | _woorden(z.adres)) - plaatswoorden
        nood, bron = "", "comune"
        if g is not None and len(g):
            score = g.naam.fillna("").map(lambda n: len((_woorden(n) - plaatswoorden) & wz))
            if score.max() > 0:
                r, bron = g.loc[score.idxmax()], "osm-naam"
            else:
                c = centrum.loc[z.istat]
                r, bron = g.loc[((g.lat - c.lat) ** 2 + (g.lon - c.lon) ** 2).idxmin()], "osm-dichtstbij"
            lat, lon, nood = r.lat, r.lon, str(r.emergency if pd.notna(r.emergency) else "")
        else:
            c = centrum.loc[z.istat]
            lat, lon = c.lat, c.lon
        zl.loc[i, ["lat", "lon", "locatie", "osm_emergency"]] = [lat, lon, bron, nood]
        reden = ""
        if bron == "osm-naam" and nood == "no":
            reden = "OSM: geen spoedeisende hulp"
        elif str(z.tipo).lower().startswith(ALLEEN_MET_PS) and not (bron == "osm-naam" and "yes" in nood):
            reden = "specialistisch instituut zonder spoedeisende hulp in OSM"
        elif BESTUUR.match(str(z.naam)) and not ((_woorden(z.naam) - plaatswoorden - {"asst", "asl"}) and bron == "osm-naam"):
            reden = "hoofdkantoor"
        uit.append(reden)
    zl["uitgesloten"] = uit
    zl["naam"] = zl["naam"].str.strip().str.title()
    zl.to_csv(src.RAW.parent / "work" / "ziekenhuizen.csv", index=False)
    print(f"  ziekenhuizen: {len(zl)} vestigingen, {sum(1 for u in uit if u)} uitgesloten; locatie "
          + ", ".join(f"{k} {v}" for k, v in zl.locatie.value_counts().items()))
    return zl[zl.uitgesloten == ""]


# ---------------------------------------------------------------- hoofdroutine
def bouw(provincie: str | None = None, met_osrm: bool = True) -> pd.DataFrame:
    print("Basis")
    basis = src.elenco()
    geldig = set(basis.istat)
    kaart = codekaart(src.RAW / "elenco.xlsx")

    grens = src.grenzen()
    grens["istat"] = grens["istat"].map(lambda c: kaart.get(c, c))
    grens = grens.dissolve(by="istat", aggfunc={"opp_km2": "sum"}).reset_index()
    ontbreekt = geldig - set(grens.istat)
    if ontbreekt:
        print(f"  ! geen grens voor {sorted(ontbreekt)}")

    inw_raw = src.inwoners()
    inw_gew = inw_raw.set_index("istat")["inwoners"]
    inw = harmoniseer(inw_raw.assign(_start=inw_raw.inwoners / (1 + inw_raw.groei_pct / 100)),
                      kaart, geldig, "inwoners", som=("inwoners", "_start"))
    inw["groei_pct"] = ((inw.inwoners - inw._start) / inw._start * 100).round(2)
    inw = inw.drop(columns="_start")

    def h(df, naam, **kw):
        d = df.copy()
        w = inw_gew.reindex(d["istat"].values)
        w.index = d.index
        return harmoniseer(d, kaart, geldig, naam, gewicht=w, **kw)

    tabel = basis.merge(inw, on="istat", how="left")
    tabel = tabel.merge(h(src.hoogte(), "hoogte"), on="istat", how="left")
    tabel = tabel.merge(grens[["istat", "opp_km2"]], on="istat", how="left")
    if provincie:
        tabel = tabel[tabel.prov == provincie].copy()
        print(f"  alleen provincie {provincie}: {len(tabel)} comuni")

    print("Indicatoren")
    tabel = tabel.merge(h(src.inkomen(), "inkomen"), on="istat", how="left")
    tabel = tabel.merge(h(src.afval(), "afval"), on="istat", how="left")
    tabel = tabel.merge(h(src.breedband(), "breedband"), on="istat", how="left")
    tabel = tabel.merge(h(src.apotheken(grens), "apotheken", som=("apotheken_n",)), on="istat", how="left")
    tabel = tabel.merge(h(src.buitenlanders(), "buitenlanders", som=("buitenlanders_n",)), on="istat", how="left")
    tabel = tabel.merge(h(src.omi(), "omi"), on="istat", how="left")
    tabel["apotheken_n"] = tabel["apotheken_n"].fillna(0).astype(int)   # niet in de lijst = geen apotheek
    tabel["apotheken_per_1000"] = (tabel.apotheken_n / tabel.inwoners * 1000).round(2)
    tabel["buitenlanders_pct"] = (tabel.buitenlanders_n / tabel.inwoners * 100).round(1)

    print("OpenStreetMap")
    poi = osm.extract()
    cen = centra(grens, basis, poi)
    tabel = tabel.merge(cen, on="istat", how="left")
    # hoogte ontbreekt voor comuni die na 2021 gefuseerd zijn: neem de OSM-hoogte van het centrum
    mis = tabel.hoogte_m.isna() & tabel.osm_ele.notna()
    tabel.loc[mis, "hoogte_m"] = tabel.loc[mis, "osm_ele"].astype(float).round(0)
    tabel["hoogte_bron"] = np.where(mis, "osm", np.where(tabel.hoogte_m.notna(), "istat", None))
    tabel["pm25"] = src.pm25_op_punten(tabel.lat.values, tabel.lon.values)

    # voorzieningen binnen 800 m van het centrum (equirectangulaire meters, ruim nauwkeurig genoeg)
    def m(lat, lon):
        return np.c_[np.radians(lon) * 6371000 * np.cos(np.radians(42)), np.radians(lat) * 6371000]
    vz = poi[poi.cat == "voorziening"]
    boom = cKDTree(m(vz.lat.values, vz.lon.values))
    tabel["voorzieningen_800m"] = [len(x) for x in boom.query_ball_point(m(tabel.lat.values, tabel.lon.values), WALK_RADIUS_M)]

    # scholen: MIM-bestand heeft geen coördinaten, dus: alle scholen in comuni waarvan de grens
    # binnen 5 km van het centrum ligt (de eigen comune telt altijd mee)
    sch = src.scholen_per_comune().merge(basis[["istat", "catastale"]], on="catastale", how="inner")
    sch = grens[["istat", "geometry"]].merge(sch.drop(columns="catastale"), on="istat")
    ctr = gpd.GeoDataFrame(tabel[["istat"]], geometry=gpd.points_from_xy(tabel.lon, tabel.lat), crs=4326).to_crs(grens.crs)
    ctr["geometry"] = ctr.buffer(SCHOOL_RADIUS_KM * 1000)
    j = gpd.sjoin(ctr, sch, predicate="intersects")
    agg = j.groupby("istat_left")[["basis", "midden", "hoog"]].sum()
    for niv in ["basis", "midden", "hoog"]:
        tabel[f"scholen_{niv}_5km"] = tabel.istat.map(agg[niv]).fillna(0).astype(int)
    # Trentino-Alto Adige en Valle d'Aosta hebben eigen (niet-staats)scholen die niet in het MIM-bestand staan
    zonder = tabel.regio_code.isin([2, 4])
    tabel.loc[zonder, ["scholen_basis_5km", "scholen_midden_5km", "scholen_hoog_5km"]] = np.nan

    if met_osrm:
        print("Rijtijden (OSRM)")
        routing.prepare(OSM_PBF)
        zw = src.zwemwater().rename(columns={"bathingWaterName": "naam"})
        zh = ziekenhuizen(poi, grens, cen, kaart, geldig, basis)
        lh = poi[(poi.cat == "luchthaven") & ~poi.iata.isin(LUCHTHAVEN_UIT)]
        doelen = {
            "zee": zw[zw.soort == "zee"], "meer": zw[zw.soort == "meer"],
            "ski": poi[(poi.cat == "ski") & (poi.km2 >= 0.5)],
            "station": poi[poi.cat == "station"], "luchthaven": lh, "ziekenhuis": zh,
        }
        doelen = {k: v[["lat", "lon", "naam"]].fillna("").astype({"lat": float, "lon": float}) for k, v in doelen.items()}
        rt = routing.rijtijden(tabel[["istat", "lat", "lon"]], doelen)
        tabel = tabel.merge(rt, on="istat", how="left")
    else:
        for s in ["zee", "meer", "ski", "station", "luchthaven", "ziekenhuis"]:
            tabel[f"min_{s}"] = np.nan
            tabel[f"{s}_naam"] = None

    # ligging (stap 1) als vlaggen
    h_ = tabel.hoogte_m
    tabel["kust"] = tabel.min_zee <= KUST_MIN
    tabel["meer"] = tabel.min_meer <= MEER_MIN
    heuvelland = (h_ >= HEUVEL_LAAG_CENTRUM) & (h_ < HEUVEL[0]) & (tabel.hoogteverschil_m >= HEUVEL_LAAG_VERSCHIL)
    tabel["heuvel"] = h_.between(*HEUVEL) | heuvelland
    tabel["berg"] = (h_ > HEUVEL[1]) | (tabel.min_ski <= SKI_MIN)
    tabel["vlakte"] = (h_ < HEUVEL[0]) & ~heuvelland & ~tabel.kust

    return provinciecijfers_en_percentielen(tabel)


def provinciecijfers_en_percentielen(t: pd.DataFrame) -> pd.DataFrame:
    """Ontbreekt een cijfer, neem dan het provinciecijfer (gewogen naar inwoners) en markeer dat."""
    t = t.copy()
    vlag = {i: [] for i in t.index}
    for col, (_, fallback, kort) in INDICATOREN.items():
        if not fallback or col not in t:
            continue
        ok = t[col].notna() & t.inwoners.notna()
        if not ok.any():
            continue
        sub = t[ok]
        prov = (sub[col] * sub.inwoners).groupby(sub.prov).sum() / sub.inwoners.groupby(sub.prov).sum()
        mis = t[col].isna() & t.prov.isin(prov.index)
        if not mis.any():
            continue
        t.loc[mis, col] = t.loc[mis, "prov"].map(prov).astype(float).round(1)
        for i in t.index[mis]:
            vlag[i].append(kort)
    t["provinciecijfer"] = pd.Series(vlag)
    for col, (hoger, _, kort) in INDICATOREN.items():
        if col in t:
            t[f"p_{kort}"] = percentiel(pd.to_numeric(t[col], errors="coerce"), hoger)
    return t
