"""Fase 3: deelscores per criterium, wintertest en 80-jarentest.

Percentielen (0-100, laag-is-beter omgedraaid) komen uit build.provinciecijfers_en_percentielen().
Een deelscore is het gemiddelde van de percentielen van de indicatoren met data; zonder data: null.
Binnen een test wordt het gewicht van een ontbrekend onderdeel over de andere onderdelen verdeeld.
"""
import json

import numpy as np
import pandas as pd

from build import INDICATOREN, percentiel
from config import ROOT

KORT = {kol: kort for kol, (_, _, kort) in INDICATOREN.items()}

WINTERTEST = {          # onderdeel: gewicht binnen de test (sectie 7a)
    "snai": 30, "groei_10j": 20, "65plus": 10, "basis_lopen": 20, "zomerpiek": 10, "winter_hoogte": 10,
}
TACHTIGTEST = {         # sectie 7b
    "ziekenhuis": 30, "apotheek_huisarts": 20, "supermarkt_lopen": 15, "ov": 15, "lea": 20,
}


def gewogen(delen: pd.DataFrame, gewichten: dict) -> pd.Series:
    """Gewogen gemiddelde per rij; ontbrekende onderdelen tellen niet mee (gewicht wordt herverdeeld)."""
    w = pd.Series(gewichten)
    waarden = delen[list(gewichten)]
    heeft = waarden.notna()
    som = (waarden.fillna(0) * w).sum(axis=1)
    totaal_w = (heeft * w).sum(axis=1)
    return (som / totaal_w.replace(0, np.nan)).round(0)


def criteria_scores(t: pd.DataFrame) -> pd.DataFrame:
    crit = json.loads((ROOT / "config/criteria.json").read_text(encoding="utf-8"))["criteria"]
    t = t.copy()
    for c in crit:
        cols = [f"p_{KORT[i['kolom']]}" for i in c["indicatoren"] if i["kolom"] in KORT and f"p_{KORT[i['kolom']]}" in t]
        t[f"s_{c['id']}"] = t[cols].mean(axis=1, skipna=True).round(0) if cols else np.nan
    return t


def tests(t: pd.DataFrame) -> pd.DataFrame:
    t = t.copy()
    p = lambda k: t.get(f"p_{k}")
    # wintertest
    zomerpiek = percentiel(t["bedden_per_inw"] * t["seizoen_pct"], hoger_beter=False)
    winter = pd.DataFrame({
        "snai": t["snai_score"],                                  # lineair: polo 100, ultraperiferico 0
        "groei_10j": p("groei_10j"),
        "65plus": p("65plus"),
        "basis_lopen": t["basis_800m"] / 3 * 100,                 # supermarkt, apotheek, bar binnen 800 m
        "zomerpiek": zomerpiek,                                   # hoge zomerpiek t.o.v. inwoners is slechter
        "winter_hoogte": percentiel(t["hoogte_m"], hoger_beter=False),   # geen sneeuw/vorstdata: hoogte als benadering
    })
    t["test_winter"] = gewogen(winter, WINTERTEST)
    for k in WINTERTEST:
        t[f"winter_{k}"] = winter[k].round(0)
    # 80-jarentest
    apo_arts = pd.concat([p("apotheek_rit"), t["huisarts_800m"].map({True: 100.0, False: 0.0})], axis=1).mean(axis=1)
    tachtig = pd.DataFrame({
        "ziekenhuis": p("ziekenhuis"),
        "apotheek_huisarts": apo_arts,
        "supermarkt_lopen": t["supermarkt_800m"].map({True: 100.0, False: 0.0}),
        "ov": p("station"),
        "lea": p("lea"),
    })
    t["test_tachtig"] = gewogen(tachtig, TACHTIGTEST)
    for k in TACHTIGTEST:
        t[f"tachtig_{k}"] = tachtig[k].round(0)
    t["p_zomerpiek"] = zomerpiek
    return t


def bereken(t: pd.DataFrame) -> pd.DataFrame:
    return tests(criteria_scores(t))
