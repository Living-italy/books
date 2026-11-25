# Plaatsvinder Italië: beschrijving

Stand 25 november 2026. Deze beschrijving volgt de code op branch `plaatsvinder` (map `plaatsvinder/`).

## 1. Wat het is

De Plaatsvinder helpt Nederlandstaligen die een huis in Italië zoeken. De bezoeker beantwoordt een paar vragen. De tool geeft dan de 25 Italiaanse gemeenten die het best passen, met per gemeente een uitleg.

- Alle 7.894 Italiaanse gemeenten (comuni) doen mee, met de ISTAT-code als sleutel.
- Alle cijfers komen uit open bronnen. Elke waarde heeft een bron, een peildatum en een schaalniveau.
- De tool rekent geen huizen door. Het gaat om de keuze van de plaats, niet van de woning.

Er zijn twee delen:

1. **De pipeline** (Python, map `pipeline/`): haalt de bronnen op, rekent per gemeente de indicatoren en scores uit en maakt de databestanden voor de site. Alleen nodig om de data te verversen: `python pipeline/run.py`.
2. **De website** (PHP 8 zonder framework en gewone JavaScript, map `web/`): de wizard, de uitslag, de gemeentepagina en het vergelijken. Geen database; de site leest JSON-bestanden. De zip `plaatsvinder-web.zip` is de complete site om op stefsmulders.nl te zetten.

## 2. De website voor de bezoeker

### 2.1 De wizard (7 stappen)

Onderaan elke stap staat een teller: "Nog 4.913 plaatsen over van 7.894". Die telt direct in de browser na elke keuze.

| Stap | Vraag | Wat het doet |
|---|---|---|
| 1 | Wie bent u? | Kies een profiel (zie 2.2). Bij de gepensioneerde volgt de vraag "permanent wonen of het jaar verdelen". Het profiel bepaalt de startgewichten en enkele standaardeisen. |
| 2 | Uw wensenlijst | Maximaal 3 dealbreakers, 3 must-haves en 3 nice-to-haves uit de criteria (gids, paragraaf 1.3). Zie 2.3. |
| 3 | Waar wilt u wonen? | Kust, meer, heuvels, bergen, vlakte; meerdere keuzes mogelijk (zie 3.5). |
| 4 | Hoe groot mag de plaats zijn? | Minder dan 1.000 inwoners, 1.000-5.000, 5.000-20.000, 20.000-100.000, meer dan 100.000. |
| 5 | Wat vindt u belangrijk? | Een schuifje per criterium; de gewichten tellen altijd op tot 100. Bij klimaat de keuze "zachte winter" of "vier seizoenen"; bij andere buitenlanders "liever veel", "maakt niet uit" of "liever weinig". |
| 6 | Wat is voor u een harde eis? | Harde filters: maximale prijs per m², maximale rijtijd naar een luchthaven met vluchten het hele jaar, naar een ziekenhuis met spoedeisende hulp, naar huis (Utrecht, Brussel, Keulen of München), minimaal aandeel glasvezel, maximale hoogte van het centrum, seismische zone 1 uitsluiten, rode zone van Vesuvius en Campi Flegrei uitsluiten (standaard aan), alleen gemeenten met het 7%-regime. Optioneel een budget: huren of kopen, maximaal bedrag en woninggrootte. |
| 7 | Waar in Italië? | Heel Italië met regio's uitzetten, of alleen bepaalde regio's en daarbinnen eventueel alleen bepaalde provincies. |

Antwoorden blijven in de browser bewaard (localStorage, niet op de server). Wie terugkomt, krijgt "U was hier al eerder" met de keuze tussen verdergaan en opnieuw beginnen. Dit patroon komt uit de wensenlijst van Un Sogno di Casa.

### 2.2 Profielen en startgewichten

