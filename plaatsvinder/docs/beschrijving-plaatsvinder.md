# Plaatsvinder Italië: beschrijving

Stand 26 november 2026. Deze beschrijving volgt de code op branch `plaatsvinder` (map `plaatsvinder/`).

## 1. Wat het is

De Plaatsvinder helpt Nederlandstaligen die een huis in Italië zoeken. De bezoeker beantwoordt een paar vragen. De tool geeft dan de 25 Italiaanse gemeenten die het best passen, met per gemeente een uitleg.

- Alle 7.894 Italiaanse gemeenten (comuni) doen mee, met de ISTAT-code als sleutel.
- Alle cijfers komen uit open bronnen. Elke waarde heeft een bron, een peildatum en een schaalniveau.
- De tool rekent geen huizen door. Het gaat om de keuze van de plaats, niet van de woning.

Er zijn twee delen:

1. **De pipeline** (Python, map `pipeline/`): haalt de bronnen op, rekent per gemeente de indicatoren en scores uit en maakt de databestanden voor de site. Alleen nodig om de data te verversen: `python pipeline/run.py`.
2. **De website** (PHP 8 zonder framework en gewone JavaScript, map `web/`): de wizard, de uitslag, de gemeentepagina en het vergelijken. Geen database; de site leest JSON-bestanden. De zip `plaatsvinder-web.zip` is de complete site om op stefsmulders.nl te zetten.

## 2. De website voor de bezoeker

### 2.1 De wizard (6 stappen)

Onderaan elke stap staat een teller: "Nog 1.848 plaatsen over van 7.894". Die telt direct in de browser na elke keuze.

| Stap | Vraag | Wat het doet |
|---|---|---|
| 1 | Wie bent u? | Kies een profiel (zie 2.2). Bij de gepensioneerde volgt de vraag "permanent wonen of het jaar verdelen". Het profiel bepaalt de startkeuzes bij de eigenschappen. |
| 2 | Waar wilt u zoeken? | Heel Italië (standaard, met de tip om daar te beginnen), een of meer regio's, of een of meer provincies (gegroepeerd per regio, met zoekveld). Een harde filter; de scores blijven over heel Italië berekend. |
| 3 | Waar wilt u wonen? | Kust, meer, heuvels, bergen, vlakte; meerdere keuzes mogelijk (zie 3.5). |
| 4 | Hoe groot mag de plaats zijn? | Minder dan 1.000 inwoners, 1.000-5.000, 5.000-20.000, 20.000-100.000, meer dan 100.000. |
| 5 | Wat vindt u belangrijk? | Eén lijst met alle eigenschappen (zie 2.3). Naast de naam twee vakjes: noodzakelijk en onbelangrijk; niets aangevinkt is meewegen. Daaronder de uitleg en, behalve bij onbelangrijk, de niveaus als direct zichtbare keuzes met korte uitleg, en hoeveel gemeenten het gekozen niveau halen. |
| 6 | Hoe zwaar wegen ze? | Een schuifje van 1 tot 9 voor elke eigenschap die meeweegt. Noodzakelijke eigenschappen tellen vast met gewicht 10, onbelangrijke met 0. |

Antwoorden blijven in de browser bewaard (localStorage, niet op de server). Wie terugkomt, krijgt "U was hier al eerder" met de keuze tussen verdergaan en opnieuw beginnen.

### 2.2 Profielen en startkeuzes

Het profiel (gepensioneerde, tweede huis, gezin, remote worker, investeerder) bepaalt de startkeuzes per eigenschap. Elke eigenschap hoort bij een criterium uit `config/profielen.json`. Heeft dat criterium in het profiel gewicht 0, dan begint de eigenschap als onbelangrijk; anders als meewegen met gewicht criteriumgewicht / 2 (afgerond, tussen 1 en 9) en het standaardniveau. Uitzonderingen (`config/eigenschappen.json`, onderdeel profielen): gepensioneerde spoedeisende hulp noodzakelijk binnen 45 minuten; remote worker internet noodzakelijk minimaal 50%; investeerder geen massatoerisme onbelangrijk. De bezoeker kan alles aanpassen.

### 2.3 De eigenschappen

32 eigenschappen in 8 groepen, met 3 tot 5 niveaus. Uitzonderingen: het 7%-regime en vulkaangebied hebben 2 niveaus, de ontbonden gemeenteraad 1; dat zijn ja/nee-eigenschappen.

- **Noodzakelijk**: een gemeente die het gekozen niveau niet haalt, valt af. Telt in de score met het vaste maximale gewicht 10.
- **Meewegen**: sluit niets uit. Telt mee met het eigen gewicht (1 tot 9) en het gewenste niveau.
- **Onbelangrijk**: geen niveau, gewicht 0.

