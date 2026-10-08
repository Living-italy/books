"""Fotozoeker installeren op Windows.

Installeer eerst Python 3.12 van python.org (vink "Add python.exe to PATH" aan)
en dubbelklik daarna op dit bestand.
"""

import os
import subprocess
import sys
import time
import venv
from pathlib import Path

HIER = Path(__file__).resolve().parent
# De programmaonderdelen (ongeveer 1,5 GB) komen buiten OneDrive te staan.
BASIS = Path(os.environ.get("LOCALAPPDATA") or Path.home() / ".local" / "share")
VENV = BASIS / "Fotozoeker" / "venv"
BIN = VENV / ("Scripts" if os.name == "nt" else "bin")
PY = BIN / ("python.exe" if os.name == "nt" else "python")
PYW = BIN / ("pythonw.exe" if os.name == "nt" else "python")


def stap(tekst):
    print(f"\n  {tekst}\n", flush=True)


def draai(*args):
    subprocess.run([str(PY), *args], check=True, cwd=HIER)


def controleer_python():
    if not (3, 10) <= sys.version_info[:2] <= (3, 14):
        raise RuntimeError(
            f"Deze Python-versie ({sys.version.split()[0]}) wordt niet ondersteund. "
            "Installeer Python 3.12 van python.org."
        )
    if "WindowsApps" in sys.executable:
        raise RuntimeError(
            "Dit is de Python uit de Microsoft Store, die werkt hier niet goed. "
            "Installeer Python 3.12 van python.org."
        )
    try:
        import tkinter  # noqa: F401
    except ImportError:
        raise RuntimeError(
            "Python mist het onderdeel tkinter. Installeer Python 3.12 van python.org "
            "opnieuw en laat 'tcl/tk and IDLE' aangevinkt."
        )


def main():
    print("\n  Fotozoeker installeren\n  ======================")
    controleer_python()

    stap("De onderdelen worden nu geïnstalleerd. De eerste keer duurt dit "
         "5 tot 15 minuten (ongeveer 1,5 GB downloaden).")
    if not PY.exists():
        venv.create(VENV, with_pip=True)
    draai("-m", "pip", "install", "--upgrade", "pip", "--quiet")
    draai("-m", "pip", "install", "-r", "requirements.txt")

    stap("AI-modellen downloaden...")
    draai("-c", "import fotozoeker as f; f.laad_model(f.BEELDMODEL); f.laad_model(f.TEKSTMODEL)")

    if os.name == "nt":
        stap("Snelkoppeling op het bureaublad maken...")
        draai("snelkoppeling.py")

    stap("Klaar! Je vindt Fotozoeker nu op je bureaublad. De app wordt gestart.")
    subprocess.Popen([str(PYW), str(HIER / "app.py")], cwd=HIER)
    time.sleep(5)


if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(f"\n  Er ging iets mis: {e}")
        print("  Maak een foto of screenshot van dit venster en stuur die naar Claude.\n")
        input("  Druk op Enter om dit venster te sluiten.")
