"""Laders voor de bronnen uit het criteria-onderzoek (fase 2). Elke functie geeft een DataFrame met 'istat'.

Downloads staan in download_v2(); handmatige stappen staan in data/README.md.
"""
import json
import re
import unicodedata
import zipfile
from pathlib import Path

import geopandas as gpd
import numpy as np
import pandas as pd
import requests

from config import RAW, ROOT

CONFIG = ROOT / "config"
UA = {"User-Agent": "plaatsvinder-pipeline/2.0"}
SDMX = "https://esploradati.istat.it/SDMXWS/rest/data/IT1,{flow},1.0/{key}"

DOWNLOADS = {
    "dpc/classificazione_sismica.xlsx": "https://rischi.protezionecivile.it/static/731c04d7fb1e26091a831455d7cfb9cf/classificazione-sismica-aggiornata-maggio-2025.xlsx",
    "snai/aree_interne.xlsx": "https://www.istat.it/wp-content/uploads/2022/07/20220715_Elenco_Comuni_Classi_di_Aree_Interne.xlsx",
    "istat2/POSAS_2026.zip": "https://demo.istat.it/data/posas/POSAS_2026_it_Comuni.zip",
    "istat2/ricostruzione_comuni.zip": "https://demo.istat.it/data/ricostruzione/PopolazioneEta-Territorio-Comuni.zip",
    "istat2/RCS_cittadinanza_2025.zip": "https://demo.istat.it/data/rcs/Dati_RCS_cittadinanza_2025.zip",
    "idrogeo/pir_comuni.csv": "https://idrogeo.isprambiente.it/api/pir/comuni/export?outputFormat=csv",
    "effis/effis_layer.zip": "https://forest-fire.emergency.copernicus.eu/downloads/data.request.form/effis_layer.zip",
    "mef/addcom_2025.csv": "https://www1.finanze.gov.it/finanze2/dipartimentopolitichefiscali/fiscalitalocale/nuova_addcomirpef/download/download.php?anno=2025",
    "assaeroporti/export_2025_12": "https://data-upload.assaeroporti.com/download-export/2025/12",
}
SDMX_DOWNLOADS = {
    "istat_sdmx/122_54_DF_DCSC_TUR_1.csv": ("122_54_DF_DCSC_TUR_1", "A..BEDS.N.ALL.551_553.NAP.ALL.ALL.ALL.TOT", "startPeriod=2025"),
    "istat_sdmx/122_54_DF_DCSC_TUR_3.csv": ("122_54_DF_DCSC_TUR_3", "M..NI.N.ALL.551_553.WORLD.ALL.ALL.ALL.TOT", "startPeriod=2024-01&endPeriod=2024-12"),
    "istat_sdmx/73_67_DF_DCCV_DELITTIPS_9.csv": ("73_67_DF_DCCV_DELITTIPS_9", "all", "startPeriod=2024&endPeriod=2024"),
    "istat_sdmx/DF_DCSS_ABITAZIONI_TV_1.csv": ("DF_DCSS_ABITAZIONI_TV_1", "all", "startPeriod=2021&endPeriod=2021"),
}


def download_v2(force: bool = False) -> None:
    import time
    print("Bronnen criteria-onderzoek downloaden")
    for naam, url in DOWNLOADS.items():
        dest = RAW / naam
        if dest.exists() and not force:
            continue
        dest.parent.mkdir(parents=True, exist_ok=True)
        print(f"  ↓ {naam}")
        with requests.get(url, headers=UA, stream=True, timeout=600) as r:
            r.raise_for_status()
            with open(dest, "wb") as f:
                for c in r.iter_content(1 << 20):
                    f.write(c)
    for naam, (flow, key, q) in SDMX_DOWNLOADS.items():
        dest = RAW / naam
        if dest.exists() and not force:
            continue
        dest.parent.mkdir(parents=True, exist_ok=True)
        print(f"  ↓ {naam} (ISTAT SDMX)")
        r = requests.get(SDMX.format(flow=flow, key=key) + "?" + q, timeout=900,
                         headers={**UA, "Accept": "application/vnd.sdmx.data+csv;version=1.0.0"})
        r.raise_for_status()
        dest.write_bytes(r.content)
        time.sleep(15)   # ISTAT staat maar enkele verzoeken per minuut toe


def _istat(s):
    return s.astype(str).str.strip().str.replace(r"\.0$", "", regex=True).str.zfill(6).str[-6:]


def _uit_zip(zpad: Path, patroon: str) -> Path:
    doel = zpad.with_suffix("")
    if not doel.exists():
        with zipfile.ZipFile(zpad) as z:
            z.extractall(doel)
    return next(doel.rglob(patroon))