| Groep | Eigenschap | Niveaus (van soepel naar streng) |
|---|---|---|
| Wonen en prijs | Woningprijs | 1. tot € 2.000 per m²; 2. tot € 1.500 per m²; 3. tot € 1.000 per m²; 4. tot € 750 per m² |
| Natuur en omgeving | Beschermde natuur | 1. enig beschermd gebied binnen 5 km; 2. minstens 10% binnen 5 km; 3. minstens 25% binnen 5 km; 4. minstens 50% binnen 5 km |
| Natuur en omgeving | Rust en ruimte | 1. tot 500 inwoners per km²; 2. tot 200 inwoners per km²; 3. tot 100 inwoners per km²; 4. tot 50 inwoners per km² |
| Natuur en omgeving | Geen snelweg dichtbij | 1. minstens 1 km van een snelweg; 2. minstens 2 km; 3. minstens 5 km |
| Natuur en omgeving | Geen massatoerisme | 1. tot 50 per inwoner; 2. tot 20 per inwoner; 3. tot 10 per inwoner; 4. tot 5 per inwoner |
| Natuur en omgeving | Cultuur in de buurt | 1. binnen 30 minuten; 2. binnen 15 minuten; 3. binnen 10 minuten; 4. binnen 5 minuten |
| Klimaat | Zachte winter | 1. januari gemiddeld 0 °C of warmer; 2. januari 3 °C of warmer; 3. januari 6 °C of warmer; 4. januari 9 °C of warmer |
| Klimaat | Geen extreme zomerhitte | 1. hoogstens 60 dagen boven 30 °C; 2. hoogstens 45 dagen; 3. hoogstens 30 dagen; 4. hoogstens 15 dagen |
| Klimaat | Niet te hoog gelegen | 1. centrum tot 1.000 m; 2. tot 800 m; 3. tot 600 m; 4. tot 400 m |
| Klimaat | Veel zon | 1. minstens 3,5 kWh; 2. minstens 3,8 kWh; 3. minstens 4,1 kWh; 4. minstens 4,4 kWh |
| Klimaat | Schone lucht | 1. PM2,5 tot 20 µg/m³; 2. tot 15 µg/m³; 3. tot 12 µg/m³; 4. tot 10 µg/m³ |
| Bereikbaarheid | Luchthaven met vluchten het hele jaar | 1. binnen 2 uur; 2. binnen 90 minuten; 3. binnen 60 minuten; 4. binnen 45 minuten; 5. binnen 30 minuten |
| Bereikbaarheid | Met de auto naar huis | 1. binnen 18 uur; 2. binnen 16 uur; 3. binnen 14 uur; 4. binnen 12 uur; 5. binnen 11 uur |
| Bereikbaarheid | Treinstation dichtbij | 1. binnen 30 minuten; 2. binnen 20 minuten; 3. binnen 15 minuten; 4. binnen 10 minuten; 5. binnen 5 minuten |
| Bereikbaarheid | Hogesnelheidstrein | 1. binnen 90 minuten; 2. binnen 60 minuten; 3. binnen 45 minuten; 4. binnen 30 minuten |
| Voorzieningen en zorg | Voorzieningen op loopafstand | 1. minstens één van de drie; 2. minstens twee van de drie; 3. alle drie |
| Voorzieningen en zorg | Spoedeisende hulp | 1. binnen 60 minuten; 2. binnen 45 minuten; 3. binnen 30 minuten; 4. binnen 20 minuten |
| Voorzieningen en zorg | Goede regionale zorg | 1. laagste gebiedsscore minstens 50; 2. minstens 60; 3. minstens 70; 4. minstens 80 |
| Voorzieningen en zorg | Oud worden zonder auto | 1. 80-jarentest minstens 35 van 100; 2. 80-jarentest minstens 50 van 100; 3. 80-jarentest minstens 65 van 100 |
| Voorzieningen en zorg | Snel internet | 1. minstens 25% glasvezel; 2. minstens 50%; 3. minstens 75%; 4. minstens 90% |
| Leven buiten het seizoen | Levendig in de winter | 1. wintertest minstens 35 van 100; 2. wintertest minstens 50 van 100; 3. wintertest minstens 65 van 100 |
| Leven buiten het seizoen | Niet afgelegen | 1. niet ultraperiferico (binnen ongeveer 67 minuten); 2. hoogstens intermedio (binnen ongeveer 41 minuten); 3. cintura of kern (binnen ongeveer 28 minuten); 4. zelf een kern (polo) |
| Veiligheid en risico's | Veiligheid | 1. hoogstens 1,5 keer het gemiddelde; 2. hoogstens 1,2 keer; 3. hoogstens het gemiddelde; 4. hoogstens 0,8 keer |
| Veiligheid en risico's | Geen ontbonden gemeenteraad | 1. niet ontbonden in 2025 of 2026 |
| Veiligheid en risico's | Weinig aardbevingsgevaar | 1. geen zone 1; 2. zone 3 of 4; 3. alleen zone 4 |
| Veiligheid en risico's | Geen overstroming of aardverschuiving | 1. hoogstens 25% van de inwoners; 2. hoogstens 10%; 3. hoogstens 5%; 4. hoogstens 2% |
| Veiligheid en risico's | Weinig bosbrand | 1. hoogstens 10% afgebrand; 2. hoogstens 5%; 3. hoogstens 1%; 4. geen noemenswaardige brand (hoogstens 0,1%) |
| Veiligheid en risico's | Buiten vulkaangebied | 1. buiten de rode zone; 2. ook buiten de gele zone |
| Belasting, verhuur en verkoop | 7%-regime | 1. komt in aanmerking of moet nog worden nagegaan; 2. komt zeker in aanmerking |
| Belasting, verhuur en verkoop | Lage gemeentelijke belasting | 1. hoogstens 0,8%; 2. hoogstens 0,5%; 3. hoogstens 0,2%; 4. geen opslag |
| Belasting, verhuur en verkoop | Vraag voor vakantieverhuur | 1. minstens 2 per inwoner; 2. minstens 5 per inwoner; 3. minstens 10 per inwoner; 4. minstens 20 per inwoner |
| Belasting, verhuur en verkoop | Goed verkoopbaar | 1. minstens 1 per 100 woningen; 2. minstens 1,5; 3. minstens 2; 4. minstens 3 |

