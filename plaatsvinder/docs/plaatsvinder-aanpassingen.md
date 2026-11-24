# Opdracht voor Claude Code: Plaatsvinder Italië aanpassen aan het criteria-onderzoek

## 0. Context

De Plaatsvinder Italië is een Nederlandstalige tool op stefsmulders.nl (PHP + JSON-data, sleutel per gemeente = ISTAT-code) die kopers helpt een Italiaanse gemeente te kiezen, naar voorbeeld van magictowns.it/find-your-town. Het bouwplan staat in het document "Plaatsvinder Italië: bronnen- en bouwplan". Een onderzoek naar de criteria van buitenlandse kopers levert de aanpassingen hieronder op.

**Werkwijze voor jou (Claude Code):**
1. Lees eerst de bestaande code, datapipeline en configuratie in deze repository. Bestaat er nog niets, bouw dan volgens het bouwplan en neem deze opdracht meteen mee.
2. Maak een kort plan (welke bestanden je aanmaakt of wijzigt) en werk daarna fase voor fase (sectie 9). Commit na elke fase.
3. Schrijf alle teksten in de interface in het Nederlands. Gebruik nergens het gedachtestreepje (em-dash); gebruik komma's, haakjes of een nieuwe zin.
4. Scrape geen commerciële sites (Immobiliare.it, Idealista, Gate-away, Il Sole 24 Ore). Gebruik alleen open bronnen, of handmatig ingevoerde tabellen met bronvermelding.
5. Elke dataset krijgt een veld `bron`, `url`, `peildatum` en `schaalniveau` (gemeente, provincie, regio, raster). De interface toont die bij elk cijfer.

## 1. Criteria en hun rangorde

Maak `config/criteria.json` met per criterium: `id`, `label`, `uitleg`, `rang`, `basis` (`D` = enquêtedata, `DE` = deels data deels expertoordeel, `E` = expertoordeel), `richting` (hoger is beter of slechter), `bronnen`, `schaalniveau`.

| Rang | id | Label | Basis |
|---|---|---|---|
| 1 | prijs | Budget en prijsniveau | D |
| 2 | natuur | Natuur, landschap, rust en ruimte | D |
| 3 | klimaat | Klimaat (winter en zomerhitte) | D |
| 4 | cultuur | Cultuur, eten, wijn, authenticiteit | D |
| 5 | bereikbaarheid | Luchthaven en rijafstand naar huis | DE |
| 6 | voorzieningen | Dagelijkse voorzieningen | D |
| 7 | zorg | Gezondheidszorg | D |
| 8 | internet | Snel internet en glasvezel | D |
| 9 | fiscaal | Belastingen en fiscale regelingen | D |
| 10 | levendigheid | Levendigheid versus isolatie (wintertest) | E |
| 12 | kosten | Kosten van levensonderhoud | D |
| 13 | risico | Natuurrisico's | E |
| 14 | veiligheid | Veiligheid en criminaliteit | DE |
| 15 | expats | Andere buitenlanders in de gemeente | DE |
| 17 | klimaatverandering | Hitte en droogte | E |
| 18 | toerisme | Verhuurpotentieel en toerisme | D |
| 19 | liquiditeit | Herverkoopbaarheid | E |

Rang 11 (staat van de woning), 16 (taal en mentaliteit), 20 (werk), 21 (hinder door landbouw en jacht) en 22 (subsidies en 1-euro-huizen) zitten niet in de score. Toon ze als vaste aandachtspunten op de resultaatpagina (sectie 7).

Toon in de interface bij elk criterium een klein label met de basis ("op basis van enquêtes onder kopers" of "expertoordeel"), zodat de gebruiker ziet waarop de rangschikking berust.

## 2. Nieuwe en aangepaste databronnen

Koppel alles op ISTAT-gemeentecode. Neem per bron een downloadscript op in de pipeline en documenteer handmatige stappen in `data/README.md`. Zoek zelf de actuele download-URL op als een link hieronder naar een overzichtspagina wijst.