def norm(s) -> str:
    s = unicodedata.normalize("NFKD", str(s)).encode("ascii", "ignore").decode().lower()
    return re.sub(r"[^a-z0-9]+", " ", s).strip()


def cfg(naam: str) -> dict:
    return json.loads((CONFIG / f"{naam}.json").read_text(encoding="utf-8"))


# ---------------------------------------------------------------- risico
def seismische_zone() -> pd.DataFrame:
    d = pd.read_excel(RAW / "dpc/classificazione_sismica.xlsx", dtype=str)
    # subzones (2A, 3S, '1-2A') naar het hoogste gevaar: het laagste cijfer
    zone = d["ZONA_SISMICA"].astype(str).map(lambda z: min(int(c) for c in re.findall(r"[1-4]", z)) if re.search(r"[1-4]", z) else np.nan)
    return pd.DataFrame({"istat": _istat(d["COD_ISTAT_COMUNE"]), "seismische_zone": zone,
                         "seismische_zone_detail": d["ZONA_SISMICA"].str.strip()})


def idrogeo() -> pd.DataFrame:
    d = pd.read_csv(RAW / "idrogeo/pir_comuni.csv", dtype=str)
    num = lambda c: pd.to_numeric(d[c], errors="coerce")
    return pd.DataFrame({"istat": _istat(d["pro_com"]),
                         "frane_pop_pct": num("popfrp3p4p").round(1),
                         "alluvioni_pop_pct": num("popidp2_p").round(1)})


def bosbrand(grens: gpd.GeoDataFrame, vanaf: str = "2016-01-01") -> pd.DataFrame:
    shp = _uit_zip(RAW / "effis/effis_layer.zip", "modis.ba.poly.shp")
    b = gpd.read_file(shp, where="COUNTRY='IT'")
    b = b[b["FIREDATE"].astype(str) >= vanaf].to_crs(grens.crs)
    b["geometry"] = b.geometry.buffer(0)
    snede = gpd.overlay(grens[["istat", "geometry"]], b[["geometry"]], how="intersection", keep_geom_type=True)
    ha = snede.assign(ha=snede.area / 1e4).groupby("istat")["ha"].sum()
    opp = grens.set_index("istat").area / 1e4
    pct = (ha.reindex(opp.index).fillna(0) / opp * 100).clip(upper=100).round(2)
    return pct.rename("bosbrand_pct").reset_index()


def zona_rossa(basis: pd.DataFrame) -> pd.DataFrame:
    idx = {(norm(r.naam), r.prov): r.istat for r in basis.itertuples()}
    rijen = {}
    for g in cfg("zona_rossa")["gemeenten"]:
        ist = idx.get((norm(g["naam"]), g["prov"]))
        if ist is None:
            print(f"  ! rode zone: '{g['naam']}' niet gevonden")
            continue
        oud = rijen.get(ist)
        deel = "geheel" if g["deel"] == "geheel" or (oud and oud["vulkaan_deel"] == "geheel") else "deels"
        vul = g["vulkaan"] if not oud or oud["vulkaan"] == g["vulkaan"] else oud["vulkaan"] + " en " + g["vulkaan"]
        rijen[ist] = {"istat": ist, "vulkaan": vul, "vulkaan_deel": deel}
    return pd.DataFrame(list(rijen.values()))


# ---------------------------------------------------------------- levendigheid
def snai() -> pd.DataFrame:
    d = pd.read_excel(RAW / "snai/aree_interne.xlsx", header=None, dtype=str)
    d.columns = [str(c).strip() for c in d.iloc[2]]
    d = d.iloc[3:]
    klasse = d["CODICE_Aree_Interne 2021-2027"].str.strip()
    score = klasse.map({"A": 100, "B": 80, "C": 60, "D": 40, "E": 20, "F": 0})
    return pd.DataFrame({"istat": _istat(d["PROCOM_T"]), "snai_klasse": d["DESCRIZIONE_Aree_Interne 2021-2027"].str.strip(),
                         "snai_score": score})


def leeftijd_2026() -> pd.DataFrame:
    p = _uit_zip(RAW / "istat2/POSAS_2026.zip", "*.csv")
    d = pd.read_csv(p, sep=";", skiprows=1, dtype=str, encoding="utf-8-sig")
    d["n"] = pd.to_numeric(d["Totale"], errors="coerce")
    d["eta"] = pd.to_numeric(d["Età"], errors="coerce")
    d = d[d["eta"].notna() & (d["eta"] < 999)]
    g = d.groupby(_istat(d["Codice comune"]))
    tot = g["n"].sum()
    oud = d[d["eta"] >= 65].groupby(_istat(d.loc[d["eta"] >= 65, "Codice comune"]))["n"].sum()
    return pd.DataFrame({"istat": tot.index, "inwoners_2026": tot.values,
                         "aandeel_65plus": (oud.reindex(tot.index) / tot * 100).round(1).values})


