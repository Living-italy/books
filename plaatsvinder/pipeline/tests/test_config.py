import copy
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import config_check  # noqa: E402


class TestConfig(unittest.TestCase):
    def test_configuratie_geldig(self):
        self.assertEqual(config_check.controleer(), [])

    def test_gewichten_tellen_op_tot_100(self):
        for p in config_check.laad("profielen")["profielen"]:
            with self.subTest(profiel=p["id"]):
                self.assertEqual(sum(p["gewichten"].values()), 100)

    def test_controle_vangt_verkeerde_som(self):
        orig = config_check.laad
        def nep(naam):
            d = orig(naam)
            if naam == "profielen":
                d = copy.deepcopy(d)
                d["profielen"][0]["gewichten"]["prijs"] += 1
            return d
        config_check.laad = nep
        try:
            self.assertTrue(any("niet 100" in f for f in config_check.controleer()))
        finally:
            config_check.laad = orig


if __name__ == "__main__":
    unittest.main()
