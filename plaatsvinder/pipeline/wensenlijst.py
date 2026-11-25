"""Wensenlijst (opdracht sectie 14): per gemeente welke dealbreakers en must-haves haar laten afvallen.

Elk item in config/wensenlijst.json heeft een regel die beschrijft wanneer een gemeente afvalt. Het resultaat is
een bitmasker per gemeente (bit i = items[i] laat de gemeente afvallen). De site leest alleen dat masker.
Ontbrekende data sluit niet uit. Items met status "gepland" krijgen nooit een bit.
"""
import json

import numpy as np
import pandas as pd

from config import ROOT


def cfg() -> dict:
    return json.loads((ROOT / "config/wensenlijst.json").read_text(encoding="utf-8"))


def _regel(t: pd.DataFrame, r: dict) -> pd.Series:
    """True = gemeente valt af. NaN in de kolom geeft False."""
    if "of" in r:
        uit = pd.Series(False, index=t.index)
        for sub in r["of"]:
            uit |= _regel(t, sub)
        return uit
    k, op, w = r["kolom"], r["op"], r["waarde"]
    if k not in t:
        return pd.Series(False, index=t.index)
    v = t[k]
    bekend = v.notna()
    if op == "in":
        return bekend & v.isin(w)
    if op == "==":
        if isinstance(w, bool):                   # ook "True"/"False" uit een csv
            v = v.map(lambda x: x if isinstance(x, bool) else str(x) == "True")
        return bekend & (v == w)
    num = pd.to_numeric(v, errors="coerce")
    bekend = num.notna()
    if op == "top_pct":                           # hoogste w % van Italië
        grens = num.quantile(1 - w / 100)
        return bekend & (num > grens)
    if op == "factor_gemiddelde":                 # hoger dan w keer het landelijk gemiddelde (gewogen naar inwoners)
        inw = pd.to_numeric(t["inwoners"], errors="coerce")
        ok = bekend & inw.notna()
        gem = (num[ok] * inw[ok]).sum() / inw[ok].sum()
        return bekend & (num > w * gem)
    vergelijk = {">": num > w, ">=": num >= w, "<": num < w, "<=": num <= w}[op]
    return bekend & vergelijk


def actieve_items() -> list[dict]:
    return [i for i in cfg()["items"] if i["status"] == "actief"]


def masker(t: pd.DataFrame) -> pd.Series:
    m = pd.Series(0, index=t.index, dtype="int64")
    for bit, it in enumerate(cfg()["items"]):
        if it["status"] != "actief":
            continue
        m += _regel(t, it["regel"]).astype("int64") * (1 << bit)
    return m


def rapport(t: pd.DataFrame) -> list[dict]:
    """Per item: hoeveel gemeenten afvallen en hoeveel er geen data hebben."""
    uit = []
    for it in cfg()["items"]:
        if it["status"] != "actief":
            uit.append({"id": it["id"], "lijst": it["lijst"], "label": it["label"], "af": None, "zonder_data": None})
            continue
        af = int(_regel(t, it["regel"]).sum())
        kols = [s["kolom"] for s in it["regel"].get("of", [it["regel"]])]
        zonder = int(t[[k for k in kols if k in t]].isna().all(axis=1).sum()) if any(k in t for k in kols) else len(t)
        uit.append({"id": it["id"], "lijst": it["lijst"], "label": it["label"], "af": af, "zonder_data": zonder})
    return uit