| Criterium | Bron | Niveau | Bewerking |
|---|---|---|---|
| prijs | OMI quotazioni (Agenzia delle Entrate) | OMI-zone | Per gemeente: mediaan van de min/max €/m² voor woningen in normale staat; ook de laagste zone apart bewaren |
| liquiditeit | OMI forniture, NTN-transacties | gemeente | Transacties per 100 woningen (woningvoorraad uit ISTAT-census) |
| risico (aardbeving) | Protezione Civile, classificazione sismica | gemeente | Zone 1 tot 4; regionale lijsten, nieuwste versie per regio |
| risico (aardverschuiving, overstroming) | ISPRA IdroGEO, indicatoren per gemeente | gemeente | Percentage inwoners in aardverschuivingszone P3+P4 en in overstromingsscenario P2 |
| risico (bosbrand) | EFFIS (Copernicus) | polygonen | Afgebrande oppervlakte per gemeente, som laatste 10 jaar, als percentage van het oppervlak |
| risico (vulkaan) | Protezione Civile, zona rossa Vesuvio en Campi Flegrei | gemeente | Ja/nee |
| klimaatverandering | Copernicus ERA5-Land (of ISPRA SCIA) | raster | Per gemeentecentroïde: dagen boven 32 °C, neerslagtrend; droogte vanuit ISPRA alleen als grove regiolaag |
| zorg (regio) | Ministero della Salute, LEA/NSG 2023 | regio | Statische tabel, zie sectie 3 |
| zorg (lokaal) | dati.salute.gov.it (ziekenhuizen met spoedeisende hulp, apotheken) | adres | Rijtijd per gemeente met OSRM |
| levendigheid | SNAI-classificatie aree interne 2021 tot 2027 (ISTAT/DPCoe) | gemeente | Klasse: polo, polo intercomunale, cintura, intermedio, periferico, ultraperiferico |
| levendigheid | ISTAT demo.istat.it | gemeente | Bevolkingsontwikkeling over 10 jaar, aandeel 65-plussers |
| expats | ISTAT stranieri residenti per cittadinanza | gemeente | Per 1.000 inwoners: Nederlanders, Belgen, Duitsers, Britten, en totaal |
| internet | AGCOM Broadband Map (+ Infratel BUL-status) | adres, aggregaat gemeente | Percentage adressen met FTTH, en met minimaal 100 Mbit/s |
| toerisme | ISTAT capacità e movimento esercizi ricettivi | gemeente (bedden), provincie (overnachtingen per maand) | Bedden per inwoner; seizoensindex = aandeel overnachtingen in juni tot september |
| fiscaal | ISTAT inwoners per 1 januari + regio | gemeente | 7%-regime, zie sectie 4 |
| fiscaal | Dipartimento delle Finanze, IMU-tarief en addizionale comunale IRPEF | gemeente | IMU-tarief tweede woning |
| veiligheid | Il Sole 24 Ore, Indice della criminalità | provincie | Alleen als handmatige tabel met bronvermelding, en alleen als de voorwaarden dat toelaten. Anders weglaten en dat melden |
| bereikbaarheid | OpenStreetMap + OSRM | gemeente | Rijtijd naar Utrecht, Brussel, Keulen, München; rijtijd naar de 3 dichtstbijzijnde luchthavens met lijnvluchten |
| bereikbaarheid | Assaeroporti (passagiers per luchthaven) + handmatige lijst | luchthaven | Per luchthaven: passagiers, en of er het hele jaar vluchten naar NL, BE of DE zijn (veld `heel_jaar`). Seizoensroutes tellen niet voor de winter |

Labels zonder score (handmatige tabel `config/trends.json`, met bron en jaar): "In opkomst bij buitenlandse kopers" voor Piemonte, Friuli-Venezia Giulia, Trentino-Alto Adige, en de provincies Vercelli, Biella, Novara, Fermo en Viterbo (Gate-away, jaarrapport 2025). Populariteit telt niet mee in de score.

## 3. Statische tabel zorgkwaliteit (LEA 2023)