| Criterium | Gepensioneerde | Tweede huis | Gezin | Remote worker | Investeerder / B&B |
|---|---|---|---|---|---|
| Budget en prijsniveau | 15 | 15 | 12 | 12 | 15 |
| Natuur, rust en ruimte | 7 | 15 | 3 | 6 | 7 |
| Klimaat | 12 | 8 | 6 | 10 | 0 |
| Cultuur | 5 | 8 | 2 | 6 | 8 |
| Bereikbaarheid | 8 | 18 | 8 | 16 | 12 |
| Dagelijkse voorzieningen | 12 | 4 | 18 | 8 | 0 |
| Gezondheidszorg | 15 | 0 | 8 | 0 | 0 |
| Internet | 2 | 3 | 12 | 22 | 3 |
| Levendigheid (wintertest) | 8 | 0 | 12 | 12 | 0 |
| Natuurrisico's | 8 | 8 | 8 | 6 | 10 |
| Veiligheid | 3 | 5 | 8 | 2 | 5 |
| Andere buitenlanders | 2 | 0 | 0 | 0 | 0 |
| Toerisme / verhuur | 0 | 10 | 0 | 0 | 22 |
| Herverkoopbaarheid | 0 | 6 | 0 | 0 | 18 |
| Luchtkwaliteit | 3 | 0 | 3 | 0 | 0 |
| Fiscaal, kosten, klimaatverandering | 0 | 0 | 0 | 0 | 0 |

- Gepensioneerde die het jaar verdeelt: bereikbaarheid 18, natuur en luchtkwaliteit 0.
- Standaardeisen: gepensioneerde spoedeisende hulp binnen 45 minuten; remote worker minimaal 50% glasvezel. De bezoeker kan ze aanpassen.
- Labels in de profielkeuze volgen de gids: "permanente bewoning (hoofdwoning)" en "tweede huis (vakantie, af en toe)".
- Fiscaal, kosten van levensonderhoud en klimaatverandering hebben startgewicht 0. De bezoeker kan ze zelf zwaarder maken.

### 2.3 Wensenlijst

- **Dealbreakers** worden harde eisen, met de drempel uit `config/criteria.json`. Heeft de bezoeker zelf al een strengere eis gezet, dan blijft die staan.

| Criterium | Dealbreaker betekent |
|---|---|
| Budget | maximaal € 1.500 per m² |
| Klimaat | centrum niet hoger dan 800 m |
| Bereikbaarheid | luchthaven met vluchten het hele jaar binnen 90 minuten |
| Gezondheidszorg | spoedeisende hulp binnen 45 minuten |
| Internet | minimaal 50% van de huishoudens met glasvezel |
| Fiscaal | alleen gemeenten met het 7%-regime |
| Natuurrisico's | geen seismische zone 1 en geen rode zone |

- **Must-haves** tellen 1,5 keer zo zwaar, **nice-to-haves** 1,2 keer. Daarna worden de gewichten weer naar precies 100 gebracht (grootste-rest-methode, zodat de afronding klopt).

### 2.4 De uitslag

- De 25 gemeenten met de hoogste totaalscore (0 tot 100), met provincie, regio, inwoners en hoogte.
- Per gemeente de drie sterkste en de drie zwakste criteria met een balkje, de wintertest en de 80-jarentest, en de criteria zonder data.
- Bij een budget: de geschatte woonlast (OMI-prijs maal woninggrootte) en hoeveel procent onder het budget.
- Badge "trend" voor regio's en provincies die volgens het jaarrapport van Gate-away.com in opkomst zijn (handmatige tabel, geen score).
- Overzicht "Uw keuzes" met per stap "Wijzigen". Na het wijzigen brengt "Terug naar de uitslag" de bezoeker direct terug.
- Tot drie gemeenten aanvinken om te vergelijken.

### 2.5 De gemeentepagina

