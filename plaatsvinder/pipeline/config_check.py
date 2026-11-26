"""Valideert de handmatige configuratie in config/ tegen de schema's en controleert de samenhang.

Gebruik: python pipeline/config_check.py   (stopt met foutcode 1 bij een fout)
"""
import json
import sys
from pathlib import Path

from jsonschema import Draft202012Validator

CONFIG = Path(__file__).resolve().parent.parent / "config"
BESTANDEN = ["criteria", "profielen", "lea_2023", "regime7", "kratergemeenten", "zona_rossa",
             "luchthavens", "trends", "bronnen", "aandachtspunten", "gids_links", "gids_prijzen"]


def laad(naam: str) -> dict:
    return json.loads((CONFIG / f"{naam}.json").read_text(encoding="utf-8"))


def controleer() -> list[str]:
    fouten = []
    cfg = {}
    for naam in BESTANDEN:
        cfg[naam] = laad(naam)
        schema = json.loads((CONFIG / "schema" / f"{naam}.schema.json").read_text(encoding="utf-8"))
        for f in Draft202012Validator(schema).iter_errors(cfg[naam]):
            fouten.append(f"{naam}.json: {'/'.join(map(str, f.path))}: {f.message}")

    crit_ids = {c["id"] for c in cfg["criteria"]["criteria"]}
    bron_ids = set(cfg["bronnen"]["bronnen"])
    for c in cfg["criteria"]["criteria"]:
        for b in c["bronnen"] + [i["bron"] for i in c["indicatoren"]]:
            if b not in bron_ids:
                fouten.append(f"criteria.json: bron '{b}' bij '{c['id']}' staat niet in bronnen.json")
    for p in cfg["profielen"]["profielen"]:
        som = sum(p["gewichten"].values())
        if som != 100:
            fouten.append(f"profielen.json: gewichten van '{p['id']}' tellen op tot {som}, niet 100")
        onbekend = set(p["gewichten"]) - crit_ids
        if onbekend:
            fouten.append(f"profielen.json: '{p['id']}' noemt onbekende criteria {sorted(onbekend)}")
        for v in p.get("vragen", []):
            for o in v["opties"]:
                if "effect" in o:
                    g = dict(p["gewichten"]); g.update(o["effect"])
                    if sum(g.values()) != 100:
                        fouten.append(f"profielen.json: '{p['id']}', optie '{o['id']}' telt op tot {sum(g.values())}")
    for c in cfg["criteria"]["criteria"]:
        w = [i.get("gewicht") for i in c["indicatoren"]]
        if any(x is not None for x in w) and sum(x or 0 for x in w) != 100:
            fouten.append(f"criteria.json: gewichten binnen '{c['id']}' tellen niet op tot 100")
    for r in cfg["lea_2023"]["regios"]:
        if r["prevenzione"] + r["distrettuale"] + r["ospedaliera"] != r["totaal"]:
            fouten.append(f"lea_2023.json: totaal van {r['gebied']} klopt niet")
    # eigenschappen
    eg = laad("eigenschappen")
    ids = [e["id"] for e in eg["eigenschappen"]]
    if len(ids) != len(set(ids)):
        fouten.append("eigenschappen.json: ids niet uniek")
    for e in eg["eigenschappen"]:
        if e["bron"] not in bron_ids:
            fouten.append(f"eigenschappen.json: bron '{e['bron']}' bij '{e['id']}' staat niet in bronnen.json")
        if e["criterium"] not in crit_ids or e["groep"] not in eg["groepen"] or e["richting"] not in ("lager", "hoger"):
            fouten.append(f"eigenschappen.json: criterium, groep of richting bij '{e['id']}'")
        w = [n["waarde"] for n in e["niveaus"]]
        oplopend = all(a < b for a, b in zip(w, w[1:]))
        if not 1 <= len(w) <= 5 or (e["richting"] == "hoger") != oplopend and len(w) > 1:
            fouten.append(f"eigenschappen.json: niveaus van '{e['id']}' lopen niet van soepel naar streng")
    return fouten


if __name__ == "__main__":
    fouten = controleer()
    for f in fouten:
        print("FOUT:", f)
    print("configuratie in orde" if not fouten else f"{len(fouten)} fout(en)")
    sys.exit(1 if fouten else 0)