def bevolking_2016() -> pd.DataFrame:
    """Bevolking op 1 januari 2016 per comune (ISTAT, ricostruzione intercensuaria)."""
    _uit_zip(RAW / "istat2/ricostruzione_comuni.zip", "*.csv")
    rijen = []
    for f in sorted((RAW / "istat2/ricostruzione_comuni").glob("*.csv")):
        jaar, blok = None, False
        for regel in f.read_text(encoding="latin-1").splitlines():
            if regel.startswith('"Tutte le cittadinanze - Anno:'):
                jaar = re.search(r"Anno: (\d{4})", regel).group(1)
                blok = jaar == "2016"
                continue
            if regel.startswith('"Cittadinanza'):
                blok = False
            if not blok or ";" not in regel or regel.startswith("Territorio"):
                continue
            delen = regel.split(";")
            if re.fullmatch(r"\d{6}", delen[0].strip('"')):
                # kolommen: code; naam; leeftijd 0 ... 100+ (geen totaalkolom)
                tot = sum(int(x) for x in delen[2:] if x.strip().isdigit())
                rijen.append((delen[0].strip('"'), tot))
    d = pd.DataFrame(rijen, columns=["istat", "inwoners_2016"])
    return d.drop_duplicates("istat")


# ---------------------------------------------------------------- expats
def expats() -> pd.DataFrame:
    p = _uit_zip(RAW / "istat2/RCS_cittadinanza_2025.zip", "*.csv")
    d = pd.read_csv(p, sep=";", dtype=str, encoding="utf-8-sig")
    d["n"] = pd.to_numeric(d["Totale"], errors="coerce").fillna(0)
    landen = {"Paesi Bassi": "nl", "Belgio": "be", "Germania": "de", "Regno Unito": "gb"}
    d = d[d["Stato di cittadinanza"].isin(landen)]
    p = d.pivot_table(index="Codice Istat", columns="Stato di cittadinanza", values="n", aggfunc="sum", fill_value=0)
    p = p.rename(columns=landen).reindex(columns=list(landen.values()), fill_value=0)
    p.columns = [f"inw_{c}" for c in p.columns]
    p = p.reset_index().rename(columns={"Codice Istat": "istat"})
    p["istat"] = _istat(p["istat"])
    return p


# ---------------------------------------------------------------- toerisme, veiligheid, woningen
SARDEGNA_OUD = {"sassari": "090", "nuoro": "091", "cagliari": "092", "oristano": "095", "sud sardegna": "111"}


def provincie_koppeling(elenco_xlsx) -> tuple[dict, pd.DataFrame]:
    """ISTAT-gebiedscode (ITTER107) -> oude provinciecode (indeling 2017-2025), en per comune die oude code.

    ISTAT-tabellen per provincie gebruiken de indeling van vóór de Sardijnse hervorming van 2026.
    """
    e = pd.read_excel(elenco_xlsx, dtype=str, keep_default_na=False, na_values=[""])
    oud = e[[c for c in e.columns if "2017 al 2025" in c][0]].str.zfill(6).str[:3]
    per_comune = pd.DataFrame({"istat": e["Codice Comune formato alfanumerico"], "prov_oud": oud})
    naam_naar_code = {norm(n): c for n, c in zip(e[[c for c in e.columns if c.startswith("Denominazione dell")][0]], oud)
                      if c not in SARDEGNA_OUD.values()}
    naam_naar_code.update(SARDEGNA_OUD)
    naam_naar_code.update({"bolzano bozen": "021", "valle d aosta vallee d aoste": "007", "forli cesena": "040",
                           "massa carrara": "045", "pesaro e urbino": "041", "barletta andria trani": "110",
                           "reggio nell emilia": "035", "reggio di calabria": "080", "monza e della brianza": "108"})
    itter = json.loads((RAW / "istat_sdmx/itter107.json").read_text(encoding="utf-8"))
    code = {k: naam_naar_code.get(norm(v)) for k, v in itter.items() if re.fullmatch(r"IT[A-Z0-9]{3}", k)}
    return {k: v for k, v in code.items() if v}, per_comune


def _sdmx(naam: str) -> pd.DataFrame:
    return pd.read_csv(RAW / "istat_sdmx" / naam, dtype=str)


