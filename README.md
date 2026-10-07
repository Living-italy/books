# Koopdossier Italië

Offline bruikbare webapp voor Nederlandse en Vlaamse kopers van een Italiaanse woning. De app begint met de conformiteitscheck "Is dit huis wel legaal?" en zet de uitkomst om in een dossier per woning: welke documenten zijn opgevraagd, ontvangen en goedgekeurd, en welke rode vlaggen spelen er.

Stand: **fase 1** (check en kern). Fase 2 (rode vlaggen, clausules, opdracht voor de geometra, schadebeperking, offline gebruik) en fase 3 (toegangscode, betaalde dossiercheck, Duitse versie) volgen.

## Mappen

```
koopdossier/              deze map upload je naar de server
  index.html              de hele app: opmaak, stijl en script
  content.nl.json         alle inhoud, los van de code
  api.php                 geeft de aanmelding na de check door aan Brevo
  fonts/                  Playfair Display en DM Sans (OFL-licentie)
bron/                     bronbestanden voor de inhoud, niet uploaden
NAKIJKLIJST.md            lege velden en rode vlaggen om te controleren
```

## Uploaden via DirectAdmin

1. Open in DirectAdmin **Bestandsbeheer** (File Manager) en ga naar `public_html` (of de map van het juiste domein).
2. Maak een map aan, bijvoorbeeld `koopdossier`.
3. Upload de inhoud van de lokale map `koopdossier/`: `index.html`, `content.nl.json`, `api.php` en de map `fonts/` met alle bestanden erin. Een zip uploaden en in DirectAdmin uitpakken kan ook.
4. Open `api.php` in de editor van DirectAdmin en vul bovenin in:
   - `BREVO_API_KEY`: de API-sleutel uit Brevo (*SMTP & API > API Keys*).
   - `BREVO_LIST_ID`: het ID van de lijst van de bestaande check.
   - `ALLOWED_ORIGINS`: leeg laten als de app op hetzelfde domein staat.
5. Controleer in Brevo dat de contactkenmerken bestaan: `VOORNAAM` (tekst), `RISICO_SCORE` (getal), `RISICO_NIVEAU` (tekst), `AANDACHTSPUNTEN` (getal) en `BRON` (tekst). Ze zijn gelijk aan die van de oude check: `RISICO_NIVEAU` is "Laag risico", "Verhoogd risico" of "Hoog risico", en `BRON` is `conformiteitscheck` (instelbaar via `bron` in `index.html`). Neem de API-sleutel en het lijst-ID over uit het oude `conformiteitscheck.php`.
6. Open `index.html` en vul zo nodig het blok `SETTINGS` bovenin in: `ga4Id` en `metaPixelId` voor het meten op de checkschermen, en `privacyUrl` voor de link naar je privacybeleid bij de aanmelding (standaard `/privacy`, zoals in de oude check). De adressen van Begrippenwijzer, chatbot en contactpagina zijn voor fase 2. Is een adres leeg, dan toont de app de knop niet.
7. Ga naar `https://jouwdomein.nl/koopdossier/?check` en doorloop de testlijst hieronder.

**Het oude adres van de check doorverwijzen.** De originele check staat ter referentie in `bron/conformiteitscheck.php`. Vervang op de server de inhoud van het oude `conformiteitscheck.php` door alleen deze regel:

```php
<?php header('Location: /koopdossier/?check', true, 301); exit;
```

of zet in `.htaccess` van die map:

```
Redirect 301 /conformiteitscheck.php /koopdossier/?check
```

De server heeft PHP met cURL nodig. Node, een database of een build-stap zijn niet nodig.

## Inhoud bijwerken (`content.nl.json`)

Alle teksten die niet tot de interface horen, staan in `content.nl.json`. De documentteksten komen uit hoofdstuk 4.2 en 5.2 van de gids "Huis kopen in Italië - Definitief". `NAKIJKLIJST.md` laat zien wat nog leeg is en wat nog moet worden nagekeken. Je kunt het bestand bewerken in de editor van DirectAdmin of lokaal in een teksteditor. Controleer na elke wijziging of het nog geldige JSON is, bijvoorbeeld op jsonlint.com. Eén vergeten komma en de app laadt niet.

Vaste afspraken:

