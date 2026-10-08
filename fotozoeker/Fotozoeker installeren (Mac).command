#!/bin/bash
# Dubbelklik op dit bestand om Fotozoeker te installeren.
cd "$(dirname "$0")" || exit 1
DIR="$(pwd)"
echo
echo "  Fotozoeker installeren"
echo "  ======================"
echo

stop() {
  echo
  echo "  $1"
  echo
  read -r -p "  Druk op Enter om dit venster te sluiten."
  exit 1
}

# Zoek een Python met tkinter (de versie van python.org heeft dat altijd)
PY=""
for v in 3.12 3.13 3.11 3.10 3.14; do
  for p in "/Library/Frameworks/Python.framework/Versions/$v/bin/python3" \
           "/opt/homebrew/bin/python$v" "/usr/local/bin/python$v"; do
    if [ -x "$p" ] && "$p" -c "import tkinter" 2>/dev/null; then
      PY="$p"
      break 2
    fi
  done
done
if [ -z "$PY" ]; then
  open "https://www.python.org/downloads/macos/"
  stop "Python staat nog niet op deze Mac. Download en installeer Python 3.12 via de
  website die nu opent (de 'macOS 64-bit universal2 installer'). Dubbelklik daarna
  nog een keer op dit bestand."
fi

echo "  Python gevonden. De onderdelen worden nu geïnstalleerd."
echo "  De eerste keer duurt dit 5 tot 15 minuten (ongeveer 1,5 GB downloaden)."
echo
[ -x venv/bin/python ] || "$PY" -m venv venv || stop "Kon geen Python-omgeving maken."
venv/bin/python -m pip install --upgrade pip --quiet
venv/bin/python -m pip install -r requirements.txt || stop "Installeren mislukt. Controleer je internetverbinding en probeer het opnieuw."

echo
echo "  AI-modellen downloaden..."
venv/bin/python -c "import fotozoeker as f; f.laad_model(f.BEELDMODEL); f.laad_model(f.TEKSTMODEL)" \
  || stop "Downloaden van de modellen mislukt. Probeer het opnieuw."

echo
echo "  App op het bureaublad zetten..."
APP="$HOME/Desktop/Fotozoeker.app"
rm -rf "$APP"
mkdir -p "$APP/Contents/MacOS" "$APP/Contents/Resources"
cp icoon.icns "$APP/Contents/Resources/icoon.icns"
cat > "$APP/Contents/Info.plist" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0"><dict>
  <key>CFBundleName</key><string>Fotozoeker</string>
  <key>CFBundleDisplayName</key><string>Fotozoeker</string>
  <key>CFBundleIdentifier</key><string>nl.fotozoeker.app</string>
  <key>CFBundleExecutable</key><string>Fotozoeker</string>
  <key>CFBundleIconFile</key><string>icoon</string>
  <key>CFBundlePackageType</key><string>APPL</string>
  <key>CFBundleVersion</key><string>1.0</string>
</dict></plist>
PLIST
printf '#!/bin/bash\ncd %q || exit 1\nexec ./venv/bin/python app.py\n' "$DIR" > "$APP/Contents/MacOS/Fotozoeker"
chmod +x "$APP/Contents/MacOS/Fotozoeker"
touch "$APP"

echo
echo "  Klaar! Je vindt Fotozoeker nu op je bureaublad. De app wordt gestart."
open "$APP"
sleep 2