def bedden() -> pd.DataFrame:
    d = _sdmx("122_54_DF_DCSC_TUR_1.csv")
    d = d[d["TIME_PERIOD"] == d["TIME_PERIOD"].max()]
    return pd.DataFrame({"istat": _istat(d["REF_AREA"]), "bedden": pd.to_numeric(d["OBS_VALUE"], errors="coerce")})


def seizoensindex_provincie(nuts3: dict) -> pd.DataFrame:
    """Aandeel overnachtingen in juni tot en met september, per provincie (ISTAT-code -> oude provinciecode)."""
    d = _sdmx("122_54_DF_DCSC_TUR_3.csv")
    d["v"] = pd.to_numeric(d["OBS_VALUE"], errors="coerce")
    d["m"] = d["TIME_PERIOD"].str[-2:].astype(int)
    d = d[d["REF_AREA"].isin(nuts3)]
    tot = d.groupby("REF_AREA")["v"].sum()
    zomer = d[d["m"].between(6, 9)].groupby("REF_AREA")["v"].sum()
    s = (zomer / tot * 100).round(1)
    return pd.DataFrame({"prov_oud": [nuts3[k] for k in s.index], "seizoen_pct": s.values}).drop_duplicates("prov_oud")


def delitti_provincie(nuts3: dict) -> pd.DataFrame:
    d = _sdmx("73_67_DF_DCCV_DELITTIPS_9.csv")
    d = d[(d["TYPE_CRIME"] == "TOT") & (d["DATA_TYPE"] == "CRIMET")] if "TOT" in set(d["TYPE_CRIME"]) else d
    d = d[d["TIME_PERIOD"] == d["TIME_PERIOD"].max()]
    d = d[d["REF_AREA"].isin(nuts3)]
    if "Y_KNOWN_OFFENDER_IDEN" in d:
        d = d[d["Y_KNOWN_OFFENDER_IDEN"] == d["Y_KNOWN_OFFENDER_IDEN"].mode()[0]]
    v = d.groupby("REF_AREA")["OBS_VALUE"].first().astype(float)
    return pd.DataFrame({"prov_oud": [nuts3[k] for k in v.index], "delitti_100k": v.round(0).values,
                         "delitti_jaar": d["TIME_PERIOD"].max()}).drop_duplicates("prov_oud")


def woningen() -> pd.DataFrame:
    d = _sdmx("DF_DCSS_ABITAZIONI_TV_1.csv")
    d = d[d["INDICATOR"] == "NUM_DW_AV"]
    d = d[d["REF_AREA"].str.fullmatch(r"\d{6}")]
    return pd.DataFrame({"istat": d["REF_AREA"], "woningen": pd.to_numeric(d["OBS_VALUE"], errors="coerce")})


def verkopen(basis: pd.DataFrame) -> pd.DataFrame:
    """OMI, volumi delle compravendite: verkochte woningen (NTN) per comune, nieuwste jaar.
    Handmatig in data/raw/omi_ntn/ (bestand *VALORI-RES.csv), gekoppeld op kadastercode."""
    bestanden = sorted((RAW / "omi_ntn").glob("*VALORI-RES*.csv"))
    if not bestanden:
        print("  ! geen OMI-transacties in data/raw/omi_ntn: herverkoopbaarheid blijft leeg")
        return pd.DataFrame(columns=["istat", "ntn", "ntn_jaar"])
    f = bestanden[-1]
    d = pd.read_csv(f, sep=";", dtype=str, encoding="latin-1")
    jaar = re.search(r"_(20\d\d)_", f.name).group(1)
    kol = f"NTN_{jaar}"
    code = [c for c in d.columns if c.endswith("_CodCom")][0]
    kat = dict(zip(basis.catastale, basis.istat))
    ntn = pd.to_numeric(d[kol].str.replace(",", ".", regex=False), errors="coerce")
    out = pd.DataFrame({"istat": d[code].str.strip().map(kat), "ntn": ntn.round(1), "ntn_jaar": jaar})
    los = out["istat"].isna().sum()
    if los:
        print(f"  ! OMI-transacties: {los} kadastercodes niet gevonden")
    return out.dropna(subset=["istat"])


# ---------------------------------------------------------------- fiscaal
def addizionale(basis: pd.DataFrame) -> pd.DataFrame:
    d = pd.read_csv(RAW / "mef/addcom_2025.csv", sep=";", dtype=str, encoding="latin-1")
    cols = [c for c in d.columns if c.startswith("ALIQUOTA")]
    waarden = d[cols].apply(lambda c: pd.to_numeric(c.str.replace("*", "", regex=False).str.replace(",", ".", regex=False), errors="coerce"))
    kat = dict(zip(basis.catastale, basis.istat))
    return pd.DataFrame({"istat": d["CODICE_CATASTALE"].map(kat), "addizionale_irpef_pct": waarden.max(axis=1).round(2)}).dropna(subset=["istat"])


