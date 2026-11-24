# Plaatsvinder Italië, versie 1

Twee delen:

- `pipeline/`: Python-scripts die alle open bronnen downloaden en er één tabel van maken met één rij per comune (sleutel: ISTAT-code). Uitvoer: `data/comuni.json` (volledig) en `data/comuni-lite.json` (alleen filterkolommen).
- `web/`: de wizard in PHP 8 en vanilla JS, zonder framework en zonder database.

Het plan staat in [PLAN.md](PLAN.md).

## Pipeline draaien

Nodig: Python 3.12 of 3.13, Docker, `osmium-tool` en `curl`.

```bash
python3.13 -m venv .venv
```

```bash
.venv/bin/pip install -r pipeline/requirements.txt
```

```bash
.venv/bin/python pipeline/run.py
```

Dat ene commando ververst alles: het downloadt de bronnen, bouwt het OSRM-wegennet (alleen als dat nog niet bestaat), rekent de rijtijden uit, maakt de tabel en kopieert de twee JSON-bestanden naar `web/data/`.

Opties:

| Optie | Wat het doet |
| --- | --- |
| `--provincie PV` | alleen één provincie (sigla), om te controleren. Percentielen gelden dan binnen die provincie. |
| `--geen-download` | gebruikt wat al in `data/raw/` staat |
| `--opnieuw` | downloadt alles opnieuw en bouwt OSRM en het OSM-uittreksel opnieuw (doe dit twee keer per jaar) |
| `--zonder-osrm` | slaat de rijtijden over; die kolommen blijven leeg |

Rekentijd voor heel Italië op 4 cores en 16 GB: OSRM voorbereiden ongeveer 30 minuten, de rest enkele minuten tot een kwartier.

### Ziekenhuizen

salute.gov.it blokkeert geautomatiseerde downloads. Download daarom zelf het bestand "Strutture di ricovero attive" (xlsx) van het Ministero della Salute en zet het in `data/raw/salute/` (de naam moet "strutture" en "ricovero" bevatten; de pipeline neemt het nieuwste).

Het bestand geeft per vestiging de comune en het adres, maar geen coördinaten. De pipeline:

- laat privéklinieken (case di cura) weg;
- koppelt elke vestiging aan het OSM-ziekenhuis in dezelfde comune met de meeste gedeelde naamwoorden (de naam van de comune zelf telt niet mee), anders aan het OSM-ziekenhuis het dichtst bij het centrum, anders aan het centrum van de comune;
- laat vestigingen weg waar OSM `emergency=no` zegt, private IRCCS en onderzoeksinstituten zonder `emergency=yes`, en hoofdkantoren (ASST/ASL zonder eigen ziekenhuisnaam).

Het resultaat, met de reden van uitsluiting, staat in `data/work/ziekenhuizen.csv`.

### OMI-prijzen

OMI vraagt een (gratis) account, dus de pipeline downloadt die niet. Download in de Forniture dati OMI de **Quotazioni immobiliari, fornitura nazionale**, nieuwste semester, en zet de twee csv's (`QI_..._VALORI.csv` en `QI_..._ZONE.csv`) in `data/raw/omi/`. Niet nodig: Volumi delle compravendite en Perimetri di Zona.

De pipeline neemt het nieuwste semester (nu 2026/1) en berekent per comune de mediaan van de woonzones: abitazioni civili, economiche, signorili en ville e villini, staat "normale", het midden van min en max per zone. Landelijke zones (fascia R) tellen alleen mee als een comune geen andere zones heeft. OMI gebruikt deels verouderde ISTAT-codes, daarom koppelt de pipeline op de kadastercode (`Comune_amm`). Comuni zonder notering krijgen het provinciecijfer (gemarkeerd): bij koop 92, bij huur 590 (OMI noteert in veel dorpen geen huur).

## Wat er in de tabel staat

Per comune onder meer: naam, provincie, regio, middelpunt, inwoners, hoogte, en deze indicatoren:

