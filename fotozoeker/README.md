# Fotozoeker

Doorzoek de fotomappen op je laptop op wat er op de foto staat, in gewone taal.
Bijvoorbeeld "mensen die aan tafel eten", "zonsondergang boven zee" of
"kinderen in de sneeuw".

Het werkt met CLIP, een AI-model dat foto's en tekst in dezelfde "betekenisruimte"
plaatst. Elke foto wordt één keer geanalyseerd (de index), daarna is zoeken
binnen een paar seconden klaar. Je kunt zoeken in het Nederlands, Engels,
Italiaans en zo'n 50 andere talen.

Alles draait op je eigen computer. Je foto's gaan niet naar internet. Alleen de
eerste keer worden de modellen gedownload (ongeveer 1 GB).

## Installeren als app (eenmalig)

1. Zet deze map **fotozoeker** op een vaste plek, bijvoorbeeld in Documenten.
   Het icoon op je bureaublad verwijst naar deze map, dus verplaats hem daarna niet meer.
2. Dubbelklik op het installatiebestand:
   - **Windows:** `Fotozoeker installeren (Windows).bat`
   - **Mac:** `Fotozoeker installeren (Mac).command`
3. Er opent een zwart venster dat alles installeert. De eerste keer duurt dat
   5 tot 15 minuten (ongeveer 1,5 GB downloaden). Daarna staat **Fotozoeker** op je
   bureaublad en start de app vanzelf.

Heb je nog geen Python? Op Windows probeert het installatiebestand Python zelf te
installeren. Lukt dat niet, of zit je op een Mac, dan opent de downloadpagina van
python.org. Installeer Python 3.12 (op Windows "Add python.exe to PATH" aanvinken)
en dubbelklik daarna nog een keer op het installatiebestand.

**Mac: "kan niet worden geopend"?** macOS blokkeert bestanden van internet. Klik met
de rechtermuisknop (of ctrl-klik) op het installatiebestand en kies **Open**, en
daarna nog een keer **Open**. Vraagt de app later om toegang tot je map Afbeeldingen
of Documenten, klik dan op **Sta toe**.

## De app gebruiken

1. Klik op **Map toevoegen...** en kies de map(pen) met je foto's.
2. Klik op **Foto's scannen**. Elke foto wordt één keer geanalyseerd: op een gewone
   laptop zo'n 5 tot 20 foto's per seconde, dus 10.000 foto's kost ongeveer een
   kwartier tot een half uur. Je kunt tussendoor stoppen en later verder gaan.
   Heb je nieuwe foto's? Klik nog een keer op scannen, dan worden alleen de nieuwe bekeken.
3. Typ wat je zoekt, bijvoorbeeld *mensen die aan tafel eten*, en druk op Enter.

- Klik op een foto om hem te openen, rechtsklik om hem in zijn map te tonen.
- Bij **Liever niet** vul je in wat je juist niet wilt zien, bijvoorbeeld *restaurant*.
- Met **Treffers kopiëren naar map...** zet je alle treffers bij elkaar in één map.

## Gebruik via de terminal (optioneel)

Alles kan ook zonder venster. Activeer eerst de omgeving
(`source venv/bin/activate` op Mac, `venv\Scripts\activate` op Windows):

```
python fotozoeker.py index ~/Pictures "D:\Foto's 2019"
python fotozoeker.py zoek "mensen die aan tafel eten" --niet "restaurant" --top 60
python fotozoeker.py zoek "hond op het strand" --map ~/Pictures/2023 --kopieer ~/Desktop/honden
python fotozoeker.py opruimen
```

De terminal opent de treffers als pagina in je browser.

## Tips voor goede zoekopdrachten

- Beschrijf wat je zou **zien**: "een groep mensen rond een gedekte tafel met
  borden en glazen" werkt beter dan "gezellig etentje".
- Probeer ook eens Engels. Het beeldmodel is in het Engels getraind en dat geeft
  soms net iets betere resultaten.
- De score zegt vooral iets over de volgorde. Alles boven ongeveer 0,25 is
  meestal een goede treffer, maar dat verschilt per zoekopdracht.
- Foto's in de Apple Foto's-app zitten in een speciale bibliotheek. Exporteer ze
  eerst naar een gewone map, of kies in de app de map `Afbeeldingen` en geef
  Fotozoeker toegang als macOS daarom vraagt.
- Gezichten herkennen ("foto's van oma") kan dit model niet. Het begrijpt wat er
  gebeurt, niet wie er op de foto staat.

## Waar staat wat

- De index: `~/.fotozoeker/index.sqlite` (een paar MB per 10.000 foto's)
- De gekozen fotomappen: `~/.fotozoeker/instellingen.json`
- De programmaonderdelen en AI-modellen: de map `venv` hier en `~/.cache/huggingface`

Verwijderen: gooi het icoon op je bureaublad, deze map, de map `.fotozoeker` in je
gebruikersmap en `~/.cache/huggingface` weg.

Ondersteunde formaten: JPG, PNG, WebP, HEIC (iPhone), GIF, BMP, TIFF.
RAW-bestanden (.CR2, .NEF, .ARW) worden niet gelezen.
