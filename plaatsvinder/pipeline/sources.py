"""Laders per bron. Elke functie geeft een DataFrame met kolom 'istat' (6 cijfers, tekst)."""
import re
from pathlib import Path

import geopandas as gpd
import numpy as np
import pandas as pd

from config import RAW, OMI_DIR

X = RAW / "x"


def _istat(s: pd.Series) -> pd.Series:
    return s.astype(str).str.strip().str.replace(r"\.0$", "", regex=True).str.zfill(6).str[-6:]


def _num(s: pd.Series) -> pd.Series:
    """Italiaanse notatie (1.234,5 of 64,79%) naar float; '-' en leeg worden NaN."""
    s = s.astype(str).str.strip().str.replace("%", "", regex=False)
    s = s.str.replace(".", "", regex=False).str.replace(",", ".", regex=False)
    return pd.to_numeric(s, errors="coerce")


def _one(pattern: str) -> Path:
    hits = sorted(X.glob(pattern))
    if not hits:
        raise FileNotFoundError(pattern)
    return hits[0]


# ---------------------------------------------------------------- basis
def elenco() -> pd.DataFrame:
    d = pd.read_excel(RAW / "elenco.xlsx", dtype=str, keep_default_na=False, na_values=[""])   # "NA" = Napoli, geen lege waarde
    col = {c.split("\n")[0].strip(): c for c in d.columns}
    out = pd.DataFrame({
        "istat": _istat(d[col["Codice Comune formato alfanumerico"]]),
        "naam": d[col["Denominazione in italiano"]].str.strip(),
        "naam_alt": d[col["Denominazione altra lingua"]].fillna("").str.strip(),
        "catastale": d[col["Codice Catastale del Comune"]].str.strip(),
        "regio_code": d[col["Codice Regione"]].astype(int),
        "regio": d[col["Denominazione Regione"]].str.strip(),
        "prov_code": d[col["Codice dell'Unità territoriale sovracomunale"]].str.strip(),
        "provincie": d[col["Denominazione dell'Unità territoriale sovracomunale"]].str.strip(),
        "prov": d[col["Sigla automobilistica"]].str.strip(),
        "capoluogo": d[col["Flag Comune capoluogo di Provincia/Città metropolitana/libero consorzio"]] == "1",
    })
    return out


def grenzen() -> gpd.GeoDataFrame:
    g = gpd.read_file(_one("limiti/Com*/Com*_WGS84.shp"))
    g["istat"] = _istat(g["PRO_COM_T"])
    g["opp_km2"] = (g.geometry.area / 1e6).round(2)           # bestand is in UTM 32N (meters)
    return g[["istat", "opp_km2", "geometry"]]


def inwoners() -> pd.DataFrame:
    p = _one("p2/*.csv")
    d = pd.read_csv(p, sep=";", skiprows=1, dtype=str, encoding="utf-8-sig")
    tot = "Popolazione al 31 dicembre - Totale"
    start = "Popolazione censita al 1° gennaio - Totale"
    out = pd.DataFrame({"istat": _istat(d["Codice comune"]),
                        "inwoners": pd.to_numeric(d[tot], errors="coerce"),
                        "_start": pd.to_numeric(d[start], errors="coerce")})
    out = out.dropna(subset=["inwoners"])
    out["groei_pct"] = (out["inwoners"] - out["_start"]) / out["_start"] * 100
    return out.drop(columns="_start")


def hoogte() -> pd.DataFrame:
    d = pd.read_excel(RAW / "altimetria.xlsx", header=None, dtype=str)
    d.columns = [str(c).strip() for c in d.iloc[1]]
    d = d.iloc[2:]
    return pd.DataFrame({"istat": _istat(d["PRO_COM"]),
                         "hoogte_m": pd.to_numeric(d["ALT CENTR MUN"], errors="coerce"),
                         "hoogteverschil_m": pd.to_numeric(d["RANGE"], errors="coerce")})


