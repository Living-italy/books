@echo off
setlocal
cd /d "%~dp0"
title Fotozoeker installeren
set "FZ=%CD%"
echo.
echo  Fotozoeker installeren
echo  ======================
echo.

rem Zoek een geschikte Python (met tkinter, versie 3.10 t/m 3.14)
set "PY="
for %%v in (3.12 3.13 3.11 3.10 3.14) do (
  if not defined PY (
    py -%%v -c "import tkinter" >nul 2>&1 && set "PY=py -%%v"
  )
)
if not defined PY (
  python -c "import sys, tkinter; sys.exit(0 if (3,10) <= sys.version_info[:2] <= (3,14) else 1)" >nul 2>&1 && set "PY=python"
)
if not defined PY goto geenpython

echo  Python gevonden. De onderdelen worden nu geinstalleerd.
echo  De eerste keer duurt dit 5 tot 15 minuten (ongeveer 1,5 GB downloaden).
echo.
if not exist "venv\Scripts\pythonw.exe" (
  %PY% -m venv venv || goto fout
)
"venv\Scripts\python.exe" -m pip install --upgrade pip --quiet
"venv\Scripts\python.exe" -m pip install -r requirements.txt || goto fout

echo.
echo  AI-modellen downloaden...
"venv\Scripts\python.exe" -c "import fotozoeker as f; f.laad_model(f.BEELDMODEL); f.laad_model(f.TEKSTMODEL)" || goto fout

echo.
echo  Snelkoppeling op het bureaublad maken...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$d=[Environment]::GetFolderPath('Desktop'); $s=(New-Object -ComObject WScript.Shell).CreateShortcut((Join-Path $d 'Fotozoeker.lnk')); $s.TargetPath=Join-Path $env:FZ 'venv\Scripts\pythonw.exe'; $s.Arguments=[char]34 + (Join-Path $env:FZ 'app.py') + [char]34; $s.WorkingDirectory=$env:FZ; $s.IconLocation=Join-Path $env:FZ 'icoon.ico'; $s.Description='Zoek foto''s op inhoud'; $s.Save()" || goto fout

echo.
echo  Klaar! Je vindt Fotozoeker nu op je bureaublad. De app wordt gestart.
start "" "venv\Scripts\pythonw.exe" "app.py"
timeout /t 5 >nul
exit /b 0

:geenpython
echo  Python staat nog niet op deze computer. Ik probeer het nu te installeren...
echo.
winget install -e --id Python.Python.3.12 --scope user --accept-package-agreements --accept-source-agreements
if errorlevel 1 (
  echo.
  echo  Dat lukte niet automatisch. Installeer Python 3.12 via de website die nu opent.
  echo  Vink bij de installatie "Add python.exe to PATH" aan.
  start "" "https://www.python.org/downloads/windows/"
) else (
  echo.
  echo  Python is geinstalleerd.
)
echo  Dubbelklik daarna nog een keer op dit bestand om verder te gaan.
echo.
pause
exit /b 1

:fout
echo.
echo  Er ging iets mis tijdens het installeren. Zie de meldingen hierboven.
echo  Controleer je internetverbinding en probeer het nog een keer.
echo.
pause
exit /b 1
