import sys
import unittest
from pathlib import Path

import pandas as pd

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
from bronnen_v2 import cfg, in_aanmerking_7procent  # noqa: E402

REGELS = cfg("regime7")
TABEL = pd.DataFrame([
    {"istat": "073001", "regio_code": 16, "inwoners": 25000, "krater": None},   # Puglia, 25.000
    {"istat": "073002", "regio_code": 16, "inwoners": 35000, "krater": None},   # Puglia, 35.000
    {"istat": "052001", "regio_code": 9, "inwoners": 5000, "krater": None},     # Toscane, 5.000
    {"istat": "066049", "regio_code": 13, "inwoners": 69000, "krater": "2009"}, # kratergemeente
    {"istat": "073003", "regio_code": 16, "inwoners": 25000, "krater": None},
])


class TestRegime7(unittest.TestCase):
    def test_puglia_25000_ja(self):
        self.assertEqual(in_aanmerking_7procent("073001", 2027, TABEL, REGELS)["uitkomst"], "ja")

    def test_puglia_35000_nee(self):
        self.assertEqual(in_aanmerking_7procent("073002", 2027, TABEL, REGELS)["uitkomst"], "nee")

    def test_toscane_5000_nee(self):
        self.assertEqual(in_aanmerking_7procent("052001", 2027, TABEL, REGELS)["uitkomst"], "nee")

    def test_kratergemeente_te_verifieren(self):
        u = in_aanmerking_7procent("066049", 2027, TABEL, REGELS)
        self.assertEqual(u["uitkomst"], "te_verifieren")
        self.assertEqual(u["reden"], "Controleer de actuele wettekst")

    def test_oude_grens_voor_2026(self):
        # vóór 2026 gold 20.000 inwoners
        self.assertEqual(in_aanmerking_7procent("073003", 2025, TABEL, REGELS)["uitkomst"], "nee")


if __name__ == "__main__":
    unittest.main()
