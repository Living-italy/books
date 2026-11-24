# Data: downloads en handmatige stappen

`python pipeline/run.py` downloadt alle open bronnen naar `data/raw/` en bouwt de tabel. De dekking per bron staat na elke run in `data/dekking.md`.

## Handmatig (de pipeline kan deze niet zelf ophalen)

| Bron | Waarom handmatig | Waar neerzetten |
|---|---|---|
| OMI, Quotazioni immobiliari (fornitura nazionale, nieuwste semester): `QI_..._VALORI.csv` en `QI_..._ZONE.csv` | gratis account bij de Agenzia delle Entrate | `data/raw/omi/` |
| OMI, Volumi delle compravendite (NTN) per comune | idem | `data/raw/omi_ntn/` (nog niet aangeleverd; criterium herverkoopbaarheid blijft leeg) |
| Ministero della Salute, "Strutture di ricovero attive" (xlsx) | salute.gov.it blokkeert geautomatiseerde downloads | `data/raw/salute/` |
| Copernicus ERA5-Land | account en API-sleutel nodig | nog niet aangeleverd; klimaat en klimaatverandering blijven leeg |

## Handmatige tabellen in `config/`

| Bestand | Inhoud | Controle |
|---|---|---|
| `lea_2023.json` | LEA/NSG 2023 per regio | gecontroleerd tegen de ministeriële sintesi van april 2025 (Calabrië 177, niet 150) |
| `kratergemeenten.json` | kraters 2009 en 2016 | uit de decreten; inwonersgrens krater: te verifiëren |
| `zona_rossa.json` | rode zones Vesuvius en Campi Flegrei | Protezione Civile; Napoli, Nola, Pomigliano, Giugliano en Marano liggen er deels in |
| `luchthavens.json` | passagiers 2025 (Assaeroporti) en vluchten het hele jaar | `heel_jaar` is voorlopig en moet handmatig worden gecontroleerd |
| `grens_naar_huis.json` | rijtijd grensovergang naar Utrecht, Brussel, Keulen, München | eenmalig met de openbare OSRM-server; opnieuw berekenen bij grote wegwijzigingen |
| `trends.json` | "In opkomst bij buitenlanders" | Gate-away jaarrapport 2025, telt niet mee in de score |

## Bewerkingen die afwijken van de opdracht

- **Veiligheid**: ISTAT, tasso di delittuosità per provincie (2024), in plaats van Il Sole 24 Ore (commerciële bron, hergebruik niet toegestaan).
- **Natuur**: beschermde natuurgebieden uit OpenStreetMap (IUCN-klasse 1 tot 4 en 6, nationale parken, reservaten, Natura 2000-tags); de EEA-bron Natura 2000 was vanuit de bouwomgeving niet bereikbaar. Voorlopige maat.
- **Cultuur**: OSM-telling van historische objecten en musea binnen 5 km. Geen open kaartlaag van DOC/DOCG-gebieden gevonden. Voorlopige maat.
- **Fiscaal**: IMU-tarief tweede woning ontbreekt (geen open bestand per comune, alleen een zoekportaal); de addizionale comunale IRPEF 2025 (max. tarief) is wel opgenomen.
- **Scholen binnen 20 minuten (gezin)**: benaderd als staatsscholen in comuni binnen 15 km hemelsbreed.
- **Rijtijd naar huis**: Italiaans deel met de eigen OSRM naar 9 grensovergangen, deel buiten Italië uit `config/grens_naar_huis.json`.
- **Provinciecijfers ISTAT** (criminaliteit, seizoen): ISTAT gebruikt nog de provincie-indeling van vóór 2026; de koppeling loopt via de oude provinciecode per comune.
