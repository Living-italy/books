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

## Installeren (eenmalig)

Je hebt Python 3.9 of nieuwer nodig ([python.org](https://www.python.org/downloads/),
op Windows bij de installatie "Add Python to PATH" aanvinken).

Open een terminal (Mac: Terminal, Windows: PowerShell) in deze map en typ:

```
python -m venv venv
```

Mac/Linux:
```
source venv/bin/activate
pip install -r requirements.txt
```

Windows:
```
venv\Scripts\activate
pip install -r requirements.txt
```

## Gebruiken

Activeer eerst de omgeving (`source venv/bin/activate` of `venv\Scripts\activate`).

**1. Fotomappen indexeren.** Dit duurt het langst: op een gewone laptop zo'n
5 tot 20 foto's per seconde, dus 10.000 foto's is ongeveer een kwartier tot een
half uur. Daarna worden bij een nieuwe run alleen nieuwe of gewijzigde foto's
bekeken.

```
python fotozoeker.py index ~/Pictures
python fotozoeker.py index "C:\Users\Stef\Pictures" "D:\Foto's 2019"
```

**2. Zoeken.**

```
python fotozoeker.py zoek "mensen die aan tafel eten"
```

Je krijgt een lijst in de terminal en er opent een pagina in je browser met de
beste treffers als miniaturen. Klik op een foto om het origineel te openen.

Handige opties:

| Optie | Wat het doet |
|---|---|
| `--top 100` | meer treffers tonen (standaard 40) |
| `--niet "restaurant"` | foto's die hierop lijken lager zetten |
| `--map ~/Pictures/2023` | alleen binnen deze map zoeken |
| `--kopieer ~/Desktop/etentjes` | de treffers naar een map kopiëren |
| `--min-score 0.25` | alleen treffers boven deze score |

**3. Opruimen** (optioneel): foto's die je verwijderd of verplaatst hebt uit de
index halen.

```
python fotozoeker.py opruimen
```

## Tips voor goede zoekopdrachten

- Beschrijf wat je zou **zien**: "een groep mensen rond een gedekte tafel met
  borden en glazen" werkt beter dan "gezellig etentje".
- Probeer ook eens Engels. Het beeldmodel is in het Engels getraind en dat geeft
  soms net iets betere resultaten.
- De score zegt vooral iets over de volgorde. Alles boven ongeveer 0,25 is
  meestal een goede treffer, maar dat verschilt per zoekopdracht.
- Gezichten herkennen ("foto's van oma") kan dit model niet. Het begrijpt wat er
  gebeurt, niet wie er op de foto staat.

## Waar staat wat

- De index: `~/.fotozoeker/index.sqlite` (een paar MB per 10.000 foto's)
- De laatste resultatenpagina: `~/.fotozoeker/resultaten.html`
- Een andere plek kiezen kan met `--index pad/naar/index.sqlite` vóór het commando.

Ondersteunde formaten: JPG, PNG, WebP, HEIC (iPhone), GIF, BMP, TIFF.
RAW-bestanden (.CR2, .NEF, .ARW) worden niet gelezen.
