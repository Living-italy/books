"""Schrijft data/dekking.md: per indicator het aantal comuni met data (van alle comuni in de tabel)."""
import json
from datetime import date

import pandas as pd

from config import DATA, OUT_CSV, ROOT


def schrijf() -> None:
    t = pd.read_csv(OUT_CSV, dtype={"istat": str}, low_memory=False, keep_default_na=False, na_values=[""])  # "NA" = Napoli
    crit = json.loads((ROOT / "config/criteria.json").read_text(encoding="utf-8"))
    bronnen = json.loads((ROOT / "config/bronnen.json").read_text(encoding="utf-8"))["bronnen"]
    n = len(t)
    r = [f"# Dekking per bron\n\nGemaakt op {date.today().isoformat()}. Totaal: {n} comuni.\n",
         "| Criterium | Indicator | Bron | Peildatum | Schaalniveau | Comuni met data | % |", "|---|---|---|---|---|---|---|"]
    for c in crit["criteria"]:
        if not c["indicatoren"]:
            r.append(f"| {c['label']} | (geen indicator) | | | | 0 | 0 |")
        for i in c["indicatoren"]:
            b = bronnen.get(i["bron"], {})
            k = t[i["kolom"]].notna().sum() if i["kolom"] in t else 0
            r.append(f"| {c['label']} | {i['label']} | {b.get('bron', i['bron'])} | {b.get('peildatum', '')} | "
                     f"{b.get('schaalniveau', '')} | {k} | {k / n * 100:.1f} |")
    # wensenlijst (sectie 14h): hoeveel gemeenten elk item wegstreept, en wat het doet met de steekproef
    import wensenlijst
    from steekproef import GEMEENTEN as STEEKPROEF
    r += ["", "## Wensenlijst: gemeenten die afvallen per item", "",
          f"Van de {n} gemeenten. Ontbrekende data sluit niet uit. **Vet**: het item streept meer dan de helft weg.", "",
          "| Lijst | Item | Valt af als | Gemeenten af | % | Zonder data |", "|---|---|---|---|---|---|"]
    items = {i["id"]: i for i in wensenlijst.cfg()["items"]}
    for x in wensenlijst.rapport(t):
        it = items[x["id"]]
        if x["af"] is None:
            r.append(f"| {it['lijst']} | {it['label']} | {it['drempel']} | gepland, staat uit | | |")
            continue
        pct = x["af"] / n * 100
        af = f"**{x['af']}**" if pct > 50 else str(x["af"])
        r.append(f"| {it['lijst']} | {it['label']} | {it['drempel']} | {af} | {pct:.1f} | {x['zonder_data']} |")
    sel = pd.Series(False, index=t.index)
    for naam, prov in STEEKPROEF:
        sel |= (t["naam"] == naam) & (t["prov"] == prov)
    st = t[sel]
    r += ["", "### Steekproefgemeenten", "", "Per item: valt de gemeente af (x) of niet (-).", "",
          "| Item | " + " | ".join(st["naam"]) + " |", "|---|" + "---|" * len(st)]
    for it in wensenlijst.actieve_items():
        af = wensenlijst._regel(t, it["regel"])[sel]          # drempels als top 10 % gelden over heel Italië
        r.append(f"| {it['label']} ({it['lijst']}) | " + " | ".join("x" if a else "-" for a in af) + " |")
    (DATA / "dekking.md").write_text("\n".join(r) + "\n", encoding="utf-8")
    print(f"  dekking: {DATA / 'dekking.md'}")


if __name__ == "__main__":
    schrijf()