| Kolom | Bron | Bewerking |
| --- | --- | --- |
| `inwoners`, `groei_pct` | ISTAT bilancio demografico 2025 | groei = verandering in 2025 |
| `hoogte_m` | ISTAT Altimetria dei comuni (stand 2021) | hoogte van het centrum; voor comuni die na 2021 fuseerden: OSM-hoogte |
| `inkomen_eur` | MEF IRPEF 2024 | belastbaar inkomen / aantal belastingplichtigen |
| `afval_gescheiden_pct` | ISPRA Catasto Rifiuti 2024 | % gescheiden ingezameld |
| `glasvezel_pct` | AGCOM Broadband Map, dati comunali (stand 05-10-2026) | FTTH-dekking van huishoudens (DESI) |
| `apotheken_n`, `apotheken_per_1000` | Ministero della Salute, farmacie | actieve apotheken, gekoppeld op coördinaten |
| `pm25` | EEA, interpolated air quality PM2,5 2023 (1 km) | waarde op het middelpunt |
| `buitenlanders_pct` | ISTAT STRASA 2026 | buitenlanders / inwoners |
| `voorzieningen_800m` | OpenStreetMap | winkels, horeca, bank, post enz. binnen 800 m van het centrum |
| `scholen_*_5km` | MIM Anagrafe scuole statali 2026/27 | staatsscholen per niveau binnen 5 km |
| `min_zee`, `min_meer` | EEA Bathing Water 2025 | rijtijd naar dichtstbijzijnde zwemwaterpunt |
| `min_ski` | OSM `landuse=winter_sports` (≥ 0,5 km²) | rijtijd |
| `min_station` | OSM `railway=station/halt` (zonder metro, tram, museumlijnen) | rijtijd |
| `min_luchthaven` | OSM luchthavens met IATA-code en lijnvluchten | rijtijd |
| `min_ziekenhuis` | Ministero della Salute, "Strutture di ricovero attive" (stand 01-10-2026), locatie uit OSM | rijtijd naar het dichtstbijzijnde ziekenhuis; zie hieronder |
| `koop_m2`, `huur_m2` | Agenzia delle Entrate, OMI | mediaan €/m² woningen |

Vlaggen voor stap 1: `kust` (≤ 30 min van zwemwater aan zee), `meer` (≤ 30 min van een meer), `heuvel` (centrum op 300 tot 600 m, of centrum op minstens 150 m met minstens 200 m hoogteverschil binnen de comune), `berg` (> 600 m of ≤ 20 min van een skigebied), `vlakte` (< 300 m, geen heuvelland en niet aan de kust).

Regels:

- Elke indicator heeft ook een percentiel 0-100 over alle comuni (in `p`). Waar laag beter is (rijtijd, fijnstof, prijs) is de schaal omgedraaid.
- Ontbrekende data is `null`, nooit 0.
- Ontbreekt een cijfer voor een comune (inkomen, afval, glasvezel, OMI), dan staat er het provinciecijfer (gewogen naar inwoners) en staat de indicator in de lijst `provinciecijfer`.
- Rijtijden: OSRM (auto, inclusief veerverbindingen) in Docker op `italy-latest.osm.pbf`. Per comune alleen de 5 hemelsbreed dichtstbijzijnde kandidaten per soort. Rijtijden boven 240 minuten (vooral kleine eilanden, waar OSRM met onbetrouwbare veerboottijden rekent) worden 240 en heten op de site "meer dan 4 uur".
- Codes: Sardinië kreeg in 2026 nieuwe provinciecodes (112 tot 119). De pipeline vertaalt oude codes via de ISTAT-lijst. Recente fusies staan in `FUSIES` in `pipeline/config.py`; de pipeline meldt codes die niet te koppelen zijn.
- Trentino-Alto Adige en Valle d'Aosta: hun scholen staan niet in het MIM-bestand, dus scholen = null.

`data/comuni-lite.json` is kolomsgewijs: positie `i` in elke lijst is de `i`-de comune in `comuni.json` (op ISTAT-code). `gr` is de grootteklasse, `lig` een bitmasker in de volgorde van `ligging`, `lat`/`lon` × 1000.

## Versie 2: criteria-onderzoek

Uitgevoerd volgens `docs/plaatsvinder-aanpassingen.md`; het plan staat in `docs/plan-aanpassingen.md`.

