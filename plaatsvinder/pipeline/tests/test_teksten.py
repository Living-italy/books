"""Geen gedachtestreepje (em-dash) in teksten van de interface; het korte streepje alleen in de verplichte
bronvermelding «Agenzia delle Entrate – OMI»."""
import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
BESTANDEN = [p for p in list((ROOT / "web").rglob("*")) + list((ROOT / "config").rglob("*.json"))
             if p.is_file() and p.suffix in {".js", ".json", ".php", ".css"} and "data" not in p.parts]


class TestTeksten(unittest.TestCase):
    def test_geen_em_dash(self):
        for p in BESTANDEN:
            with self.subTest(bestand=str(p.relative_to(ROOT))):
                self.assertNotIn("—", p.read_text(encoding="utf-8"))

    def test_en_dash_alleen_in_omi_vermelding(self):
        for p in BESTANDEN:
            tekst = re.sub(r"Agenzia delle Entrate – OMI", "", p.read_text(encoding="utf-8"))
            with self.subTest(bestand=str(p.relative_to(ROOT))):
                self.assertNotIn("–", tekst)


if __name__ == "__main__":
    unittest.main()