# ---------------------------------------------------------------- indicatoren
def afval() -> pd.DataFrame:
    d = pd.read_csv(RAW / "rifiuti.csv", sep=";", skiprows=1, dtype=str, encoding="latin-1", index_col=False)
    d.columns = [c.strip() for c in d.columns]
    code = d["IstatComune"].str.strip()
    out = pd.DataFrame({"istat": code.str[-6:],
                        "afval_gescheiden_pct": _num(d["Percentuale RD (%)"])})
    # 'Vedi aggregazione: ...' = alleen een cijfer voor een samenwerkingsverband -> ontbreekt
    return out.dropna(subset=["istat"])


def inkomen() -> pd.DataFrame:
    d = pd.read_csv(_one("irpef/*.csv"), sep=";", dtype=str, encoding="latin-1")
    n = pd.to_numeric(d["Numero contribuenti"], errors="coerce")
    bedrag = pd.to_numeric(d["Reddito imponibile - Ammontare in euro"], errors="coerce")
    return pd.DataFrame({"istat": _istat(d["Codice Istat Comune"]),
                         "inkomen_eur": (bedrag / n).round(0)})


def buitenlanders() -> pd.DataFrame:
    d = pd.read_csv(_one("strasa/*.csv"), sep=";", skiprows=1, dtype=str, encoding="utf-8-sig")
    d["n"] = pd.to_numeric(d["Maschi"], errors="coerce").fillna(0) + pd.to_numeric(d["Femmine"], errors="coerce").fillna(0)
    d = d[d["Età"].astype(str).str.strip() == "999"]     # 999 = totaal over alle leeftijden
    g = d.groupby(_istat(d["Codice comune"]))["n"].sum()
    return g.rename("buitenlanders_n").reset_index().rename(columns={"Codice comune": "istat"})


def apotheken(grens=None) -> pd.DataFrame:
    """Actieve apotheken per comune. Gekoppeld op coördinaten (binnen de huidige grenzen), want het
    bestand gebruikt voor gefuseerde comuni nog de oude codes; zonder coördinaten op code."""
    d = pd.read_csv(RAW / "salute" / "farmacie.csv", sep=";", dtype=str, encoding="latin-1")
    d = d[d["data_fine_validita"].str.strip() == "-"].copy()
    d["istat"] = _istat(d["cod_comune"])
    lat, lon = _num(d["latitudine"].str.replace(".", ",", regex=False)), _num(d["longitudine"].str.replace(".", ",", regex=False))
    if grens is not None:
        ok = lat.between(35, 48) & lon.between(6, 19)
        pts = gpd.GeoDataFrame(d[ok], geometry=gpd.points_from_xy(lon[ok], lat[ok]), crs=4326).to_crs(grens.crs)
        j = gpd.sjoin(pts, grens[["istat", "geometry"]], predicate="within")
        d.loc[j.index, "istat"] = j["istat_right"].values
    g = d.groupby("istat").size()
    return g.rename("apotheken_n").reset_index()


def breedband() -> pd.DataFrame:
    d = pd.read_csv(RAW / "agcom" / "comuni.csv", sep=";", dtype=str, encoding="utf-8-sig")
    return pd.DataFrame({"istat": _istat(d["pro_com"]),
                         "glasvezel_pct": _num(d["Copertura FTTH DESI"])})


SCHOOL_HOOG = re.compile(r"^(LICEO|IST(ITUTO)? ?(TEC|PROF|SUPERIORE|MAGISTRALE|D'ARTE)|IST TEC|IST PROF|SCUOLA MAGISTRALE)")