- **Verhoog `version`** (een datum, bijvoorbeeld `"2026-11-15"`) bij elke wijziging. De voettekst toont die datum als "Inhoud bijgewerkt op". In fase 2 ververst de service worker de cache zodra deze waarde verandert.
- **Een `id` verandert nooit**, ook niet als je de tekst herschrijft. De voortgang van gebruikers verwijst naar de id's. Een nieuw document of een nieuwe vlag krijgt een nieuwe id.
- **Cursief** maak je met sterretjes: `*visura catastale*`. Gebruik dat voor Italiaanse termen en zet de Nederlandse uitleg erbij.
- **Een leeg veld** (`""`) toont de app als "[nog invullen]". Vul niets in wat niet in de bron staat.

Belangrijkste onderdelen:

| Onderdeel | Wat het doet |
| --- | --- |
| `check.levels` | De drempels (tot en met 15 laag, tot en met 40 verhoogd, daarboven hoog), het label, de oordeelkop (`title`) en de oordeeltekst (`text`). |
| `check.questions` | De 15 checkvragen: `weight` (samen 100), `text`, `help`, de gekoppelde `documents` en de `flag` die bij "nee" alvast wordt aangevinkt. |
| `check.profile` | De profielvragen. Elke antwoordoptie heeft `flags`. Die vlaggen bepalen welke documenten gelden. `"multiple": true` maakt meer antwoorden mogelijk; `"exclusive": true` is een hint voor opties als "geen" en "weet ik niet". |
| `check.profileFlags` | Leesbare uitleg per vlag, bijvoorbeeld bij "extra belangrijk, omdat erfgenamen verkopen". |
| `check.riskTexts` | Risicoteksten: `{ "id": "...", "when": ["verbouwd"], "rank": 1, "text": "..." }`. `when` = één treffer genoeg, `whenAll` = alle vlaggen nodig. Naast profielvlaggen werken ook `nee:<vraag>` en `weet:<vraag>`, zodat een risicotekst al na de check kan verschijnen. De app toont er hoogstens drie, laagste `rank` eerst. |
| `check.actions` | Acties per profiel: `{ "id": "...", "when": ["grond"], "phase": "compromesso", "text": "...", "source": "gids 4.2.6" }`. Zonder `when` en `whenAll` geldt een actie altijd (basisset). Het dossier toont ze in een uitklapbare lijst per fase, de pdf in de actielijst. Risicoteksten en acties komen uit de gids; zie paragraaf 6 van `NAKIJKLIJST.md`. |
| `documents` | De documenten. `phase` is `bod`, `compromesso` of `rogito`. `appliesWhen`: leeg = altijd, anders één treffer genoeg. `criticalWhen`: maakt het document extra belangrijk. `dropWhen`: laat het document vervallen of vervangen (zie hieronder). De vijf kopjes zijn `what`, `why`, `who`, `costTime` en `contract`. `requestIt` en `requestNl` zijn de regels in de opvraagmail. |
| `documents[].source` | De paragraaf in de gids waar de teksten vandaan komen, bijvoorbeeld `gids 4.2.1`. De app toont dit onder de kopjes. |
| `documents[].inMail` | Zet op `false` voor documenten die je niet bij makelaar of verkoper opvraagt, zoals de eigen bouwkundige keuring. Ze komen dan niet in de opvraagmail. |
| `documents[].flags` | Rode vlaggen: `id`, `text`, `severity` (`bespreken`, `oplossen` of `niet-tekenen`) en `clauses`: de id's van de clausules die bij deze vlag horen. |
| `mail` | Onderwerp, aanhef per ontvanger, inleiding en slot van de opvraagmail, in het Italiaans en het Nederlands. `{immobile}` wordt de naam van de woning met de gemeente. |
| `process` | De stappen van het stroomschema in de pdf. |
| `offerNext` | Het aanbod voor de volgende stap, onderaan de pdf. |
| `clauses` | De clausulebibliotheek: `titleNl`, `whenNl` (wanneer je hem nodig hebt), `textNl`, `textIt`, `source`, `model` (waar dezelfde afspraak in het model-*compromesso* van bijlage G staat, leeg als het model hem niet heeft), `appliesWhen` (profielvlaggen waarbij de app hem voorstelt) en `reviewed`. Zolang `reviewed` op `false` staat, toont de app "concept, nog niet juridisch nagelezen". Het scherm met clausules komt in fase 2. |

**Een document laten vervallen of vervangen**, bijvoorbeeld bij bouw vóór 1 september 1967:

```json
"dropWhen": [
  { "when": ["voor1967"], "replacedBy": "id-van-ander-document", "reason": "het pand is gebouwd vóór 1 september 1967 en ..." }
]
```

