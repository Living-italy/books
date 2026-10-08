"""Zet een Fotozoeker-snelkoppeling op het bureaublad (Windows).

Wordt aangeroepen door het installatiebestand, met de python uit de Fotozoeker-omgeving.
"""

import sys
from pathlib import Path

import win32com.client

hier = Path(__file__).resolve().parent
shell = win32com.client.Dispatch("WScript.Shell")
# SpecialFolders vindt ook een bureaublad dat door OneDrive is verplaatst.
bureaublad = Path(shell.SpecialFolders("Desktop"))
pad = bureaublad / "Fotozoeker.lnk"

lnk = shell.CreateShortcut(str(pad))
lnk.TargetPath = str(Path(sys.executable).with_name("pythonw.exe"))  # zonder zwart venster
lnk.Arguments = f'"{hier / "app.py"}"'
lnk.WorkingDirectory = str(hier)
lnk.IconLocation = str(hier / "icoon.ico")
lnk.Description = "Zoek foto's op inhoud"
lnk.Save()
print(f"  Snelkoppeling gemaakt: {pad}")
