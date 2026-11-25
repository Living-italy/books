# Plan: secties 13 (zoekgebied) en 14 (wensenlijst herzien)

Opdracht: `docs/plaatsvinder-aanpassingen.md`, secties 13 en 14. Secties 1 tot en met 12 zijn gebouwd (zie `plan-aanpassingen.md` en `plan-aanpassingen-12.md`).

## Keuzes

- **Vaste drempels (14a).** De instelbare grenswaarden van de vorige versie vervallen; de opdracht zegt nu dat de bezoeker ze niet kan aanpassen. De eigen harde eisen uit stap 6 blijven; gelden beide, dan sluiten beide uit, dus telt de strengste.
- **Rekenen in de pipeline.** Per wensenlijst-item rekent de pipeline per gemeente uit of die afvalt (`config/wensenlijst.json` beschrijft de regel). Het resultaat gaat als bitmasker mee in `score.json` en `comuni-lite.json`. PHP en de teller in de browser lezen alleen dat masker, zodat ze altijd hetzelfde tellen. Ontbrekende data sluit niet uit (zelfde regel als bij de harde eisen).
- **Nice-to-haves** verhogen het gewicht van het bijbehorende criterium met factor 1,2 (één keer per criterium), daarna terug naar 100.
- **Must-haves** wegen niet meer zwaarder; ze sluiten uit.

## Fasen

| Fase | Wat | Bestanden |
|---|---|---|
| 1 | Configuratie: `wensenlijst.json` (items 14b en 14c met drempel, indicator, bron, uitleg, criterium, blokkades), schema, `zona_gialla.json`, `internationale_scholen.json` (leeg), teksten | `config/`, `web/lang/nl.json` |
| 2 | Data (14g): gele zones Vesuvius en Campi Flegrei; laagste LEA-gebiedsscore per regio (al in `lea_2023.json`); afstand centrum tot autosnelweg (OSM `highway=motorway`); Noord-Europa, VK en VS per inwoner; rijtijd naar museum of historisch object (OSRM); overnachtingen per inwoner (provinciecijfer ISTAT verdeeld naar het aandeel bedden, als schatting gemarkeerd: ISTAT publiceert geen overnachtingen per gemeente); station met reizigersverkeer | `pipeline/` |
| 3 | Rekenen: masker per gemeente, nice-to-haves, items die te veel wegstrepen (14f), dekking per item (14h), steekproef per item | `pipeline/`, `web/lib/model.php` |
| 4 | Interface: stap 2 zoekgebied (heel Italië, regio's, provincies per regio met zoekveld, tip), stap 3 wensenlijst in drie lijsten met uitleg (i-knop), blokkades en waarschuwingen (14e), hulp bij te weinig plaatsen (14f), alternatieven buiten het zoekgebied gemarkeerd, deelbare link | `web/assets/app.js`, `web/lang/nl.json` |
| 5 | Controle: tests 13 en 14h, dekking, steekproef, browser op telefoonformaat | `web/tests/`, `data/` |

## Volgorde van de wizard

1. Profiel, 2. Zoekgebied, 3. Wensenlijst, 4. Ligging, 5. Grootte, 6. Gewichten, 7. Harde eisen.

## Bronnen die nog ontbreken of een schatting zijn

- Overnachtingen per gemeente: geen open bron per gemeente gevonden; schatting uit het provinciecijfer en het aandeel bedden.
- Internationale scholen: lijst leeg, item staat uit.

## Stand na de bouw

- Alle fasen gebouwd en getest (`php web/tests/test_model.php`, Python-tests, browser op 390 px).
- Provincies: 110 eenheden in de actuele ISTAT-indeling, waaronder 8 in Sardinië (Cagliari, Gallura Nord-Est Sardegna, Medio Campidano, Nuoro, Ogliastra, Oristano, Sassari, Sulcis Iglesiente). ISTAT-tabellen per provincie (aangiften, overnachtingen) gebruiken nog de indeling 2017-2025 met 107 provincies; die worden via de oude provinciecode per gemeente gekoppeld.
- Items die meer dan de helft wegstrepen: zie `data/dekking.md` (vet).
