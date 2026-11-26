"""Eigenschappen (config/eigenschappen.json): per gemeente het behaalde niveau en het percentiel per eigenschap.

Niveau i (1..n) is gehaald als de waarde aan de drempel van niveau i voldoet ('lager': <=, 'hoger': >=).
Het behaalde niveau is het hoogste gehaalde niveau (0 = geen enkel). Ontbrekende waarde: geen niveau.
"""
import json

import numpy as np
import pandas as pd

from config import ROOT


def cfg() -> dict:
    return json.loads((ROOT / "config/eigenschappen.json").read_text(encoding="utf-8"))


def afgeleid(t: pd.DataFrame) -> pd.DataFrame:
    """Kolommen die alleen voor de eigenschappen nodig zijn."""
    t = t.copy()
    d, inw = pd.to_numeric(t["delitti_100k"], errors="coerce"), pd.to_numeric(t["inwoners"], errors="coerce")
    ok = d.notna() & inw.notna()
    gem = (d[ok] * inw[ok]).sum() / inw[ok].sum()
    t["delitti_factor"] = (d / gem).round(2)
    t["water_risico_pct"] = t[["frane_pop_pct", "alluvioni_p3_pop_pct"]].apply(pd.to_numeric, errors="coerce").max(axis=1, skipna=False)
    zone = t.get("vulkaan_zone")
    t["vulkaan_rang"] = np.where(zone == "rood", 0, np.where(zone == "geel", 1, 2))
    t["maffia_rang"] = np.where(pd.to_numeric(t["sciolto_mafia_jaar"], errors="coerce") >= 2025, 0, 1)
    t["regime7_rang"] = t["regime7_status"].map({"nee": 0, "te_verifieren": 1, "ja": 2})
    return t


def niveaus(t: pd.DataFrame) -> pd.DataFrame:
    """Per eigenschap het behaalde niveau (float, NaN zonder data)."""
    uit = {}
    for e in cfg()["eigenschappen"]:
        v = pd.to_numeric(t[e["kolom"]], errors="coerce") if e["kolom"] in t else pd.Series(np.nan, index=t.index)
        n = pd.Series(0.0, index=t.index)
        for i, niv in enumerate(e["niveaus"], start=1):
            ok = v <= niv["waarde"] if e["richting"] == "lager" else v >= niv["waarde"]
            n[ok] = i
        n[v.isna()] = np.nan
        uit[e["id"]] = n
    return pd.DataFrame(uit, index=t.index)


def percentielen(t: pd.DataFrame) -> pd.DataFrame:
    from build import percentiel
    uit = {}
    for e in cfg()["eigenschappen"]:
        v = pd.to_numeric(t[e["kolom"]], errors="coerce") if e["kolom"] in t else pd.Series(np.nan, index=t.index)
        uit[e["id"]] = percentiel(v, e["richting"] == "hoger")
    return pd.DataFrame(uit, index=t.index)


def niveau_tekst(niv: pd.DataFrame) -> pd.Series:
    """Eén teken per eigenschap: '0'..'5', of '-' zonder data."""
    return niv.apply(lambda r: "".join("-" if pd.isna(x) else str(int(x)) for x in r), axis=1)


def rapport(t: pd.DataFrame) -> list[str]:
    """Markdown: per eigenschap en niveau hoeveel gemeenten afvallen als het noodzakelijk is."""
    niv = niveaus(t)
    n = len(t)
    r = ["", "## Eigenschappen: gemeenten die afvallen per niveau (als de eigenschap noodzakelijk is)", "",
         f"Van de {n} gemeenten. Ontbrekende data sluit niet uit. **Vet**: meer dan de helft valt af.", "",
         "| Eigenschap | Niveau | Valt af | % | Zonder data |", "|---|---|---|---|---|"]
    for e in cfg()["eigenschappen"]:
        x = niv[e["id"]]
        for i, nv in enumerate(e["niveaus"], start=1):
            af = int((x < i).sum())
            pct = af / n * 100
            r.append(f"| {e['label']} | {i}. {nv['label']} | {'**' + str(af) + '**' if pct > 50 else af} | {pct:.1f} | {int(x.isna().sum())} |")
    return r