Maak `config/lea_2023.json` (bron: Ministero della Salute, monitoraggio LEA/NSG 2023, maximum 300). Controleer de waarden tegen het ministeriële document en vul ontbrekende regio's aan (Campania, Valle d'Aosta). Gebruik de ministeriële score, niet die van GIMBE.

```
Veneto 288, Toscana 286, PA Trento 278, Emilia-Romagna 278, Piemonte 270,
Lombardia 257, Umbria 257, Marche 248, Friuli-Venezia Giulia 235, Puglia 228,
Liguria 219, Lazio 216, PA Bolzano 202, Molise 193, Sardegna 192,
Basilicata 189, Abruzzo 182, Sicilia 173, Calabria 150
```

Let op: Trentino-Alto Adige bestaat hier uit twee autonome provincies met elk een eigen score.

## 4. Het 7%-regime (art. 24-ter TUIR)

Functie `in_aanmerking_7procent(istat_code, jaar_eerste_optie)` met unittests.

- Regio is een van: Sicilia, Calabria, Sardegna, Campania, Basilicata, Abruzzo, Molise, Puglia.
- Inwonertal op 1 januari van het jaar vóór het eerste jaar van de optie is maximaal 30.000 (sinds wet nr. 34 van 11 maart 2026, van kracht 7 april 2026; daarvoor 20.000). Zet de grens in een configuratiewaarde.
- Daarnaast de gemeenten in de aardbevingskraters van 2009 en 2016 (delen van Lazio, Le Marche, Umbrië, Abruzzo). Laad die lijst uit een apart bestand `config/kratergemeenten.json`. De bronnen spreken elkaar tegen over de inwonersgrens daar; zet dit in de configuratie als `inwonersgrens_krater: "te_verifieren"` en toon bij deze gemeenten de tekst "Controleer de actuele wettekst".
- Toon altijd de disclaimer: geldt alleen voor gepensioneerden met een buitenlands pensioen die fiscaal inwoner van Italië worden en de vijf jaar ervoor dat niet waren. Geen belastingadvies.
- Bij gemeenten die in aanmerking komen en een LEA-score onder 200 hebben, toon een waarschuwing: "Fiscaal gunstig, maar de regionale zorg scoort laag."

## 5. Scoremodel: twee lagen

### 5a. Harde filters (ja/nee, gemeente valt af)

| Filter | Standaard | Gebruiker kan |
|---|---|---|
| Maximale prijs per m² (OMI) | uit | bedrag invullen |
| Maximale rijtijd naar luchthaven met vluchten het hele jaar | uit | 30, 60, 90 min |
| Maximale rijtijd naar ziekenhuis met spoedeisende hulp | uit (bij profiel gepensioneerde: 45 min aan) | 20, 30, 45, 60 min |
| Maximale rijtijd naar Utrecht, Brussel, Keulen of München | uit | aantal uren |
| Seismische zone 1 uitsluiten | uit | aan/uit |
| Vulkanische rode zone uitsluiten | aan | aan/uit |
| Minimaal aandeel adressen met snel internet | uit (bij remote worker: 50% met 100 Mbit/s aan) | percentage |
| Alleen gemeenten met 7%-regime | uit | aan/uit |

### 5b. Gewogen score (0 tot 100)

- Normaliseer elke indicator naar 0 tot 100 met percentielen over alle gemeenten (robuust tegen uitschieters). Draai de richting om waar lager beter is.
- Ontbrekende data: herverdeel het gewicht over de andere criteria en toon "geen data" bij dat criterium. Vul nooit stilzwijgend een gemiddelde in.
- Grove bronnen (regio, provincie) gelden voor alle gemeenten daarbinnen; toon het schaalniveau.
- Toon naast de totaalscore de deelscores per criterium, zodat de gebruiker ziet waarom een plaats hoog of laag staat.

## 6. Profielen met standaardgewichten

Maak `config/profielen.json`. De gebruiker kiest eerst een profiel, ziet daarna de gewichten als schuifjes en kan ze aanpassen. Gewichten tellen op tot 100. Dit zijn startwaarden op basis van expertoordeel; markeer ze zo in de interface.