De app toont het document dan onder "Vervallen voor deze woning", met de reden. `replacedBy` mag weg als er geen vervanger is. De `reason` volgt op "Vervalt omdat".

Interfaceteksten (knoppen, kopjes, meldingen) staan in het object `UI` in `index.html`. Voor een Duitse versie komen er later een `content.de.json` en een tweede tekstobject bij.

## Hoe de app rekent

- Nee telt het volle gewicht, weet ik niet 0,7 keer het gewicht, ja telt nul. De som wordt afgerond op een heel getal.
- Een vraag waarvan alle documenten volgens het profiel niet van toepassing zijn, telt als ja.
- De score wordt steeds uit de antwoorden berekend en nooit opgeslagen.
- Een nee zet het document bovenaan met "aandachtspunt uit de check" en vinkt de bijbehorende rode vlag aan. Een weet ik niet zet het document bovenaan met "eerst opvragen" en bovenaan in de opvraagmail.

## Privacy

- Dossiergegevens (namen, adressen, antwoorden, notities) blijven in de browser, onder de sleutel `koopdossier` in `localStorage`.
- Alleen als de gebruiker zelf een e-mailadres invult, gaan e-mailadres, voornaam, score, risiconiveau en het aantal aandachtspunten naar `api.php` en van daar naar Brevo.
- GA4 en Meta Pixel laden alleen op de checkschermen en alleen als hun ID is ingevuld. De namen zijn gelijk aan die van de oude check, zodat rapporten doorlopen. Score en niveau gaan mee, antwoorden nooit.

  | Moment | GA4 | Meta |
  | --- | --- | --- |
  | Check begonnen | `check_gestart` | `ConformiteitsCheckGestart` |
  | Check af | `check_voltooid` met `score` en `niveau` | `ConformiteitsCheckVoltooid` met `score` en `niveau` |
  | Aangemeld | `generate_lead` met `score` en `niveau` (geen `value`) | `Lead` met `content_name: conformiteitscheck` |
- Zet geen sessie-opnames (zoals Microsoft Clarity) op deze pagina's, of maskeer alle invoervelden.

## Testlijst fase 1

Test op een telefoon (of in de browser op 360 px breed) en op een computer.

1. Open `/koopdossier/?check`. De check opent direct. Beantwoord vraag 1 met nee, vraag 2 met weet ik niet en de rest met ja. De uitkomst is **18, verhoogd risico** met twee aandachtspunten. Dit zie je zonder e-mailadres.
2. Controleer met dezelfde antwoorden in de oude check dat score en niveau gelijk zijn.
3. Open het netwerktabblad van de browser (F12). Vul voornaam en e-mailadres in en kies "Aanmelden en pdf maken". Er gaat één POST naar `api.php` met alleen het e-mailadres en de vijf Brevo-velden. Het contact staat daarna in Brevo.
4. Kies "Maak hier een dossier van" en beantwoord de profielvragen (appartement, geen grond, niet verhuurd). Het dossier toont de *planimetria catastale* bovenaan met "aandachtspunt uit de check" en een aangevinkte rode vlag, en de bouwvergunningen met "eerst opvragen". Het *condominio* staat erin, *prelazione agraria* en huur niet.
5. Open een document, zet de status op ontvangen, schrijf een notitie en zet het antwoord op de checkvraag op ja. De score op de woningkaart daalt.
6. Sluit de browser, open hem opnieuw. Status, notitie en vlag staan er nog.
7. Ga naar Exporteren. De opvraagmail bevat precies de documenten op "niet gevraagd", in het Italiaans, met de Nederlandse vertaling eronder. Kopieer de mail en bevestig: de documenten staan daarna op "gevraagd" met de datum van vandaag.
8. Kies "Afdrukken of pdf maken". De eerste keer vraagt de app om het e-mailadres (overslaan mag). De pdf is leesbaar op A4 en eindigt met disclaimer en inhoudsversie.
9. Download een back-up, kies "Alles wissen", zet de back-up terug. Het dossier is identiek.
10. Loop alle schermen door met alleen de Tab-toets en Enter. Alles is bereikbaar en de focus is zichtbaar. Er is nergens horizontaal scrollen op 360 px.
11. Lege teksten tonen "[nog invullen]".
12. Voeg een document toe aan `content.nl.json` en verhoog `version`. Na herladen staat het nieuwe document in het dossier en is de bestaande voortgang intact.

## Lettertypen

Playfair Display en DM Sans komen uit de openbare Google Fonts-collectie en vallen onder de SIL Open Font License. De licentieteksten staan in `koopdossier/fonts/`.
