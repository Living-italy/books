"""Schrijft data/dekking.md: per indicator het aantal comuni met data (van alle comuni in de tabel)."""
import json
from datetime import date

import pandas as pd

from config import DATA, OUT_CSV, ROOT


def schrijf() -> None:
    t = pd.read_csv(OUT_CSV, dtype={"istat": str}, low_memory=False)
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
    (DATA / "dekking.md").write_text("\n".join(r) + "\n", encoding="utf-8")
    print(f"  dekking: {DATA / 'dekking.md'}")


if __name__ == "__main__":
    schrijf()