| Criterium | Gepensioneerde / emigrant | Tweede huis | Gezin met kinderen | Remote worker | Investeerder / B&B |
|---|---|---|---|---|---|
| prijs | 15 | 15 | 12 | 12 | 15 |
| natuur | 10 | 15 | 6 | 6 | 7 |
| klimaat | 12 | 8 | 6 | 10 | 0 |
| cultuur | 5 | 8 | 2 | 6 | 8 |
| bereikbaarheid | 8 | 18 | 8 | 16 | 12 |
| voorzieningen | 12 | 4 | 18 | 8 | 0 |
| zorg | 15 | 0 | 8 | 0 | 0 |
| internet | 2 | 3 | 12 | 22 | 3 |
| levendigheid (wintertest) | 8 | 0 | 12 | 12 | 0 |
| risico | 8 | 8 | 8 | 6 | 10 |
| veiligheid | 3 | 5 | 8 | 2 | 5 |
| expats | 2 | 0 | 0 | 0 | 0 |
| toerisme | 0 | 10 | 0 | 0 | 22 |
| liquiditeit | 0 | 6 | 0 | 0 | 18 |
| **Totaal** | **100** | **100** | **100** | **100** | **100** |

Extra bij profielen:
- **Gepensioneerde:** vraag "Wilt u er permanent wonen of het jaar verdelen tussen Italië en thuis?" Bij "verdelen" telt bereikbaarheid mee met het gewicht van het profiel Tweede huis (18), en levert natuur het verschil in. De 80-jarentest (7b) is standaard zichtbaar.
- **Gezin:** voeg aan voorzieningen de aanwezigheid van basisschool en middelbare school binnen 20 minuten toe (Ministero dell'Istruzione open data).
- **Expats:** laat de gebruiker kiezen "liever veel landgenoten / maakt niet uit / liever weinig". Bij "liever weinig" draait de richting om. Bij "maakt niet uit" is het gewicht 0.
- **Cultuur:** gebruik de proxy uit het bouwplan als die bestaat. Bestaat er nog geen, gebruik in versie 1 een OSM-telling van historische objecten en musea binnen 5 km en de aanwezigheid van DOC/DOCG-wijngebied, en markeer de proxy als voorlopig.

## 7. De twee onderscheidende tests

Toon beide als aparte score (0 tot 100) met een eigen kaartje op de resultaatpagina, ook als ze in het gekozen profiel geen gewicht hebben. Leg in één zin uit wat de test meet.

### 7a. Wintertest ("Hoe is het hier in januari?")

| Onderdeel | Gewicht binnen de test |
|---|---|
| SNAI-klasse (polo = 100, ultraperiferico = 0, lineair) | 30 |
| Bevolkingsontwikkeling over 10 jaar | 20 |
| Aandeel 65-plussers (lager is beter) | 10 |
| Supermarkt, apotheek en bar binnen 800 m van het centrum (OSM) | 20 |
| Seizoensindex toerisme (hoge zomerpiek ten opzichte van inwoners is slechter) | 10 |
| Dagen met sneeuw of vorst, of hoogteligging als die data ontbreekt | 10 |

### 7b. 80-jarentest ("Kunt u hier oud worden?")

| Onderdeel | Gewicht binnen de test |
|---|---|
| Rijtijd naar ziekenhuis met spoedeisende hulp | 30 |
| Rijtijd naar apotheek en huisarts | 20 |
| Supermarkt op loopafstand van het centrum | 15 |
| Openbaar vervoer (afstand tot station, uit SNAI-gegevens) | 15 |
| LEA-score van de regio | 20 |

Toon bij een score onder 40 de tekst: "Wie hier op latere leeftijd woont, is sterk afhankelijk van de auto." Onderbouwing in de uitleg: één op de drie Nederlandse pensioenmigranten in Italië en Frankrijk denkt aan terugkeer, met gezondheid als hoofdreden (NIDI).

## 8. Resultaatpagina en eerlijkheid

1. **Risico's altijd tonen.** Elke gemeente krijgt een risicoblok met seismische zone, IdroGEO-percentages, bosbrand en vulkaan, met link naar IdroGEO voor controle van het perceel zelf. Geen uitzonderingen, ook niet voor Oltrepò Pavese of Montecalvo Versiggia.
2. **Herverkoop.** Toon het aantal transacties per jaar. Bij minder dan 1 transactie per 100 woningen: "Verkopen kan hier lang duren."
3. **Vaste aandachtspunten** (checklist onderaan elke gemeentepagina):
   - Bezoek de plaats minstens één keer in januari of februari.
   - Controleer of vluchten ook in de winter gaan.
   - Vraag naar jacht, bespuitingen in wijngaarden en boomgaarden, en bedrijven in de buurt.
   - Controleer de staat van de woning en de bouwregels (vergunningen, beschermd gebied).
   - Reken niet op huurinkomsten buiten de toeristische kernen.
   - Spreek de taal of leer die; de gemeente, de ASL en de vakmensen werken in het Italiaans.
4. **Trendlabel** "In opkomst bij buitenlandse kopers" als badge, met de uitleg dat populariteit de prijzen opdrijft en geen kwaliteitsmaat is.
5. **Vergelijken.** Laat de gebruiker tot 3 gemeenten naast elkaar zetten. Toon bij een gekozen gemeente automatisch 3 vergelijkbare alternatieven met een hogere score (zelfde profiel, binnen de harde filters), want kopers vergelijken zelden met onbekende regio's.

## 9. Volgorde van werken

1. **Fase 1, configuratie:** `criteria.json`, `profielen.json`, `lea_2023.json`, `trends.json`, `kratergemeenten.json` en de bijbehorende schema-validatie.
2. **Fase 2, data:** downloadscripts en bewerking per bron uit sectie 2, één tabel per bron, daarna één samengevoegd bestand per gemeente (JSON of SQLite, wat de bestaande code gebruikt). Rapporteer per bron de dekking (aantal gemeenten met data van de circa 7.900).
3. **Fase 3, rekenen:** normalisatie, harde filters, gewogen score, wintertest, 80-jarentest, 7%-functie.
4. **Fase 4, interface:** profielkeuze, schuifjes, filters, resultaatlijst, gemeentepagina met deelscores, risicoblok, testkaartjes, bronvermelding en basislabels.
5. **Fase 5, controle:** zie sectie 10.

## 10. Acceptatiecriteria en tests

- Unittests voor `in_aanmerking_7procent`: een gemeente in Puglia met 25.000 inwoners (ja), een in Puglia met 35.000 (nee), een in Toscane met 5.000 (nee), en een kratergemeente (melding "te verifiëren").
- Gewichten van elk profiel tellen op tot 100; de test faalt als dat niet zo is.
- Steekproef met deze gemeenten; controleer per bron dat de waarden overeenkomen met de bron zelf en zet de uitkomst in `data/steekproef.md`:
  - Montecalvo Versiggia (PV, Oltrepò Pavese)
  - een gemeente in de Chianti (bijv. Greve in Chianti)
  - een gemeente in Monferrato (bijv. Casale Monferrato)
  - een gemeente in Le Marche in de provincie Fermo
  - een kleine gemeente in Calabrië
  - een gemeente in de zona rossa van de Vesuvius (moet standaard wegvallen)
- Met het profiel Gepensioneerde mag geen gemeente met een rijtijd boven 45 minuten naar spoedeisende hulp in de resultaten staan zolang dat filter aan staat.
- Elke getoonde waarde heeft een bron, peildatum en schaalniveau.
- Geen enkele tekst in de interface bevat een gedachtestreepje (zoek in de code naar het teken en vervang).

## 11. Wat je moet melden in plaats van zelf invullen

Stop en meld het als:
- een bron niet openbaar te downloaden is of een account vraagt (bijv. Copernicus);
- de voorwaarden van een bron hergebruik verbieden;
- de inwonersgrens voor kratergemeenten niet uit de wettekst vast te stellen is;
- de LEA-waarden in het ministeriële document afwijken van de tabel in sectie 3.
