# Plan: Plaatsvinder aanpassen aan het criteria-onderzoek

Uitvoering van `docs/plaatsvinder-aanpassingen.md` op de bestaande versie 1 (`pipeline/`, `web/`). Werkwijze: fase voor fase, commit na elke fase op de branch `plaatsvinder`.

## Uitgangspunten

- De bestaande pipeline en tabel blijven de basis (ISTAT-code als sleutel, percentielen 0-100, null bij ontbrekende data, provinciecijfer met markering).
- Nieuwe map `config/` met de handmatige tabellen en instellingen. De pipeline valideert ze tegen JSON-schema's in `config/schema/` en kopieert wat de site nodig heeft naar `web/config/`.
- Elke bron krijgt een vermelding in `config/bronnen.json` met `bron`, `url`, `peildatum`, `schaalniveau` en `licentie`. Elke indicator in de tabel verwijst naar een bron-id; de site toont die bij elk cijfer.
- De pipeline rekent per comune de indicatoren, de deelscore per criterium (0-100) en de twee tests uit. De site rekent alleen de gewogen som met de gewichten van de gebruiker en past de filters toe.
- Geen scraping van commerciële sites. Wat niet open te downloaden is, wordt gemeld en blijft leeg (`geen data`).

## Fase 1: configuratie

| Bestand | Inhoud |
|---|---|
| `config/criteria.json` | 17 criteria uit sectie 1 met id, label, uitleg, rang, basis, richting, bronnen, schaalniveau, plus de indicatoren per criterium |
| `config/profielen.json` | 5 profielen met startgewichten (som 100) en de extra vragen (permanent of verdelen, expats-voorkeur) |
| `config/lea_2023.json` | NSG 2023 per regio en per autonome provincie, drie macrogebieden plus totaal, gecontroleerd tegen het ministeriële document |
| `config/trends.json` | label "In opkomst bij buitenlandse kopers" met bron en jaar |
| `config/kratergemeenten.json` | gemeenten in de kraters van 2009 en 2016 met bron (decreten) |
| `config/regime7.json` | regio's, inwonersgrens 30.000 (wet 34/2026), oude grens 20.000, `inwonersgrens_krater: "te_verifieren"` |
| `config/zona_rossa.json` | gemeenten in de rode zone van Vesuvius en Campi Flegrei met bron |
| `config/luchthavens.json` | luchthavens met lijnvluchten: passagiers (Assaeroporti) en `heel_jaar` naar NL/BE/DE |
| `config/aandachtspunten.json` | vaste checklist en de niet-gescoorde criteria (rang 11, 16, 20, 21, 22) |
| `config/bronnen.json` | alle bronnen met metadata |
| `config/schema/*.json` + `pipeline/config_check.py` | schema-validatie, ook: gewichten tellen op tot 100 |

## Fase 2: data

Een module per bron in `pipeline/bronnen/`, elk met download en bewerking naar `data/work/bron_<id>.csv` (ISTAT-code plus waarden). Daarna voegt `build.py` alles samen. `data/dekking.md` meldt per bron het aantal comuni met data.