def scholen_per_comune() -> pd.DataFrame:
    """Aantal staatsscholen per niveau per comune (MIM). Het bestand heeft geen coördinaten."""
    d = pd.read_csv(RAW / "scuole.csv", dtype=str)
    t = d["DESCRIZIONETIPOLOGIAGRADOISTRUZIONESCUOLA"].str.strip()
    niveau = np.select(
        [t == "SCUOLA PRIMARIA", t == "SCUOLA PRIMO GRADO", t.str.match(SCHOOL_HOOG)],
        ["basis", "midden", "hoog"], default="")
    d = d.assign(niveau=niveau)
    d = d[d.niveau != ""]
    p = d.pivot_table(index="CODICECOMUNESCUOLA", columns="niveau", values="CODICESCUOLA",
                      aggfunc="nunique", fill_value=0)
    p = p.reindex(columns=["basis", "midden", "hoog"], fill_value=0).reset_index()
    return p.rename(columns={"CODICECOMUNESCUOLA": "catastale"})


ZIEKENHUIS_TYPES_UIT = {"5.1", "5.2"}   # case di cura (privéklinieken): doorgaans geen spoedeisende hulp


def ziekenhuizen_salute() -> pd.DataFrame:
    """Ministero della Salute, 'Strutture di ricovero attive' (xlsx, handmatig in data/raw/salute/).

    Eén rij per vestiging: bij ziekenhuizen met meerdere vestigingen staan die als 'struttura interna'.
    Geen coördinaten; build.py koppelt ze aan OpenStreetMap.
    """
    hits = sorted((RAW / "salute").glob("*trutture*ricovero*.xlsx"))
    if not hits:
        raise FileNotFoundError("Zet het bestand 'Strutture di ricovero attive' (xlsx) in data/raw/salute/")
    d = pd.read_excel(hits[-1], sheet_name=0, header=1, dtype=str)
    d = d.apply(lambda c: c.str.strip() if c.dtype == object else c)
    soort = pd.read_excel(hits[-1], sheet_name=1, dtype=str)
    code = dict(zip(soort.iloc[:, 1].str.strip().str.lower(), soort.iloc[:, 0].str.strip()))
    d["tipo"] = d["Descrizione tipo struttura"].str.lower().map(
        lambda x: next((v for k, v in code.items() if k[:25] == str(x)[:25]), ""))
    d = d[~d["tipo"].isin(ZIEKENHUIS_TYPES_UIT) & ~d["Descrizione tipo struttura"].str.lower().str.startswith("casa di cura")]
    intern = d["Denominazione struttura interna"].notna()
    out = pd.DataFrame({
        "naam": d["Denominazione struttura interna"].where(intern, d["Denominazione struttura"]),
        "adres": d["Indirizzo struttura interna"].where(intern, d["Indirizzo"]),
        "istat": _istat(d["Codice Comune struttura interna"].where(intern, d["Codice Comune"])),
        "tipo": d["Descrizione tipo struttura"],
    })
    return out.drop_duplicates(subset=["naam", "istat"]).reset_index(drop=True)


def zwemwater() -> pd.DataFrame:
    p = next((RAW / "x" / "bwd").rglob("*.xlsx"), None) or next((RAW / "eea").rglob("*.xlsx"))
    d = pd.read_excel(p, sheet_name=0)
    d = d[d.countryCode == "IT"]
    d = d[d.season == d.season.max()]
    soort = d.bathingWaterType.map({"coastalBathingWater": "zee", "transitionalBathingWater": "zee",
                                    "lakeBathingWater": "meer"})
    d = d.assign(soort=soort).dropna(subset=["soort", "lat", "lon"])
    return d[["bathingWaterIdentifier", "bathingWaterName", "soort", "quality", "lat", "lon"]]


def pm25_op_punten(lat: np.ndarray, lon: np.ndarray) -> np.ndarray:
    import rasterio
    from pyproj import Transformer
    tif = next((RAW / "x" / "pm25").rglob("*.tif"), None) or next((RAW / "eea").rglob("pm25*.tif"))
    with rasterio.open(tif) as r:
        tr = Transformer.from_crs("EPSG:4326", r.crs, always_xy=True)
        xs, ys = tr.transform(lon, lat)
        vals = np.array([v[0] for v in r.sample(zip(xs, ys))], dtype=float)
        nod = r.nodata
    if nod is not None:
        vals[vals == nod] = np.nan
    vals[vals < 0] = np.nan
    return np.round(vals, 1)