Bij "Levendig in de winter" en "Oud worden zonder auto" is het niveau de minimale score op de wintertest of de 80-jarentest (0 tot 100, vergeleken met alle gemeenten in Italië; zie 3.4). "Minstens 35" betekent dus: een testscore van 35 of hoger. Ongeveer 83% (wintertest) en 74% (80-jarentest) van de gemeenten haalt 35, ongeveer de helft haalt 50 en ongeveer een kwart haalt 65.

De drempels, de uitleg per niveau en de bron staan in `config/eigenschappen.json`. In `data/dekking.md` staat per eigenschap en per niveau hoeveel gemeenten afvallen als de eigenschap noodzakelijk is, en welk niveau de steekproefgemeenten halen.

### 2.4 De uitslag

- De 25 gemeenten met de hoogste score (0 tot 100), met provincie, regio, inwoners, hoogte, de wintertest en de 80-jarentest.
- Per gemeente de drie sterkste en de drie zwakste eigenschappen, en een uitklap "Alle eigenschappen" met per eigenschap de waarde, het behaalde niveau, het gevraagde niveau (gehaald of niet) en de deelscore.
- Uitleg "Hoe wordt de score berekend?" en "Wat zijn de wintertest en de 80-jarentest?" (met alle onderdelen en gewichten) boven de lijst; een tik op een testscore bij een gemeente opent die uitleg. Dezelfde uitleg staat in de pdf en bij de eigenschappen "Levendig in de winter" en "Oud worden zonder auto".
- Knop "Download de top 25 als pdf": een A4-pdf met de keuzes (in dezelfde bewoording als de app), uitleg over de score en de twee tests, de ranglijst, en per gemeente een pagina met de eigenschappen die noodzakelijk zijn of meewegen (waarde en score; oranje als het gekozen niveau niet wordt gehaald). Gemaakt in de browser met jsPDF en jspdf-autotable (MIT-licentie, meegeleverd in `web/assets/lib/`, pas geladen bij gebruik).
- Knop "Toon de scores op de kaart van Italië": een kaart met alle gemeentegrenzen (ISTAT 2026, vereenvoudigd, `web/data/kaart.json`). Elke gemeente die aan de keuzes voldoet, krijgt een kleur van rood (laagste score) via geel naar groen (hoogste score); de andere zijn grijs; de top 25 heeft een zwarte rand. Inzoomen, verschuiven en klikken op een gemeente voor naam, score en een link naar de gemeentepagina. Na een klik springt de pagina naar het resultaat: bij een gemeente uit de top 25 naar haar kaart in de lijst (die even oplicht), anders naar het infovak onder de kaart.
- Bij minder dan 25 plaatsen: per noodzakelijke eigenschap hoeveel gemeenten die op zichzelf wegstreept, de strengste eerst, met de knoppen "Eén niveau soepeler" en "Laat meewegen in plaats van noodzakelijk".
- Badge "trend" voor regio's en provincies die volgens het jaarrapport van Gate-away.com in opkomst zijn (handmatige tabel, geen score).
- Een deelbare link met alle keuzes, een overzicht "Uw keuzes" met per stap "Wijzigen", en tot drie gemeenten om te vergelijken (alle eigenschappen naast elkaar).