| Criterium | Bron | Aanpak |
|---|---|---|
| prijs | OMI (al aanwezig) | mediaan plus de laagste zone apart |
| liquiditeit | OMI NTN (compravendite) + ISTAT woningvoorraad (census 2021) | NTN-bestand moet handmatig (OMI-account) |
| risico | Protezione Civile (seismische zones, xlsx mei 2025), ISPRA IdroGEO (indicatoren per comune), EFFIS (brandvlakken), rode zone (config) | |
| klimaat, klimaatverandering | ERA5-Land | geblokkeerd: Copernicus-account nodig (sectie 11) |
| zorg | LEA (config) + bestaande rijtijd ziekenhuis en apotheken | rijtijd naar apotheek erbij |
| levendigheid | SNAI 2021-2027 (ISTAT xlsx), ISTAT bevolking 10 jaar terug en 65-plussers (POSAS) | |
| expats | ISTAT inwoners per nationaliteit per comune (RCS) | NL, BE, DE, GB en totaal per 1.000 |
| internet | AGCOM (al aanwezig) | FTTH; 100 Mbit/s alleen als AGCOM het per comune levert |
| toerisme | ISTAT capaciteit per comune (bedden) en overnachtingen per maand per provincie | |
| fiscaal | 7%-regime (afgeleid), IMU-tarief en addizionale IRPEF (Finanze) | IMU alleen als er een open bestand per comune is |
| veiligheid | ISTAT aangiften per provincie (open) in plaats van Il Sole 24 Ore | Il Sole: hergebruik niet toegestaan, weglaten |
| bereikbaarheid | OSRM naar de 3 dichtstbijzijnde luchthavens met lijnvluchten; naar Utrecht, Brussel, Keulen, München via grensovergangen | Italiaans deel met de eigen OSRM, deel buiten Italië eenmalig met de openbare OSRM-server |
| natuur | Natura 2000 (EEA) binnen 5 km, bevolkingsdichtheid, hoogteverschil | proxy, gemarkeerd als voorlopig |
| cultuur | OSM: historische objecten en musea binnen 5 km | DOC/DOCG: geen open kaartlaag gevonden, proxy voorlopig |
| voorzieningen | bestaande OSM-telling plus supermarkt, apotheek, bar binnen 800 m; scholen | |
| kosten | geen open bron per comune gevonden | gewicht 0 in alle profielen, blijft leeg |

## Fase 3: rekenen

- Indicatoren naar percentielen (al aanwezig), deelscore per criterium, richting uit `criteria.json`.
- Harde filters uit sectie 5a.
- Wintertest en 80-jarentest (sectie 7) als aparte scores.
- `in_aanmerking_7procent(istat, jaar)` in Python, met unittests (sectie 10), en het resultaat per comune in de tabel.
- Tests: gewichten per profiel = 100; 7%-functie; filter gepensioneerde (rijtijd spoedeisende hulp max. 45 min).

## Fase 4: interface

- Stap 1 profiel (met de extra vragen), daarna ligging, grootte, gewichten als schuifjes (som 100, met label "startwaarden op basis van expertoordeel"), harde filters, en de keuze waar in Italië.
- Resultaatlijst met totaalscore en deelscores; gemeentepagina met deelscores, risicoblok (altijd), wintertest en 80-jarentest, 7%-regime met disclaimer, herverkoop, trendlabel, checklist, en bij elk cijfer bron, peildatum en schaalniveau.
- Vergelijken van maximaal 3 gemeenten, en 3 vergelijkbare alternatieven met een hogere score.

## Fase 5: controle

- `data/steekproef.md` met de zes gemeenten uit sectie 10, per bron gecontroleerd.
- Zoeken naar het gedachtestreepje in alle teksten.
- Alle unittests groen.

## Te melden (sectie 11)

1. **LEA 2023 wijkt af**: Calabrië is in het ministeriële document 177 (68 + 40 + 69), niet 150. Ik gebruik de ministeriële waarde. Aangevuld: Valle d'Aosta 165, Campania 206. Bron: Ministero della Salute, sintesi CORE NSG 2023 (april 2025).
2. **ERA5-Land** vraagt een Copernicus-account. Klimaat en klimaatverandering blijven leeg tot er een account (API-sleutel) is of een andere open bron.
3. **Il Sole 24 Ore**: commerciële bron, hergebruik niet toegestaan. Voorstel: ISTAT-cijfers over aangiften per provincie (open data).
4. **Kratergemeenten**: inwonersgrens blijft `te_verifieren`, zoals de opdracht vraagt.
5. **OMI NTN (transacties)**: vraagt een OMI-account; het bestand moet handmatig worden aangeleverd.
6. **Luchthavens, `heel_jaar`**: welke luchthavens het hele jaar vluchten naar NL, BE of DE hebben, staat niet in een open bestand. Dat veld vraagt een handmatige controle.
