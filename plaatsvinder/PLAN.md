# Plaatsvinder Italië: bronnen- en bouwplan

Nov 23, 2026 · @Stef Smulders

## Samenvatting

Een Nederlandse plaatsvinder naar het voorbeeld van [Magic Towns Find Your Town](https://magictowns.it/find-your-town/) is te bouwen met open data, voor ongeveer 85 procent van de onderdelen. Magic Towns noemt zelf ISTAT, Eurostat, het Ministero della Salute en eigen woningaanbod als bronnen; op de plaatsprofielen staan daarnaast OMI (Agenzia delle Entrate) en de ISTAT-volkstellingen van 2011 en 2021.

De opzet: één vooraf berekende tabel met ongeveer 7.900 comuni en 40 tot 60 kolommen, eens per half jaar ververst. De wizard op stefsmulders.nl stuurt de antwoorden naar een klein PHP-script dat filtert, scoort en de beste 25 plaatsen teruggeeft. Het zware rekenwerk (reistijden, tellingen uit OpenStreetMap) gebeurt eenmalig offline, niet bij elke bezoeker.

Niet na te bouwen zijn de vraagprijzen uit hun eigen aanbod en vermoedelijk de schoolscores per school. Daarvoor staan alternatieven in de laatste sectie.

## De acht vragen en wat ze in de tabel vragen

Elke vraag werkt als filter (plaats valt af) of als gewicht (plaats zakt of stijgt). Definities in de tweede kolom komen van Magic Towns zelf.

| Stap | Vraag | Definitie bij Magic Towns | Benodigde kolom(men) | Rol |
| --- | --- | --- | --- | --- |
| 1 | Ligging: kust, meer, heuvel, berg, vlakte | Kust/meer: binnen 30 min rijden van aangewezen zwemwater. Heuvel: 300–600 m. Berg: boven 600 m of binnen 20 min van een skigebied. Vlakte: onder 300 m en niet aan de kust | `min_naar_zee`, `min_naar_meer`, `hoogte_m`, `min_naar_skigebied` | filter (meerkeuze, OR) |
| 2 | Grootte | Tiny < 1.000, small 1.000–5.000, medium 5.000–20.000, large 20.000–100.000, metro > 100.000 | `inwoners` | filter |
| 3 | Wie ben je | Gepensioneerde, thuiswerker, forens, werkzoekend, anders | geen; verschuift gewichten | gewicht |
| 4 | Top 3 prioriteiten | Eerste keuze telt het zwaarst | één score per prioriteit (zie bronnen) | gewicht |
| 5 | Extra prioriteiten | Elke keuze telt 3× | idem | gewicht |
| 5b | Scholen (alleen als gekozen) | Beste school per niveau binnen 5 km | `school_basis`, `school_midden`, `school_hoog` | gewicht |
| 6 | Bereikbaarheid | Max. rijtijd naar luchthaven en treinstation; optioneel werkplek + straal | `min_naar_luchthaven`, `min_naar_station`, `lat`, `lon` | filter |
| 7 | Budget | Huren of kopen, max. bedrag, woninggrootte | `huur_m2`, `koop_m2` | filter + score betaalbaarheid |
| 8 | Regio's uitsluiten | Alle 20 regio's standaard aan | `regio` | filter |

## Bronnen per onderdeel

Alle bronnen hieronder zijn gratis. De koppelsleutel is overal de ISTAT-comunecode (6 cijfers, bijvoorbeeld 018095), behalve bij geodata, die je via coördinaten aan een comune koppelt.

| Onderdeel | Bron | Download | Formaat | Licentie / voorwaarde | Ververs | Bewerking |
| --- | --- | --- | --- | --- | --- | --- |
| Basislijst comuni, regio, provincie | ISTAT Elenco comuni italiani | [xlsx, stand 21-02-2026](https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.xlsx) | xlsx | open | bij elke fusie van comuni | basis van de hele tabel |
| Grenzen en middelpunt comune | ISTAT Confini amministrativi 2026 | [generalizzati (zip)](https://www.istat.it/storage/cartografia/confini_amministrativi/generalizzati/2026/Limiti01012026_g.zip) | shapefile WGS84 | open | jaarlijks | middelpunt en oppervlakte per comune |
| Inwoners | ISTAT demo, bilancio demografico | [P2\_2025 comuni (zip)](https://demo.istat.it/data/p2/P2_2025_it_Comuni.zip) | csv | open | jaarlijks | stap 2 (grootte) |
| Hoogte | ISTAT, "altitudine del centro" in de tabel Principali statistiche geografiche | via [istat.it codici comuni](https://www.istat.it/classificazione/codici-dei-comuni-delle-province-e-delle-regioni/) | xlsx | open | zelden | heuvel/berg/vlakte |
| Zwemwater zee en meer | EEA Bathing Water Directive | [EEA dataset](https://www.eea.europa.eu/data-and-maps/data/bathing-water-directive-status-of-bathing-water-5) | csv + coördinaten | open | jaarlijks | rijtijd naar dichtstbijzijnde punt, gesplitst zee/meer |
| Kaartdata en wegennet | OpenStreetMap via Geofabrik | [italy-latest.osm.pbf, 2,1 GB](https://download.geofabrik.de/europe/italy/italy-latest.osm.pbf) | pbf | ODbL, naamsvermelding | naar keuze | voorzieningen tellen, rijtijden, skigebieden, stations, luchthavens |
| Koop- en huurprijzen | Agenzia delle Entrate, Forniture dati OMI | [uitleg + login](https://www.agenziaentrate.gov.it/portale/schede/fabbricatiterreni/omi/forniture-dati-omi) | csv per semester | CC-BY 4.0, vermelding «Agenzia delle Entrate – OMI», gratis account nodig | halfjaarlijks | mediaan €/m² woning per comune |
| Afval en recycling | ISPRA Catasto Rifiuti | [csv 2024](https://www.catasto-rifiuti.isprambiente.it/get/getDettaglioComunale.csv.php?&aa=2024) | csv | open | jaarlijks | % gescheiden ingezameld |
| Inkomen (economie) | MEF Dipartimento Finanze, IRPEF per comune | [open data-pagina](https://www.finanze.gov.it/it/statistiche-fiscali/open-data-comunale-principali-variabili-irpef/) | csv | open | jaarlijks | gemiddeld inkomen, indicatie voor economie |
| Scholen (ligging) | MIM Anagrafe scuole statali | [csv 2026/27](https://dati.istruzione.it/opendata/opendata/catalogo/elements1/leaf/SCUANAGRAFESTAT20262720260901.csv) | csv | IODL 2.0 | jaarlijks | aanwezigheid per niveau binnen 5 km |
| Apotheken | Ministero della Salute open data | [dati.salute.gov.it](https://www.dati.salute.gov.it/?tag=Medicinali) | csv | open | doorlopend | aantal per 1.000 inwoners |
| Ziekenhuizen | Ministero della Salute, strutture di ricovero | [salute.gov.it](https://www.salute.gov.it/new/it/banche-dati/elenco-aziende-sanitarie-locali-e-strutture-di-ricovero/) | csv/pdf | open | jaarlijks | rijtijd naar dichtstbijzijnde ziekenhuis |
| Breedband | AGCOM Broadband Map, Open Maps | [maps.agcom.it](https://maps.agcom.it/) | csv | open | halfjaarlijks | % adressen met ≥ 1 Gbit/s |
| Luchtkwaliteit | EEA, jaargemiddelde PM2,5 | [EEA kaarten](https://www.eea.europa.eu/en/analysis/maps-and-charts/index) | raster/csv | open | jaarlijks | PM2,5 op middelpunt comune |
| Bevolkingsdata buitenlanders (optioneel) | ISTAT popolazione straniera | [STRASA\_2026 comuni (zip)](https://demo.istat.it/data/strasa/STRASA_2026_it_Comuni.zip) | csv | open | jaarlijks | extra filter "internationale gemeenschap" |

Nog te controleren voordat je bouwt: het exacte downloadbestand van de ziekenhuizen (op de pagina staat ook een PDF), de AGCOM-export per comune en de EEA-luchtkwaliteitslaag. Die pagina's heb ik gevonden maar de bestanden zelf niet geopend.

## Wat het plaatsprofiel van Castana prijsgeeft

Het volledige profiel van Castana (PV), bekeken op 23-11-2026 met een gratis account, noemt bij de meeste cijfers de bron. "Genoemd" betekent dat Magic Towns de bron op de pagina zet; "afgeleid" betekent dat de pagina de methode beschrijft en ik de open bron erbij heb gezocht.

| Cijfer op het profiel | Wat Magic Towns zegt | Open bron voor ons | Status |
| --- | --- | --- | --- |
| Inwoners, geboorten, sterfte, leeftijd | ISTAT, tot 31-12-2025 | [demo.istat.it](https://demo.istat.it/) | genoemd |
| Inkomen per belastingbetaler | MEF, 2024 | [IRPEF per comune](https://www.finanze.gov.it/it/statistiche-fiscali/open-data-comunale-principali-variabili-irpef/) | genoemd |
| Banen binnen 30 min, banen per werkende | ISTAT bedrijvenregister 2023 en pendelcijfers 2021; rijtijden eigen werk | [ISTAT pendelmatrix 2021](https://www.istat.it/notizia/matrice-di-pendolarismo-per-lavoro/) | genoemd |
| Werkloosheid | "gemodelleerd, niet geteld" | eigen schatting nodig | eigen werk |
| Officiële woningwaarde, verkochte woningen | OMI per semester, NTN-transacties | OMI-forniture | genoemd |
| Bouwjaar, staat, bewoning | ISTAT-census 2011 per censussectie; bewoning census 2021 | [ISTAT basi territoriali](https://www.istat.it/notizia/basi-territoriali-e-variabili-censuarie/) | genoemd |
| Scholen: Italiaans, wiskunde, Engels per school, vs regio en Italië | INVALSI-toetsen; schooldag uit MIM-inschrijvingen 2024/25 | per school zichtbaar in [Scuola in Chiaro](https://unica.istruzione.gov.it/portale/it/scuola-in-chiaro), geen bulkbestand gevonden | genoemd |
| Zee en meer | aangewezen zwemwater met kwaliteitsklasse ("Scarlco Sud, Excellent") | EEA Bathing Water | genoemd |
| Zorgscore | AGENAS-ziekenhuiskwaliteit per ASL plus reisafstand | [AGENAS PNE](https://pne.agenas.it/) | genoemd |
| Gemeentediensten (afval, wegen, politie, crèches, zorg) | "de jaarrekening die elke comune indient", vergeleken met even grote comuni | [OpenBDAP, bilanci enti territoriali](https://openbdap.rgs.mef.gov.it/it/FET/Esplora) | afgeleid |
| Natuurrisico: overstroming, aardverschuiving, aardbeving | "1 op 10 inwoners in gekarteerd aardverschuivingsgebied" | [ISPRA IdroGEO](https://idrogeo.isprambiente.it/app/page/open-data), CC BY 4.0 | afgeleid |
| Klimaat per maand, gevoelstemperatuur | gemodelleerd klimaatoppervlak, langjarige maandnormalen | [ERA5-Land maandgemiddelden](https://cds.climate.copernicus.eu/datasets/reanalysis-era5-land-monthly-means) | afgeleid |
| Luchtkwaliteit, 5 stoffen per maand, EAQI-klassen | Europese luchtkwaliteitsindex | [CAMS European air quality reanalyses](https://ads.atmosphere.copernicus.eu/datasets/cams-europe-air-quality-reanalyses) | afgeleid |
| Breedband 206 Mbps, upload 122 Mbps | gemeten snelheden met bandbreedte | [Ookla open data](https://github.com/teamookla/ookla-open-data) | afgeleid |
| Buitenlanders per nationaliteit | ISTAT-burgerschapsreeks | ISTAT STRASA | genoemd |
| Voorzieningen binnen 10 km, met nationaal percentiel | alleen tellingen | OpenStreetMap | afgeleid |
| Luchthavens en stations met cijfer x/10 | eigen beoordeling | eigen werk | eigen werk |

Twee ontwerpkeuzes om over te nemen. Waar een cijfer niet per kleine comune bestaat, valt Magic Towns terug op de provincie en meldt het aandeel ("75% gemeten voor Castana zelf"). En bij te weinig woningaanbod tonen ze geen vraagprijs maar alleen de OMI-waarde.

## Bewerkingsstappen

Alle bronnen komen samen in één tabel met één rij per comune; pas die tabel gaat naar de server.

&#91;embedded content: datapipeline · 6 stappen\]

Stap 4 kost de meeste tijd: OSRM rekent per comune de rijtijd naar de dichtstbijzijnde zee, meer, skigebied, station, luchthaven en ziekenhuis. Dat draait lokaal in Docker op het Italië-bestand van Geofabrik en hoeft maar twee keer per jaar.

## Scoremodel

Het model werkt in twee rondes: eerst filters die plaatsen hard uitsluiten, daarna een gewogen gemiddelde van percentielscores. Zo blijft het uitlegbaar: elke plaats krijgt een totaal van 0 tot 100 en per prioriteit een deelscore.

1. **Filters** (stap 1, 2, 6, 7, 8): ligging, grootte, maximale rijtijden, maximale prijs en uitgesloten regio's. Na elke stap toont de wizard hoeveel plaatsen er nog over zijn, net als het origineel.
2. **Normaliseren**: elke indicator wordt een percentiel van 0 tot 100 over alle comuni. Waar laag beter is (rijtijd, PM2,5, prijs) draai je de schaal om.
3. **Gewichten**: top 3-prioriteiten krijgen 5, 4 en 3 punten. Elke extra prioriteit telt 3 punten, zoals bij Magic Towns. Niet gekozen indicatoren tellen 1 punt mee, zodat de lijst niet alleen door drie kolommen bepaald wordt.
4. **Profiel**: stap 3 verschuift gewichten. Voorstel: gepensioneerde +2 op ziekenhuizen en apotheken; thuiswerker +2 op breedband; forens +2 op station en rijtijd naar werk; werkzoekend +2 op economie.
5. **Betaalbaarheid**: alleen als er een budget is ingevuld. Score = hoe ver de geschatte woonlast onder het budget blijft. Geschatte prijs = OMI €/m² × gekozen woninggrootte.
6. **Ontbrekende data**: telt niet als nul. Het gewicht van die indicator gaat naar de andere, en de uitslag toont "geen gegevens" bij die plaats.
7. **Uitslag**: top 25 met totaal en de drie sterkste en zwakste punten per plaats, in gewone taal ("apotheek in de plaats, ziekenhuis op 18 minuten").

De gewichten in punt 3 en 4 zijn een eerste voorstel en geen kopie van Magic Towns; hun exacte formule staat niet op de site.

## Technische opzet op stefsmulders.nl

De tool draait op dezelfde PHP-hosting als je andere calculators; alleen de voorbereiding vraagt een computer met Python en Docker.

| Onderdeel | Waar | Techniek | Omvang |
| --- | --- | --- | --- |
| Datapipeline | eigen computer, offline | Python (pandas, geopandas, osmium), OSRM in Docker | eenmalig enkele uren rekenen |
| Comunetabel | server | één JSON- of SQLite-bestand | ca. 7.900 rijen × 50 kolommen, 2 tot 4 MB |
| Scorer | server | één PHP-script, antwoorden in, top 25 uit | < 0,2 s per verzoek |
| Wizard | pagina | 8 schermen in HTML en vanilla JS, mobiel eerst | zelfde stijl als de wensenlijst op unsognodicasa.it |
| Teller "nog X plaatsen" | pagina | filters draaien in de browser op een lichte versie van de tabel | ca. 300 kB |
| Talen | NL eerst, DE daarna | labels in een taalbestand | twee kolommen tekst |

Onderhoud: twee keer per jaar OMI-prijzen verversen (na publicatie van het semester), eens per jaar de rest. De pipeline maakt elke keer de hele tabel opnieuw; je uploadt alleen het nieuwe bestand.

Marketingkoppeling: de uitslagpagina kan per plaats verwijzen naar je gids en de Begrippenwijzer, en optioneel de top 5 per e-mail sturen via Brevo als leadmagneet.

## Lastige punten en open vragen

| Punt | Probleem | Voorstel |
| --- | --- | --- |
| Vraagprijzen | Magic Towns gebruikt eigen woningaanbod; dat is niet openbaar. Immobiliare.it scrapen botst met hun voorwaarden. | Alleen OMI gebruiken en dat eerlijk vermelden ("officiële waarden, vraagprijzen liggen vaak hoger"). |
| Schoolkwaliteit | Magic Towns gebruikt INVALSI-uitslagen per school. Die staan per school in Scuola in Chiaro, maar een bulkbestand heb ik niet gevonden; de volledige microdata zijn alleen voor onderzoekers ([catalogus](https://serviziostatistico.invalsi.it/catalogo-dati/)). | Versie 1: alleen aanwezigheid van scholen. Later: INVALSI vragen naar een export per school, of de gebruiksvoorwaarden van Scuola in Chiaro nalezen. |
| Gemeentediensten (was: corruptie en publieke diensten) | Magic Towns meet uitgaven per dienst uit de jaarrekening van elke comune. | OpenBDAP-jaarrekeningen per comune, uitgaven per inwoner vergeleken met even grote comuni. |
| Zorgscore | AGENAS-ziekenhuiskwaliteit per ASL; bulkdownload nog niet gecontroleerd. | Versie 1: rijtijd naar spoedeisende hulp. AGENAS PNE later toevoegen. |
| Banenmarkt | Werkloosheid per comune bestaat niet jaarlijks; Magic Towns modelleert die zelf. | Banen binnen 30 min uit ISTAT-bedrijvenregister en pendelmatrix, plus IRPEF-inkomen. |
| Wandelbaarheid | Geen officiële bron. | Eigen maat uit OSM: winkels, horeca en voorzieningen binnen 800 m van het centrum. |
| Rijtijden | 7.900 comuni × duizenden punten is veel rekenwerk. | OSRM lokaal draaien met de table-functie; per comune alleen de dichtstbijzijnde 5 kandidaten. |
| Klimaat en lucht | ERA5-Land en CAMS zijn grote rasterbestanden en vragen een gratis Copernicus-account. | Eenmalig downloaden voor Italië, waarde op het middelpunt van elke comune nemen. |
| Hoogtedata | De link naar ISTAT-hoogtes in de bronnentabel wijst naar de codepagina, niet naar het bestand. | Bestand "Principali statistiche geografiche sui comuni" opzoeken op istat.it. |

Open vragen voor jou:

- [ ] Eerst alleen Nederlands, of meteen ook Duits voor italy-blog.com?
- [ ] Bouw jij de pipeline zelf, of moet die als script klaarliggen dat je met één commando draait?
- [ ] Gratis tool, of alleen de top 5 gratis en de volledige lijst na e-mailadres?
