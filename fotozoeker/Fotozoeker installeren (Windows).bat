@echo off
setlocal
cd /d "%~dp0"
title Fotozoeker installeren

rem De programmaonderdelen (ongeveer 1,5 GB) komen buiten OneDrive te staan.
set "VENV=%LOCALAPPDATA%\Fotozoeker\venv"

echo.
echo  Fotozoeker installeren
echo  ======================
echo.

rem Zoek Python 3.10 t/m 3.14 met tkinter (niet de Microsoft Store-versie)
set "PY="
for %%v in (3.12 3.13 3.11 3.10 3.14) do (
  if not defined PY (
    py -%%v -c "import tkinter" >nul 2>&1 && set "PY=py -%%v"
  )
)
if not defined PY (
  python -c "import sys, tkinter; sys.exit(0 if (3,10) <= sys.version_info[:2] <= (3,14) and 'WindowsApps' not in sys.executable else 1)" >nul 2>&1 && set "PY=python"
)
if not defined PY goto geenpython

echo  Python gevonden. De onderdelen worden nu geinstalleerd.
echo  De eerste keer duurt dit 5 tot 15 minuten (ongeveer 1,5 GB downloaden).
echo.
if not exist "%VENV%\Scripts\pythonw.exe" (
  %PY% -m venv "%VENV%" || goto fout
)
"%VENV%\Scripts\python.exe" -m pip install --upgrade pip --quiet
"%VENV%\Scripts\python.exe" -m pip install -r requirements.txt || goto fout

echo.
echo  AI-modellen downloaden...
"%VENV%\Scripts\python.exe" -c "import fotozoeker as f; f.laad_model(f.BEELDMODEL); f.laad_model(f.TEKSTMODEL)" || goto fout

echo.
echo  Snelkoppeling op het bureaublad maken...
"%VENV%\Scripts\python.exe" snelkoppeling.py || goto fout

echo.
echo  Klaar! Je vindt Fotozoeker nu op je bureaublad. De app wordt gestart.
start "" "%VENV%\Scripts\pythonw.exe" app.py
timeout /t 5 >nul
exit /b 0

:geenpython
echo  Python staat nog niet op deze computer.
echo.
echo  De installatie van Python 3.12 wordt nu gedownload via je browser.
echo  1. Open het gedownloade bestand python-3.12.10-amd64.exe
echo  2. Vink onderaan "Add python.exe to PATH" aan
echo  3. Klik op "Install Now" en wacht tot het klaar is
echo  4. Dubbelklik daarna nog een keer op dit installatiebestand
echo.
start "" "https://www.python.org/ftp/python/3.12.10/python-3.12.10-amd64.exe"
pause
exit /b 1

:fout
echo.
echo  Er ging iets mis tijdens het installeren. Zie de meldingen hierboven.
echo  Maak een foto of screenshot van dit venster en stuur die naar Claude.
echo.
pause
exit /b 1
