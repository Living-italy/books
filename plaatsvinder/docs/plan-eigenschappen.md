# Plan: één lijst met eigenschappen (vervangt wensenlijst, gewichten per criterium en overige eisen)

Opdracht van Stef (26 november 2026): geen onderscheid meer tussen dealbreakers, must-haves en nice-to-haves. Alle eigenschappen in één lijst; per eigenschap noodzakelijk, meewegen of onbelangrijk; per eigenschap een niveau (3 tot 5, met uitleg), behalve bij onbelangrijk. Daarna gewichten van alleen de eigenschappen die meewegen (noodzakelijk vast maximaal, onbelangrijk minimaal). Daarna de ranglijst met uitklapinfo over alle eigenschappen.

## Wizard

1. Profiel, 2. Zoekgebied, 3. Ligging, 4. Grootte, 5. Eigenschappen (status en niveau), 6. Gewichten, dan de uitslag.

## Rekenen

- `config/eigenschappen.json`: 33 eigenschappen in 8 groepen, elk met kolom, richting (lager of hoger is beter), eenheid, bron en niveaus van soepel naar streng.
- Pipeline (`pipeline/eigenschappen.py`): per gemeente het behaalde niveau per eigenschap (0 tot 5) en het percentiel over heel Italië. Afgeleide kolommen: criminaliteit ten opzichte van het gemiddelde, risico water (hoogste van aardverschuiving en overstroming), vulkaan-, maffia- en 7%-rang.
- Noodzakelijk: gemeente valt af als het behaalde niveau lager is dan het gekozen niveau; gewicht 10.
- Meewegen: gewicht 1 tot 9 (standaard 5).
- Onbelangrijk: gewicht 0, geen niveau.
- Deelscore = 85 x (behaald niveau / gekozen niveau, hoogstens 1) + 15 x percentiel. Totaal = gewogen gemiddelde. Ontbrekende data sluit niet uit en telt niet mee.
- De rode zone van de Vesuvius en de Campi Flegrei valt altijd af.

## Uitzonderingen op 3 tot 5 niveaus

- 7%-regime en vulkaan: 2 niveaus; ontbonden gemeenteraad: 1 niveau. Het zijn ja/nee-eigenschappen.

## Vervalt

- Wensenlijst (dealbreakers, must-haves, nice-to-haves), stap "Overige eisen" (prijs en naar huis zijn nu eigenschappen) en het budget.