def lea_per_comune(basis: pd.DataFrame) -> pd.DataFrame:
    rijen = cfg("lea_2023")["regios"]
    per_regio = {r["regio_code"]: r["totaal"] for r in rijen if r["regio_code"]}
    per_prov = {r["provincie_code"]: r["totaal"] for r in rijen if r.get("provincie_code")}
    lea = [per_prov.get(p, per_regio.get(r)) for p, r in zip(basis.prov_code, basis.regio_code)]
    return pd.DataFrame({"istat": basis.istat, "lea": lea})


def kratergemeenten(basis: pd.DataFrame) -> pd.DataFrame:
    k = cfg("kratergemeenten")
    idx = {(norm(r.naam), r.prov): r.istat for r in basis.itertuples()}
    rijen = {}
    for g in k["gemeenten"]:
        nm = k.get("naam_in_istat", {}).get(g["naam"], k.get("fusies", {}).get(g["naam"], g["naam"]))
        ist = idx.get((norm(nm), g["prov"]))
        if ist is None:
            print(f"  ! kratergemeente '{g['naam']}' niet gevonden")
            continue
        rijen.setdefault(ist, set()).add(g["krater"])
    return pd.DataFrame({"istat": list(rijen), "krater": [",".join(sorted(v)) for v in rijen.values()]})


def in_aanmerking_7procent(istat: str, jaar_eerste_optie: int, tabel: pd.DataFrame, regels: dict | None = None) -> dict:
    """Komt een gemeente in aanmerking voor het 7%-regime (art. 24-ter TUIR)?

    tabel: DataFrame met kolommen istat, regio_code, inwoners (1 januari van het jaar vóór de optie), krater.
    Geeft {'uitkomst': 'ja' | 'nee' | 'te_verifieren', 'reden': ...}.
    """
    r = regels or cfg("regime7")
    rij = tabel.loc[tabel["istat"] == istat]
    if rij.empty:
        return {"uitkomst": "nee", "reden": "gemeente onbekend"}
    rij = rij.iloc[0]
    grens = r["inwonersgrens"] if jaar_eerste_optie >= r["inwonersgrens_vanaf_jaar"] else r["inwonersgrens_oud"]
    if isinstance(rij.get("krater"), str) and rij["krater"]:
        if r["inwonersgrens_krater"] == "te_verifieren":
            return {"uitkomst": "te_verifieren", "reden": r["tekst_krater"]}
        ok = rij["inwoners"] <= r["inwonersgrens_krater"]
        return {"uitkomst": "ja" if ok else "nee", "reden": "kratergemeente"}
    if int(rij["regio_code"]) not in r["regio_codes"]:
        return {"uitkomst": "nee", "reden": "regio valt niet onder de regeling"}
    if pd.isna(rij["inwoners"]):
        return {"uitkomst": "te_verifieren", "reden": "inwonertal onbekend"}
    if rij["inwoners"] > grens:
        return {"uitkomst": "nee", "reden": f"meer dan {grens:,} inwoners".replace(",", ".")}
    return {"uitkomst": "ja", "reden": f"regio in het zuiden en hoogstens {grens:,} inwoners".replace(",", ".")}


# ---------------------------------------------------------------- luchthavens
def luchthavens(poi: pd.DataFrame) -> pd.DataFrame:
    """OSM-luchthavens met passagiers (Assaeroporti) en vluchten het hele jaar (handmatig, voorlopig)."""
    c = cfg("luchthavens")
    d = pd.read_excel(RAW / "assaeroporti/export_2025_12", "Passeggeri", header=1)
    d = d[pd.to_numeric(d["N."], errors="coerce").notna()]
    pax = {c["iata_per_naam"].get(str(n).strip()): int(t) for n, t in zip(d["Aeroporto"], d["TOTALE"])}
    l = poi[poi.cat == "luchthaven"].copy()
    l["passagiers"] = l["iata"].map(pax)
    l = l[l["passagiers"] >= c["min_passagiers_lijnvluchten"]]
    l["heel_jaar"] = l["iata"].map(lambda i: c["heel_jaar"].get(i, c["heel_jaar_voorlopig"].get(i)))
    l["heel_jaar_bron"] = l["iata"].map(lambda i: "gecontroleerd" if i in c["heel_jaar"] else
                                        ("voorlopig" if i in c["heel_jaar_voorlopig"] else "onbekend"))
    return l