- Totaalscore met de gewichten van de bezoeker, hoogte van het centrum met de uitleg "per 100 m ongeveer 0,6 °C koeler".
- Per criterium de deelscore, het gewicht, de basis (enquête of expertoordeel) en alle onderliggende waarden met bron, peildatum en schaalniveau. Een provinciecijfer wordt als zodanig gemeld.
- Extra regels per criterium:
  - prijs: de gemiddelde vraagprijs volgens de gids voor 10 regio's;
  - bereikbaarheid: de directe vluchten vanaf de dichtstbijzijnde jaarrond-luchthaven (Bijlage A) en de namen van het dichtstbijzijnde hogesnelheidsstation en nachttreinstation;
  - klimaat: een melding als hete dagen en vorstdagen geschat zijn (zie 4.3).
- Risicoblok (altijd): seismische zone met subzone, aardverschuiving, overstroming, bosbrand, vulkaan, en een link naar de IdroGEO-kaart van ISPRA.
- Wintertest en 80-jarentest met hun onderdelen. Bij een 80-jarentest onder 40 de waarschuwing dat men hier op latere leeftijd sterk afhankelijk is van de auto (onderbouwing NIDI).
- 7%-regime: ja, nee of te verifiëren, met reden; waarschuwing als de regionale zorg (LEA) laag scoort.
- Herverkoop: verkopen per 100 woningen per jaar; onder 1 de waarschuwing "Verkopen kan hier lang duren."
- Label "Gemeenteraad ontbonden" voor de 18 gemeenten uit 2025 en 2026 die wegens maffia-infiltratie zijn ontbonden, met jaar, organisatie, het hoeveelste keer en de bron. Geen score.
- Checklist (bezoek in januari of februari, wintervluchten, jacht en bespuitingen, staat van de woning, verhuur, taal) en de lijst van wat niet in de score zit.
- Blok "Meer hierover in de gids" met paragraaf en pagina: regio's (1.1.2 en Bijlage B), Bijlage A, natuurrisico's (1.1.1 c), 7%-regime (7.2.1, met link naar het 7%-document), verhuur (hoofdstuk 10) en Bijlage C (link naar de kaarten).
- Drie alternatieven: gemeenten van dezelfde grootteklasse met een vergelijkbaar profiel en een minstens zo hoge score.

## 3. Wat de tool berekent

### 3.1 Van ruwe waarde naar percentiel

Elke indicator wordt per gemeente omgezet in een percentiel van 0 tot 100 over heel Italië.

