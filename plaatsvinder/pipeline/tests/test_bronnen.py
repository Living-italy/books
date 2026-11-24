"""Elke waarde die de site toont, heeft een bron met peildatum en schaalniveau."""
import json
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
RISICO = ["seismische_zone", "frane_pop_pct", "alluvioni_pop_pct", "bosbrand_pct", "vulkaan", "regime7_status"]


class TestBronnen(unittest.TestCase):
    def test_elke_getoonde_waarde_heeft_een_bron(self):
        meta = json.loads((ROOT / "web/data/meta.json").read_text(encoding="utf-8"))
        bronnen = json.loads((ROOT / "config/bronnen.json").read_text(encoding="utf-8"))["bronnen"]
        crit = json.loads((ROOT / "config/criteria.json").read_text(encoding="utf-8"))["criteria"]
        kolommen = [i["kolom"] for c in crit for i in c["indicatoren"]] + RISICO
        for k in kolommen:
            with self.subTest(kolom=k):
                bid = meta["bron_van"].get(k)
                self.assertIsNotNone(bid, f"{k} heeft geen bron")
                for veld in ["bron", "peildatum", "schaalniveau"]:
                    self.assertTrue(bronnen[bid].get(veld), f"{k}: bron {bid} mist {veld}")


if __name__ == "__main__":
    unittest.main()
