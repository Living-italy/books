# Plan: sectie 12 (aanvullingen uit de gids "Huis kopen in Italië")

Aanvulling op `docs/plan-aanpassingen.md`. Opdracht: `docs/plaatsvinder-aanpassingen.md`, sectie 12. 12a en 12d nu; 12b en 12c in `criteria.json` met `status: "gepland"` (12c is al gebouwd).

## Nu te bouwen (geen extra input nodig)

| Onderdeel | Aanpak | Bestanden |
|---|---|---|
| Hoogteligging (12a) | hoogte van het centrum (ISTAT, al aanwezig) zichtbaar bij elke gemeente; filter "maximale hoogte" (standaard uit); uitleg 0,6 °C per 100 m | lite, `model.php`, `app.js`, `nl.json` |
| Klimaatdetails (12a) | ERA5-Land maandgemiddelden: temperatuur januari en juli, zonuren (uit inkomende zonnestraling, benadering), jaarlijkse neerslag; vorstdagen (dagminimum). Keuze "zachte winter" of "vier seizoenen" draait de richting van de wintertemperatuur en de vorstdagen om | `era5.py`, `criteria.json`, wizard |
| Luchtkwaliteit (12a) | nieuw criterium `lucht` (basis E), PM2,5 uit de EEA-kaart (al in de tabel); PM10 en NO2 zodra de EEA-bestanden binnen zijn; startgewicht 3 bij gepensioneerde en gezin, af van natuur | `criteria.json`, `profielen.json` |
| Trein (12a) | rijtijd naar station met hogesnelheidstreinen en naar station met nachttrein, uit OSM-routes (`route=train`, `service=high_speed`, Frecciarossa, Italo; `service=night`, Intercity Notte, Nightjet); binnen bereikbaarheid: luchthaven 50, auto naar huis 30, trein 20 | `osm.py`, `routing`, `scores.py` |
| Vluchten (12a) | `luchthavens.json` omgebouwd naar verbindingen per luchthaven (AMS, EIN, RTM, BRU, CRL; apart CGN, DUS, MUC) met markering seizoen; wacht op Bijlage A voor de inhoud | `luchthavens.json` + schema |
| Maffia-label (12a) | label met jaartal en link, geen score; handmatige tabel met bron (Ministero dell'Interno, relazioni annuali / decreti) | `config/sciolti_mafia.json` |
| Qualità della vita (12a) | Il Sole 24 Ore: hergebruik niet toegestaan, weglaten en melden (zoals bij de criminaliteitsindex) | - |
| Wensenlijst (12d.1) | stap na de profielkeuze: 3 dealbreakers, 3 must-haves, 3 nice-to-haves uit de criteria; dealbreakers worden harde filters (drempel per criterium in `criteria.json`), must-have ×1,5, nice-to-have ×1,2, daarna normaliseren naar 100 | `criteria.json`, `model.php`, `app.js`, tests |
| Termen gids (12d.2) | "permanente bewoning (hoofdwoning)" en "tweede huis (vakantie, af en toe)" in de profielkeuze | `profielen.json`, `nl.json` |
| Gids-verwijzingen (12d.3) | blok "Meer hierover in de gids" op elke gemeentepagina uit `config/gids_links.json` | config, `app.js` |
| Gidsprijzen (12d.4) | €/m² per regio "volgens de gids" naast de OMI-waarde uit `config/gids_prijzen.json` | config, `app.js` |
| 12b, gepland | huisdieren, wild (varkenspest), droogte, laadpalen, aardgas, internationale scholen, toeristenbelasting, nieuwe inwoners: in `criteria.json` onder `gepland` | `criteria.json` |
| Tests (12e) | dealbreaker internet verwijdert alle gemeenten onder de drempel; gewichten na must/nice = 100; elke gemeentepagina heeft een gids-link; labels veranderen de score niet | `test_model.php`, Playwright |

## Nodig van Stef

1. **Bijlage A** (vluchten per luchthaven naar AMS, EIN, RTM, BRU, CRL, met seizoen), als bestand of tabel.
2. **Gids-verwijzingen**: de URL's (of paginanummers) van 1.1.2 en Bijlage B (regio's), 1.1.1c (natuurrisico's), hoofdstuk 7 (7%-regime) en hoofdstuk 10 (verhuur). Bijlage C staat al: https://stefsmulders.nl/Downloads/Bijlage-C.html.
3. **Prijzen uit de gids**: €/m² per regio zoals in de gids.
4. **Wensenlijst-wizard van unsognodicasa.it**: de broncode (of toegang) als die als basis moet dienen; de site zelf is niet bereikbaar vanuit de bouwomgeving.
5. **EEA PM10 en NO2 (2023)**: de EEA-server verbreekt de verbinding vanaf de bouwomgeving; handmatig downloaden kan via de EEA-datahub ("European air quality data (interpolated data)").

## Stand na de gids (definitieve versie) en de wizardcode

- Bijlage A verwerkt in `config/luchthavens.json`; vervangt de voorlopige inschatting.
- Gidsprijzen voor 10 regio's; de andere regio's noemen in de gids geen bedrag.
- Gids-verwijzingen met paragraaf en pagina. De link in de gids naar `7%_regeling_italie.pdf` geeft 404 en staat er daarom niet in.
- Wizard Un Sogno di Casa: hervatten en overzicht met Wijzigen overgenomen; de woningvragen (type, m², kamers, zwembad) horen bij de makelaar en niet bij de plaatskeuze.
- Nog open: EEA PM10 en NO2, de lijst ontbonden gemeenteraden.