- 100 is het beste, 0 het slechtste. Bij "lager is beter" (prijs, rijtijden, risico's) wordt de volgorde omgedraaid.
- Gelijke waarden krijgen hetzelfde gemiddelde percentiel.
- Ontbreekt een waarde, dan blijft die leeg. Alleen bij koopprijs, huurprijs en glasvezel wordt een ontbrekend cijfer vervangen door het provinciecijfer (gewogen naar inwoners), en dat wordt op de gemeentepagina gemeld.

### 3.2 Deelscore per criterium

De deelscore is het gemiddelde van de percentielen van de indicatoren met data. Bij bereikbaarheid is dat gewogen: luchthaven 50, naar huis 30, hogesnelheidsstation 10, nachttrein 10.

### 3.3 Totaalscore

Totaal = gewogen gemiddelde van de deelscores, met de gewichten van de bezoeker. Ontbreekt een deelscore, dan gaat het gewicht naar de andere criteria; een gemeente wordt dus niet gestraft voor een gat in de data.

- Klimaat: bij "vier seizoenen" draaien de wintertemperatuur en de vorstdagen van richting om (meer winter is dan beter).
- Andere buitenlanders: bij "liever weinig" draait de richting om; bij "maakt niet uit" telt het criterium niet mee.

### 3.4 Wintertest en 80-jarentest

Twee aparte scores van 0 tot 100, die niet in het totaal meetellen.

| Wintertest (is de plaats buiten het seizoen levendig?) | Gewicht |
|---|---|
| SNAI-klasse (afstand tot diensten; polo 100, ultraperiferico 0) | 30 |
| Bevolkingsontwikkeling over 10 jaar | 20 |
| Supermarkt, apotheek en bar binnen 800 m | 20 |
| Aandeel 65-plussers (laag is beter) | 10 |
| Zomerpiek: toeristische bedden per inwoner maal seizoensaandeel (laag is beter) | 10 |
| Vorstdagen (laag is beter) | 10 |

| 80-jarentest (kun je hier oud worden zonder auto?) | Gewicht |
|---|---|
| Rijtijd naar ziekenhuis met spoedeisende hulp | 30 |
| Apotheek (rijtijd) en huisarts binnen 800 m | 20 |
| LEA-score van de regionale zorg | 20 |
| Supermarkt binnen 800 m | 15 |
| Rijtijd naar treinstation | 15 |

Ontbreekt een onderdeel, dan wordt het gewicht over de andere onderdelen verdeeld.

### 3.5 Ligging (stap 3)

| Ligging | Regel |
|---|---|
| Kust | zeezwemwater binnen 30 minuten rijden |
| Meer | meerzwemwater binnen 30 minuten |
| Heuvel | centrum 300-600 m, of heuvelland: centrum minstens 150 m en minstens 200 m hoogteverschil in de gemeente (zoals de Oltrepò Pavese) |
| Berg | centrum boven 600 m, of skigebied binnen 20 minuten |
| Vlakte | centrum onder 300 m, geen heuvelland en geen kust |

Een gemeente kan meerdere liggingen hebben.

### 3.6 Rijtijden

- Berekend met OSRM (routeplanner op OpenStreetMap, auto, inclusief veerboten) vanaf het centrum van elke gemeente naar de dichtstbijzijnde bestemmingen van elk soort.
- Het centrum is de OSM-plaats met de naam van de gemeente, anders het gemeentehuis, anders een punt binnen de gemeentegrens.
- Rijtijden boven 4 uur worden als "meer dan 4 uur" geteld (kleine eilanden).
- Naar huis: rijtijd naar de beste van negen grensovergangen plus de rijtijd van die grensovergang naar Utrecht, Brussel, Keulen of München (openbare OSRM-server). De kortste telt.
- Ziekenhuizen: de officiële lijst van het Ministero della Salute, gekoppeld aan een locatie in OSM. Vestigingen zonder spoedeisende hulp, onderzoeksinstituten en hoofdkantoren vallen weg; 814 vestigingen blijven over.
- Luchthavens "hele jaar": minstens 50.000 passagiers in 2025 (Assaeroporti) en volgens Bijlage A van de gids minstens één directe verbinding met Nederland of België zonder seizoensbeperking. Rome Ciampino, Reggio Calabria en Trapani vallen daardoor af.
- Hogesnelheidsstations: handmatige lijst van 59 stations met Frecciarossa of Italo. Nachttreinen: 36 haltes uit de treinroutes in OSM.

### 3.7 Klimaat

- Temperatuur januari en juli, zon (inkomende zonnestraling in kWh per m² per dag, als maat voor zonuren) en neerslag: ERA5-Land maandgemiddelden, raster circa 9 km, gemiddelde 2016-2025.
- Kustgemeenten waarvan het middelpunt in een zeecel valt, krijgen de dichtstbijzijnde landcel.
- De temperatuur wordt gecorrigeerd voor het hoogteverschil tussen rastervak en centrum: 0,65 °C per 100 m.
- Hete dagen (boven 30 °C) en vorstdagen: Copernicus-klimaatindicatoren, raster circa 25 km. Bij ruim 3.100 gemeenten ligt dat rastervak meer dan 150 m hoger of lager dan het centrum (dalen en bergen). Daar worden ze geschat uit de gecorrigeerde temperatuur van juli en januari, met het verband uit de overige gemeenten. De gemeentepagina meldt dat.
- Klimaatverandering: extra hete dagen en verandering van de neerslag, 2016-2025 tegenover 1991-2000.

### 3.8 Overige berekeningen

- **Koopprijs en huurprijs**: mediaan van de woonzones van OMI (nieuwste semester). Landelijke zones tellen alleen mee als een gemeente geen andere zone heeft.
- **Natuur**: aandeel beschermd gebied (nationale parken, natuurreservaten, Natura 2000 en strikt beschermde gebieden uit OSM) binnen 5 km van het centrum, plus bevolkingsdichtheid en hoogteverschil.
- **Cultuur**: aantal historische objecten en musea binnen 5 km (OSM).
- **Voorzieningen**: winkels en horeca binnen 800 m, en of supermarkt, apotheek en bar binnen 800 m liggen.
- **7%-regime**: regio in het zuiden (Sicilië, Calabrië, Sardinië, Campanië, Basilicata, Abruzzen, Molise, Puglia) en hoogstens 30.000 inwoners, of een gemeente uit de aardbevingsgebieden (L'Aquila 2009, Centraal-Italië 2016-2017) volgens wet 34/2026. Bij twijfel "te verifiëren".
- **Herverkoopbaarheid**: verkochte woningen in 2025 (OMI) per 100 woningen uit de census van 2021.
- **Expats**: Nederlanders, Belgen, Duitsers en Britten per 1.000 inwoners.

## 4. Bronnen

| Bron | Gebruikt voor | Peildatum | Schaal | Licentie |
|---|---|---|---|---|
| ISTAT, Elenco comuni | lijst van gemeenten, codes | 2026 | gemeente | CC BY 4.0 |
| ISTAT, bevolking (demo.istat.it) | inwoners, groei, 65-plussers | 31-12-2025 | gemeente | CC BY 4.0 |
| ISTAT, Altimetria | hoogte centrum, hoogteverschil | 2021 | gemeente | CC BY 4.0 |
| ISTAT, Confini amministrativi | gemeentegrenzen | 2026 | gemeente | CC BY 4.0 |
| ISTAT, cittadinanza | expats | 1-1-2025 | gemeente | CC BY 4.0 |
| ISTAT, esercizi ricettivi | toeristische bedden | 2025 | gemeente | CC BY 4.0 |
| ISTAT, toerisme per maand | seizoensaandeel (wintertest) | 2024 | provincie | CC BY 4.0 |
| ISTAT, delitti denunciati | veiligheid | 2024 | provincie | CC BY 4.0 |
| ISTAT, censimento 2021 | woningvoorraad | 2021 | gemeente | CC BY 4.0 |
| ISTAT/DPCoe, SNAI | afstand tot diensten | 2022 | gemeente | CC BY 4.0 |
| Agenzia delle Entrate, OMI quotazioni | koop- en huurprijs | 1e semester 2026 | OMI-zone | CC BY 4.0 |
| Agenzia delle Entrate, OMI compravendite | verkopen (herverkoop) | 2025 | gemeente | CC BY 4.0 |
| OpenStreetMap | wegen, voorzieningen, natuur, cultuur, stations, luchthavens | 23-11-2026 | punt | ODbL |
| OSRM op OpenStreetMap | rijtijden | 23-11-2026 | gemeente | ODbL |
| EEA, Bathing Water | kust en meer (ligging) | 2025 | punt | CC BY 4.0 |
| EEA, PM2,5 (geïnterpoleerd) | luchtkwaliteit | 2023 | 1 km | CC BY 4.0 |
| ISPRA, IdroGEO | aardverschuiving, overstroming | 2025 | gemeente | CC BY 4.0 |
| Protezione Civile, classificazione sismica | seismische zone | 31-5-2025 | gemeente | open |
| Protezione Civile, zona rossa | Vesuvius en Campi Flegrei | 2016 | gemeente | open |
| EFFIS (Copernicus) | afgebrand oppervlak | 2016-2026 | polygoon | CC BY 4.0 |
| Copernicus ERA5-Land, maandgemiddelden | temperatuur, zon, neerslag | 1991-2025 | 9 km | CC BY 4.0 |
| Copernicus, klimaatindicatoren (ECDE) | hete dagen, vorstdagen | 1940-2025 | 25 km | CC BY 4.0 |
| Ministero della Salute, strutture di ricovero | ziekenhuizen | 1-10-2026 | adres | open |
| Ministero della Salute, farmacie | apotheken | 23-11-2026 | adres | IODL 2.0 |
| Ministero della Salute, NSG/LEA | kwaliteit regionale zorg | 2023 | regio | open |
| MIM, anagrafe scuole | scholen (in de data, nog niet in een criterium) | 2026/27 | gemeente | IODL 2.0 |
| MEF, addizionale comunale IRPEF | gemeentelijke belasting | 2025 | gemeente | open |
| AGCOM Broadband Map | glasvezel | 5-10-2026 | gemeente | CC BY 4.0 |
| Assaeroporti | passagiers per luchthaven | 2025 | luchthaven | open |
| Gids, Bijlage A | jaarrond-verbindingen | november 2026 | luchthaven | eigen tabel |
| Gids, 1.1.2 en Bijlage B | vraagprijs per regio | 2025-2026 | regio | eigen tabel |
| Art. 24-ter TUIR, wet 34/2026 | 7%-regime | 2026 | gemeente | wettekst |
| Gate-away.com, jaarrapport | trend-badge | 2025 | regio, provincie | handmatige tabel |
| Openbare OSRM-server | grensovergang naar huis | 24-11-2026 | grensovergang | ODbL |
| Treinstations (Trenitalia, Italo, OSM) | hogesnelheid en nachttrein | 2026 | station | eigen tabel; ODbL |
| Ministero dell'Interno e.a. | ontbonden gemeenteraden | 25-11-2026 | gemeente | open |

De dekking per indicator staat in `data/dekking.md`. Vrijwel alles heeft data voor 99-100% van de gemeenten. Uitzonderingen: herverkoopbaarheid 95,7%, veiligheid 98,6% en 7%-regime 97,7%.

## 5. Beperkingen en keuzes

- **Kosten van levensonderhoud** heeft geen data: er is geen open bron per gemeente.
- **Niet in de score** (wel genoemd): staat van de woning, taal en mentaliteit, werk, hinder door landbouw en jacht, subsidies en 1-euro-huizen.
- **Bewust weggelaten**: Qualità della vita en de criminaliteitsindex van Il Sole 24 Ore (hergebruik niet toegestaan), en commerciële sites als Immobiliare.it en Idealista (niet gescrapet). De gidsprijzen zijn wel vraagprijzen uit die bronnen, zoals de gids ze noemt.
- **Hete dagen boven 30 °C** in plaats van de 32 °C uit de opdracht: de bron kent alleen 30 en 35 °C.
- **Vorstdagen in alpendalen** zijn waarschijnlijk wat te laag geschat, omdat koude lucht 's nachts in het dal blijft hangen.
- **Duitse luchthavens** staan niet in Bijlage A en tellen niet als jaarrond-luchthaven. Eindhoven is van 1 februari tot 19 juli 2027 dicht; dat staat als noot in de data.
- **Natuur en cultuur** zijn voorlopige maten op basis van OSM; de kwaliteit van OSM verschilt per streek.
- **Veiligheid** is een provinciecijfer: elke gemeente in een provincie krijgt hetzelfde.
- **Gewichten** komen deels uit enquêtes onder kopers (D) en deels uit expertoordeel (E). Elk criterium toont welke van de twee.
- **Ontbonden gemeenteraden**: alleen 2025 en 2026.

## 6. Gepland of open

- PM10 en NO2 (EEA 2023): de EEA-servers zijn op dit moment onbereikbaar; opnieuw proberen staat ingepland.
- Gepland in `criteria.json`: huisdieren (leishmania en dergelijke), wild (Afrikaanse varkenspest), droogte, laadpalen, aardgasnet, internationale scholen, toeristenbelasting, programma's voor nieuwe inwoners.
- Eventueel: ontbonden gemeenteraden van 2016 tot en met 2024.

## 7. Techniek

### 7.1 Pipeline (`python pipeline/run.py`)

1. Configuratiecontrole tegen JSON-schema's (`config/schema/`).
2. Downloads (overslaan met `--geen-download`). Bestanden die alleen handmatig te krijgen zijn (OMI) staan in `data/raw/`. Klimaat: `python pipeline/era5.py download` (vraagt een Copernicus-sleutel).
3. Opbouw van de tabel per gemeente: harmonisatie van ISTAT-codes (nieuwe Sardijnse provincies, fusies), OSM-verwerking (pyosmium), rijtijden met OSRM in Docker.
4. Percentielen, deelscores en tests.
5. Export: `data/comuni.json` (volledig, circa 19 MB), `web/data/score.json` (compact, voor de score), `web/data/detail/<provincie>.json` (voor de gemeentepagina), `web/data/comuni-lite.json` (circa 350 kB, voor de teller), `web/data/meta.json` en een kopie van de configuratie in `web/config/`.
6. Dekkingsrapport (`data/dekking.md`) en steekproef tegen de ruwe bronnen (`data/steekproef.md`).

Een volledige run zonder downloads duurt 4 tot 7 minuten.

### 7.2 Website

- `index.php`: de pagina met teksten en configuratie.
- `score.php`: ontvangt de antwoorden (POST) en geeft de top 25 terug, eventueel met alternatieven. Rekenregels in `lib/model.php`.
- `gemeente.php`: de details van één gemeente.
- `assets/app.js`: wizard, teller, uitslag, gemeentepagina en vergelijken. De teller in de browser gebruikt dezelfde filterregels als de server; bij de bouw is gecontroleerd dat beide hetzelfde aantal geven (4.913 in het testvoorbeeld).
- Alle teksten staan in `lang/nl.json`. Er komt geen gedachtestreepje in de teksten; een test controleert dat.

### 7.3 Tests

- `php web/tests/test_model.php`: gewichten tellen op tot 100 (ook na must/nice), standaardeisen werken, de rode zone sluit Ercolano uit, de dealbreaker internet laat geen gemeente onder 50% glasvezel over, het hoogtefilter werkt, klimaatvoorkeur werkt, labels veranderen de score niet, de gids-link staat erin, alternatieven scoren minstens zo hoog.
- `python -m unittest discover -s pipeline/tests`: configuratie, 7%-regime, teksten, bronnen.
- Steekproef: zes gemeenten (Montecalvo Versiggia, Greve in Chianti, Casale Monferrato, Montegiorgio, Gerace, Ercolano) vergeleken met de ruwe bronbestanden; alle waarden kloppen.

### 7.4 Belangrijke configuratiebestanden

| Bestand | Inhoud |
|---|---|
| `config/criteria.json` | criteria, indicatoren, richting, bron, gewichten binnen een criterium, dealbreakers, wensenlijst, gepland |
| `config/profielen.json` | profielen, startgewichten, standaardeisen, vervolgvragen |
| `config/bronnen.json` | alle bronnen met url, peildatum, schaalniveau en licentie |
| `config/luchthavens.json` | passagiersdrempel, Bijlage A (verbindingen en jaarrond) |
| `config/treinstations.json` | hogesnelheidsstations |
| `config/regime7.json`, `kratergemeenten.json` | regels en gemeenten voor het 7%-regime |
| `config/zona_rossa.json` | rode zone Vesuvius en Campi Flegrei |
| `config/grens_naar_huis.json` | rijtijd van negen grensovergangen naar Utrecht, Brussel, Keulen en München |
| `config/gids_links.json`, `gids_prijzen.json` | verwijzingen naar en prijzen uit de gids |
| `config/sciolti_mafia.json` | ontbonden gemeenteraden 2025-2026 |
| `config/aandachtspunten.json` | checklist en waarschuwingen |
| `config/trends.json` | regio's en provincies in opkomst |