- `config/`: criteria, profielen, LEA 2023, 7%-regime, kratergemeenten, rode zones, luchthavens, trends, aandachtspunten en bronnen, met JSON-schema's (`config/schema/`) en `pipeline/config_check.py`.
- `pipeline/bronnen_v2.py` en `pipeline/v2.py`: de nieuwe bronnen; `pipeline/scores.py`: deelscores, wintertest en 80-jarentest.
- `data/dekking.md`: dekking per bron (na elke volledige run). `data/steekproef.md`: steekproef per bron (`python pipeline/steekproef.py`). `data/README.md`: handmatige stappen en afwijkingen.
- Site: `web/data/score.json` (compact, voor score.php), `web/data/detail/<provincie>.json` (gemeentepagina), `web/data/comuni-lite.json` (teller, onder 400 kB). `data/comuni.json` blijft de volledige tabel.

Tests:

```bash
.venv/bin/python -m unittest discover -s pipeline/tests
```

```bash
php web/tests/test_model.php
```

## Website

Zet de map `web/` op de server (PHP 8.1 of hoger). `index.php` is de wizard (met resultaten, gemeentepagina en vergelijken), `score.php` geeft de top 25 en alternatieven, `gemeente.php` geeft de details van één gemeente. Alle teksten staan in `web/lang/nl.json`; voor Duits maak je `de.json` met dezelfde sleutels.

Eisen aan de hosting: PHP 8.1 of hoger met de extensies json en iconv, en een `memory_limit` van minstens 128 MB (score.php vraagt zelf 256 MB als de host dat toestaat). Gemeten met een tabel ter grootte van heel Italië: zonder opcache ca. 70 MB en 0,1 s per verzoek. Staat opcache aan, dan zet `score.php` `comuni.json` één keer om naar `comuni.cache.php` en houdt opcache de tabel in het geheugen; `web/data/` moet dan schrijfbaar zijn voor PHP. `web/data/.htaccess` houdt het volledige bestand uit het zicht (Apache); op nginx doe je hetzelfde met een `location`-regel.

Lokaal testen:

```bash
php -S 127.0.0.1:8090 -t web
```

Stap 8 kent twee standen: "Overal, behalve…" (regio's uitsluiten) en "Alleen in…" (een of meer regio's, en daarbinnen eventueel provincies; een gekozen provincie beperkt haar regio tot die provincies). Percentielen blijven over heel Italië berekend, zodat een score ook binnen één provincie vergelijkbaar blijft met de rest van het land.

Het scoremodel staat in `web/lib/model.php` (filters, gewichten 5/4/3 voor de top 3, 3 voor extra keuzes, 1 voor de rest, +2 voor het profiel, ontbrekende data telt niet mee). Dezelfde filters draaien in `web/assets/app.js` voor de teller.

Met een budget: geschatte woonlast = OMI €/m² × woninggrootte. Plaatsen boven budget vallen af; de score voor betaalbaarheid is hoe ver de woonlast onder het budget blijft (50% of meer onder budget = 100). De uitslag toont de geschatte prijs altijd in een apart blok bij elke plaats; betaalbaarheid staat dan niet nog eens bij de sterke of zwakke punten.

## Bronnen en verplichte naamsvermeldingen

Deze vermeldingen moeten zichtbaar op de site staan (de uitslagpagina toont ze onderaan):

- **OMI**: «Agenzia delle Entrate – OMI», licentie CC BY 4.0. Vermeld dat het officiële waarden zijn en dat vraagprijzen vaak hoger liggen.
- **OpenStreetMap**: «© OpenStreetMap-bijdragers», data onder de Open Database License (ODbL), met link naar https://www.openstreetmap.org/copyright. Rijtijden zijn berekend met OSRM op OSM-data.
- **ISPRA**: «ISPRA, Catasto Rifiuti», https://www.catasto-rifiuti.isprambiente.it
- **EEA**: «European Environment Agency (EEA)», Bathing Water Directive status 2025 en European air quality interpolated data PM2,5 2023.

Overige bronnen (open, vermelding netjes maar zonder extra voorwaarden):

