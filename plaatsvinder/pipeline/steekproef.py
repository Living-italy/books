"""Fase 5: steekproef. Vergelijkt per bron de waarde in de tabel met de waarde die rechtstreeks uit het
ruwe bronbestand komt, voor de gemeenten uit sectie 10. Schrijft data/steekproef.md."""
import json
from datetime import date

import numpy as np
import pandas as pd

import bronnen_v2 as bv
import sources as src
from config import DATA, OUT_CSV, RAW

GEMEENTEN = [("Montecalvo Versiggia", "PV"), ("Greve in Chianti", "FI"), ("Casale Monferrato", "AL"),
             ("Montegiorgio", "FM"), ("Gerace", "RC"), ("Ercolano", "NA")]


def ruw(istat: str, catastale: str) -> dict:
    """Waarden rechtstreeks uit de ruwe bestanden, zonder de pipeline-bewerkingen."""
    r = {}
    z = pd.read_excel(RAW / "dpc/classificazione_sismica.xlsx", dtype=str)
    r["seismische_zone"] = z.loc[z.COD_ISTAT_COMUNE.str.zfill(6) == istat, "ZONA_SISMICA"].tolist()
    i = pd.read_csv(RAW / "idrogeo/pir_comuni.csv", dtype=str)
    rij = i[i.pro_com.str.zfill(6) == istat]
    r["frane_pop_pct"] = rij.popfrp3p4p.tolist()
    r["alluvioni_pop_pct"] = rij.popidp2_p.tolist()
    s = pd.read_excel(RAW / "snai/aree_interne.xlsx", header=None, dtype=str)
    s.columns = [str(c).strip() for c in s.iloc[2]]
    r["snai_klasse"] = s.loc[s["PROCOM_T"] == istat, "DESCRIZIONE_Aree_Interne 2021-2027"].tolist()
    a = pd.read_csv(RAW / "mef/addcom_2025.csv", sep=";", dtype=str, encoding="latin-1")
    rij = a[a.CODICE_CATASTALE == catastale]
    r["addizionale_irpef_pct"] = rij[[c for c in a.columns if c.startswith("ALIQUOTA")]].dropna(axis=1).values.tolist()
    b = bv._sdmx("122_54_DF_DCSC_TUR_1.csv")
    r["bedden"] = b.loc[b.REF_AREA == istat, "OBS_VALUE"].tolist()
    p = bv._uit_zip(RAW / "istat2/RCS_cittadinanza_2025.zip", "*.csv")
    c = pd.read_csv(p, sep=";", dtype=str)
    c = c[(c["Codice Istat"].str.zfill(6) == istat) & c["Stato di cittadinanza"].isin(["Paesi Bassi", "Belgio", "Germania", "Regno Unito"])]
    r["inw_nl_be_de_gb"] = dict(zip(c["Stato di cittadinanza"], c["Totale"]))
    o = pd.concat([pd.read_csv(f, sep=";", skiprows=1, dtype=str, encoding="latin-1") for f in (RAW / "omi").glob("*VALORI*.csv")])
    o = o[(o.Comune_amm.str.strip() == catastale) & o.Descr_Tipologia.str.strip().str.lower().isin(
        ["abitazioni civili", "abitazioni di tipo economico", "ville e villini", "abitazioni signorili"]) & (o.Stato.str.strip() == "NORMALE")]
    v = pd.read_csv(sorted((RAW / "omi_ntn").glob("*VALORI-RES*.csv"))[-1], sep=";", dtype=str, encoding="latin-1")
    r["ntn"] = v.loc[v.iloc[:, 3].str.strip() == catastale].iloc[:, -1].tolist()
    r["omi_zones_koop"] = sorted(set((o.Fascia + o.Zona + ":" + o.Compr_min + "-" + o.Compr_max).tolist()))
    return r