### 2.5 De gemeentepagina

- Score met de keuzes van de bezoeker, en een melding als de gemeente niet in de ranglijst staat (noodzakelijk niveau niet gehaald of buiten de keuzes).
- Alle eigenschappen met waarde, niveau en deelscore, en per eigenschap de bron (bron, peildatum, schaalniveau).
- Extra: gidsprijs per regio, directe vluchten vanaf de dichtstbijzijnde jaarrond-luchthaven (Bijlage A), hogesnelheids- en nachttreinstation, melding als hete dagen en vorstdagen geschat zijn.
- Risicoblok (altijd): seismische zone, aardverschuiving, overstroming, bosbrand, vulkaan (rode of gele zone), met een link naar de IdroGEO-kaart.
- Wintertest en 80-jarentest met hun onderdelen; waarschuwing bij een 80-jarentest onder 40.
- 7%-regime, herverkoop, label ontbonden gemeenteraad, checklist, "Meer hierover in de gids" en drie alternatieven (ook buiten het zoekgebied, dan gemarkeerd).

## 3. Wat de tool berekent

### 3.1 Behaald niveau en percentiel

Per gemeente en per eigenschap rekent de pipeline het hoogste niveau uit waaraan de gemeente voldoet (0 = geen enkel; zonder data: geen niveau), en het percentiel van de waarde over heel Italië (0 tot 100, 100 is het beste). Afgeleide waarden: criminaliteit als veelvoud van het landelijk gemiddelde (gewogen naar inwoners), risico water als het hoogste van aardverschuiving (P3/P4) en overstroming (P3).

### 3.2 Deelscore per eigenschap

Deelscore = 85 x (behaald niveau / gevraagd niveau, hoogstens 1) + 15 x percentiel / 100. Wie het gevraagde niveau haalt, krijgt dus minstens 85; de laatste 15 punten maken onderscheid tussen gemeenten die hetzelfde niveau halen.

### 3.3 Totaalscore

Totaal = gewogen gemiddelde van de deelscores: noodzakelijk 10, meewegen 1 tot 9, onbelangrijk 0. Ontbreekt een gegeven, dan telt die eigenschap niet mee. Zoekgebied, ligging en grootte zijn filters; de rode zone van de Vesuvius en de Campi Flegrei valt altijd af.

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
- Klimaatverandering (extra hete dagen en verandering van de neerslag, 2016-2025 tegenover 1991-2000) wordt nog berekend (in `data/comuni.json`), maar is geen eigenschap meer in de wizard en staat niet meer op de site.

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
| Protezione Civile, zona gialla | gele zone Vesuvius en Campi Flegrei | 2016 en 2019 | gemeente | open |
| ISTAT, presenze per provincie | overnachtingen per inwoner (provinciecijfer verdeeld naar het aandeel bedden; schatting) | 2024 | provincie | CC BY 4.0 |
| Ministero della Salute, NSG per gebied | zwakke regionale zorg (laagste van drie gebiedsscores) | 2023 | regio | open |

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
- Internationale scholen: nog geen bron; staat niet in de eigenschappen.

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

- `php web/tests/test_model.php`: profielgewichten, startkeuzes voor alle eigenschappen, aantal niveaus, noodzakelijk sluit uit op het gekozen niveau, meewegen en onbelangrijk niet, vaste gewichten 10 en 0, begrenzing van gewicht en niveau, onbelangrijk en labels veranderen de score niet, hoger niveau geeft een hogere deelscore, standaardeis gepensioneerde (45 minuten), rode zone (Ercolano), zoekgebied Pavia met Montecalvo Versiggia, zelfde score bij heel Italië en eigen regio, alternatieven buiten het zoekgebied, te weinig plaatsen, gids-link.
- `python -m unittest discover -s pipeline/tests`: configuratie, 7%-regime, teksten, bronnen.
- Steekproef: zes gemeenten (Montecalvo Versiggia, Greve in Chianti, Casale Monferrato, Montegiorgio, Gerace, Ercolano) vergeleken met de ruwe bronbestanden; alle waarden kloppen.

### 7.4 Belangrijke configuratiebestanden

| Bestand | Inhoud |
|---|---|
| `config/eigenschappen.json` | de 32 eigenschappen met groep, kolom, richting, eenheid, bron, niveaus en uitleg; gewichten; startkeuzes per profiel |
| `config/criteria.json` | criteria en indicatoren (achtergrond voor de wintertest, de 80-jarentest en de koppeling met de profielen); gepland |
| `config/profielen.json` | profielen, gewichten per criterium (basis voor de startkeuzes), vervolgvragen |
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