| Bron | Download | Licentie |
| --- | --- | --- |
| ISTAT Elenco comuni italiani | https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.xlsx | CC BY 4.0 |
| ISTAT Confini amministrativi 2026 | https://www.istat.it/storage/cartografia/confini_amministrativi/generalizzati/2026/Limiti01012026_g.zip | CC BY 4.0 |
| ISTAT Bilancio demografico 2025 | https://demo.istat.it/data/p2/P2_2025_it_Comuni.zip | CC BY 4.0 |
| ISTAT Altimetria comuni | https://www.istat.it/wp-content/uploads/2026/02/Altimetria_Comuni-al-31_12_2021.xlsx | CC BY 4.0 |
| ISTAT Stranieri residenti 2026 | https://demo.istat.it/data/strasa/STRASA_2026_it_Comuni.zip | CC BY 4.0 |
| MEF IRPEF per comune 2024 | https://www.finanze.gov.it/it/statistiche-fiscali/open-data-comunale-principali-variabili-irpef/ | open |
| MIM Anagrafe scuole statali | https://dati.istruzione.it/opendata/ | IODL 2.0 |
| Ministero della Salute, farmacie | https://www.dati.salute.gov.it/it/dataset/farmacie | IODL 2.0 |
| Ministero della Salute, strutture di ricovero attive | https://www.salute.gov.it (handmatig) | open |
| AGCOM Broadband Map, dati comunali | https://maps.agcom.it/ (Reportistica) | CC BY 4.0 |
| EEA Bathing water | https://www.eea.europa.eu/en/datahub/datahubitem-view/c3858959-90da-4c1b-b9ca-492db0e514df | CC BY 4.0 |
| EEA PM2,5 2023 | https://sdi.eea.europa.eu/catalogue/srv/api/records/57807f24-25dc-4df3-9468-dcff8cee5468 | CC BY 4.0 |
| OpenStreetMap Italië | https://download.geofabrik.de/europe/italy-latest.osm.pbf (uitwijk: download.openstreetmap.fr) | ODbL |

Niet in versie 1: schoolkwaliteit (INVALSI), ziekenhuiskwaliteit (AGENAS), klimaat (ERA5) en luchtkwaliteit per maand (CAMS).

## Sectie 12: aanvullingen uit de gids

Plan: `docs/plan-aanpassingen-12.md`.

- **Wensenlijst** (stap 2, gids 1.3): maximaal drie dealbreakers, must-haves en nice-to-haves. Dealbreakers worden harde eisen met de drempel uit `criteria.json` (`dealbreaker`); een strengere eigen eis blijft staan. Must-haves tellen 1,5 keer, nice-to-haves 1,2 keer, daarna weer samen 100.
- **Hoogte**: hoogte van het centrum bij elke gemeente, filter "maximale hoogte" (standaard uit), uitleg 0,6 °C per 100 m.
- **Klimaat**: temperatuur januari en juli, vorstdagen, zoninstraling (benadering van zonuren), neerslag uit ERA5-Land; keuze "zachte winter" of "vier seizoenen". Leeg zolang `python pipeline/era5.py download` niet klaar is.
- **Luchtkwaliteit**: nieuw criterium (PM2,5, EEA 2023).
- **Trein**: rijtijd naar station met hogesnelheidstreinen en naar station met nachttrein, binnen bereikbaarheid (luchthaven 50, naar huis 30, trein 10 + 10).
- **Labels zonder score**: ontbonden gemeenteraad (`config/sciolti_mafia.json`, nog te vullen).
- **Gids**: blok "Meer hierover in de gids" (`config/gids_links.json`, alleen verwijzingen met url) en gidsprijs per regio (`config/gids_prijzen.json`, nog te vullen).
- **Gepland** (12b): staat in `criteria.json` onder `gepland`.
- **Bijlage A** (`config/luchthavens.json`): directe verbindingen per luchthaven met Nederland en België, stand november 2026. "Hele jaar" = minstens één verbinding zonder seizoensbeperking. Niet jaarrond: Rome Ciampino, Reggio Calabria, Trapani. Duitse luchthavens staan niet in de gids.
- **Gidsprijzen** (`config/gids_prijzen.json`): de bedragen per regio die de gids noemt (vraagprijzen); regio's zonder bedrag in de gids blijven leeg.
- **Gids-verwijzingen** met paragraaf en pagina; Bijlage C als link.
- Uit de wensenlijst van Un Sogno di Casa overgenomen: "verder waar u gebleven was" bij terugkeer en een overzicht van de keuzes met "Wijzigen" per stap.
- Niet opgenomen: Qualità della vita (Il Sole 24 Ore), hergebruik niet toegestaan.