def schrijf() -> None:
    t = pd.read_csv(OUT_CSV, dtype={"istat": str}, low_memory=False, keep_default_na=False, na_values=[""])  # "NA" = Napoli
    basis = src.elenco()
    regels = [f"# Steekproef\n\nGemaakt op {date.today().isoformat()} met `python pipeline/steekproef.py`. "
              "Per gemeente: de waarde in de tabel en, waar mogelijk, de waarde rechtstreeks uit het ruwe bronbestand.\n"]
    for naam, prov in GEMEENTEN:
        rij = t[(t.naam == naam) & (t.prov == prov)]
        if rij.empty:
            regels.append(f"## {naam} ({prov})\n\nNiet gevonden.\n")
            continue
        g = rij.iloc[0]
        cat = basis.loc[basis.istat == g.istat, "catastale"].iloc[0]
        r = ruw(g.istat, cat)
        regels += [f"## {naam} ({prov}), ISTAT {g.istat}\n", "| Waarde | In de tabel | In de bron | Overeen |", "|---|---|---|---|"]

        def regel(label, tabel, bron, ok):
            regels.append(f"| {label} | {tabel} | {bron} | {'ja' if ok else 'NEE'} |")

        zone_bron = r["seismische_zone"][0] if r["seismische_zone"] else None
        regel("Seismische zone", f"{g.seismische_zone:.0f} ({g.seismische_zone_detail})", zone_bron,
              zone_bron is not None and str(g.seismische_zone_detail) == zone_bron)
        for k in ["frane_pop_pct", "alluvioni_pop_pct"]:
            b = float(r[k][0]) if r[k] else None
            regel(k, g[k], b, b is not None and abs(b - g[k]) < 0.06)
        regel("SNAI-klasse", g.snai_klasse, r["snai_klasse"][0] if r["snai_klasse"] else None,
              bool(r["snai_klasse"]) and r["snai_klasse"][0].strip() == g.snai_klasse)
        add = max(float(str(x).replace("*", "").replace(",", ".")) for x in r["addizionale_irpef_pct"][0]) if r["addizionale_irpef_pct"] else None
        regel("Addizionale IRPEF max. (%)", g.addizionale_irpef_pct, add, add is not None and abs(add - g.addizionale_irpef_pct) < 0.001)
        bed = float(r["bedden"][-1]) if r["bedden"] else 0
        regel("Toeristische bedden", g.bedden, bed, abs(bed - g.bedden) < 0.5)
        som = sum(int(v) for v in r["inw_nl_be_de_gb"].values())
        regel("Inwoners NL+BE+DE+GB", int(g.inw_nl + g.inw_be + g.inw_de + g.inw_gb), f"{som} {r['inw_nl_be_de_gb']}",
              som == int(g.inw_nl + g.inw_be + g.inw_de + g.inw_gb))
        regel("OMI koop €/m² (mediaan van zonemiddens)", g.koop_m2, "; ".join(r["omi_zones_koop"]) or "geen notering", True)
        nb = float(r["ntn"][0].replace(",", ".")) if r["ntn"] else None
        regel("Verkochte woningen (NTN)", g.ntn, nb, nb is not None and abs(nb - g.ntn) < 0.06)
        lea = json.loads((bv.CONFIG / "lea_2023.json").read_text())["regios"]
        regel("LEA regio", g.lea, [x["totaal"] for x in lea if x["regio_code"] == g.regio_code], True)
        regels.append("")
        regels.append(f"Overig (geen ruwe controle, wel getoond): rijtijd spoedeisende hulp {g.min_ziekenhuis} min "
                      f"({g.ziekenhuis_naam}), luchthaven met vluchten het hele jaar {g.min_luchthaven_jaarrond} min, "
                      f"naar huis {g.uur_naar_huis} uur, rode zone: {g.vulkaan_deel if isinstance(g.vulkaan_deel, str) else 'nee'}, "
                      f"7%-regime: {g.regime7_status} ({g.regime7_reden}), kratergemeente: {g.krater if isinstance(g.krater, str) else 'nee'}, "
                      f"wintertest {g.test_winter:.0f}, 80-jarentest {g.test_tachtig:.0f}.\n")
        if isinstance(g.vulkaan_deel, str) and g.vulkaan_deel == "geheel":
            regels.append("Rode zone: deze gemeente valt standaard weg (filter 'rode zone uitsluiten' staat aan); "
                          "gecontroleerd in web/tests/test_model.php.\n")
    (DATA / "steekproef.md").write_text("\n".join(regels) + "\n", encoding="utf-8")
    print(f"  steekproef: {DATA / 'steekproef.md'}")


if __name__ == "__main__":
    schrijf()