# ---------------------------------------------------------------- OMI
def omi() -> pd.DataFrame:
    """Mediaan koop- en huurprijs (€/m²) voor woningen per comune, uit de OMI-csv's in data/raw/omi.

    Verwacht het standaardformaat van de 'Forniture dati OMI' (bestanden *_VALORI.csv, puntkomma,
    eerste regel een titel). Neemt het nieuwste semester per comune; mediaan over de woonzones
    (landelijke zones alleen als er geen andere zijn), van het midden van min en max per zone.
    """
    files = sorted(OMI_DIR.glob("**/*VALORI*.csv")) or sorted(OMI_DIR.glob("**/*.csv"))
    if not files:
        print("  ! geen OMI-bestanden in data/raw/omi: koop_m2 en huur_m2 blijven leeg")
        return pd.DataFrame(columns=["istat", "koop_m2", "huur_m2", "omi_semester"])
    frames = []
    for f in files:
        raw = f.read_bytes().decode("latin-1")
        first = raw.splitlines()[0]
        skip = 0 if "Comune_ISTAT" in first or "Comune_amm" in first else 1
        d = pd.read_csv(f, sep=";", skiprows=skip, dtype=str, encoding="latin-1")
        d.columns = [c.strip() for c in d.columns]
        m = re.search(r"(20\d\d)[_ ]?([12])", f.name)
        d["omi_semester"] = f"{m.group(1)}-{m.group(2)}" if m else f.stem
        frames.append(d)
    d = pd.concat(frames, ignore_index=True)
    need = {"Comune_ISTAT", "Descr_Tipologia", "Compr_min", "Compr_max", "Loc_min", "Loc_max"}
    missing = need - set(d.columns)
    if missing:
        raise RuntimeError(f"OMI-bestanden hebben een ander formaat dan verwacht; ontbrekende kolommen: {missing}")
    woning = d["Descr_Tipologia"].str.strip().str.lower().isin(
        ["abitazioni civili", "abitazioni di tipo economico", "ville e villini", "abitazioni signorili"])
    d = d[woning].copy()
    if "Stato" in d.columns:
        norm = d["Stato"].str.strip().str.upper() == "NORMALE"
        d = d[norm] if norm.any() else d
    # OMI gebruikt deels verouderde ISTAT-codes (o.a. Monza-Brianza nog onder Milaan); de kadastercode
    # (Comune_amm, bijv. A087) is betrouwbaar en staat ook in de ISTAT-lijst.
    kat = dict(zip(elenco()["catastale"], elenco()["istat"])) if "Comune_amm" in d.columns else {}
    d["istat"] = [kat.get(str(a).strip(), c) for a, c in zip(d.get("Comune_amm", d["Comune_ISTAT"]), _istat(d["Comune_ISTAT"]))]
    d["koop"] = ((_num(d["Compr_min"]) + _num(d["Compr_max"])) / 2).where(lambda v: v > 0)
    d["huur"] = ((_num(d["Loc_min"]) + _num(d["Loc_max"])) / 2).where(lambda v: v > 0)
    # landelijke zones (fascia R) alleen gebruiken als een comune geen andere woonzones heeft
    if "Fascia" in d.columns:
        rur = d["Fascia"].str.strip().str.upper() == "R"
        heeft_ander = (~rur).astype(int).groupby(d["istat"]).transform("max").astype(bool)
        d = d[~rur | ~heeft_ander]
    nieuwste = d.groupby("istat")["omi_semester"].transform("max")
    d = d[d["omi_semester"] == nieuwste]
    g = d.groupby("istat").agg(koop_m2=("koop", "median"), huur_m2=("huur", "median"),
                               koop_laagste_zone=("koop", "min"),
                               omi_semester=("omi_semester", "first")).reset_index()
    g["koop_laagste_zone"] = g["koop_laagste_zone"].round(0)
    g["koop_m2"] = g["koop_m2"].round(0)
    g["huur_m2"] = g["huur_m2"].round(1)
    return g
