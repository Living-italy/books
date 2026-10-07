# Nakijklijst inhoud

Gegenereerd uit `content.nl.json` (versie 2026-10-07). Alles hieronder wacht op controle door Stef. Juridische inhoud komt alleen uit de bronbestanden; wat daar niet in staat, blijft leeg en toont in de app "[nog invullen]".

## 1. Voorlopig ingevuld, graag controleren

- **Tekst van de 15 checkvragen**: afgeleid uit de tabel in de bouwbrief. Vervangen door de letterlijke vragen uit `conformiteitscheck.php`.
- **Profielvragen en antwoordopties**: afgeleid uit de bouwbrief. Vervangen door de opties uit `profielen-conformiteitscheck.md`. De keuze bij "weet ik niet" volgt de voorzichtige kant (een document blijft dan in de lijst).
- **Afronding van de score**: de app rondt de som af met gewone afronding (18,4 wordt 18). Controleer of `conformiteitscheck.php` hetzelfde doet.
- **Italiaanse opvraagregels (`requestIt`) en de opvraagmail**: conceptvertaling, nog niet nagelezen door een Italiaanse jurist (`mail.reviewed: false`).
- **Rode vlaggen**: de voorbeeldvlaggen uit de bouwbrief, plus per checkvraag een vlag die de vraag ontkent. De ernst is een voorstel (zie paragraaf 3).
- **Stroomschema (`process`)**: opgebouwd uit de fases in de bouwbrief.

## 2. Lege velden

### Check

- `check.intro` (inleiding op het startscherm, optioneel)
- `check.levels.laag.text` (oordeeltekst bij laag risico)
- `check.levels.verhoogd.text` (oordeeltekst bij verhoogd risico)
- `check.levels.hoog.text` (oordeeltekst bij hoog risico)
- `help` bij alle 15 checkvragen: `catastale`, `urbanistica`, `agibilita`, `eigendom`, `ipoteca`, `erfgenamen`, `meters`, `aanbouw`, `bestemming`, `impianti`, `ape`, `fognatura`, `servitu`, `vincoli`, `geometra`
- `check.riskTexts`: nog geen risicoteksten (uit `profielen-conformiteitscheck.md`)
- `check.actions`: nog geen acties per profiel (uit `profielen-conformiteitscheck.md`)
- `dropWhen` (vervallen of vervangen van documenten, onder meer bij bouw vóór 1967): nog bij geen enkel document ingevuld
- `criticalWhen`: alleen het voorbeeld uit de bouwbrief (*visura catastale* bij erfgenamen)
- `offerNext` (aanbod voor de volgende stap in de pdf)

### Documenten

Per document de lege kopjes. Afkortingen: what = wat het is, why = waarom het nodig is, who = wie het opvraagt, costTime = wat het kost en hoe lang het duurt, contract = wat je vastlegt in het compromesso.

| Document | Lege velden |
| --- | --- |
| `atto-provenienza` Atto di provenienza | what, why, who, costTime, contract |
| `visura-catastale` Visura catastale | what, why, who, costTime, contract |
| `planimetria-catastale` Planimetria catastale | what, why, who, costTime, contract |
| `titoli-edilizi` Titoli edilizi e stato legittimo | what, why, who, costTime, contract |
| `ape` APE | what, why, who, costTime, contract |
| `identiteit-verkoper` Identiteit en bevoegdheid van de verkoper | nameIt, what, why, who, costTime, contract |
| `makelaar` Inschrijving en provisie van de makelaar | nameIt, what, why, who, costTime, contract |
| `huur-gebruik` Locazione o comodato | what, why, who, costTime, contract |
| `relazione-tecnica` Relazione tecnica di conformità | what, why, who, costTime, contract |
| `ispezione-ipotecaria-compromesso` Ispezione ipotecaria | what, why, who, costTime, contract |
| `ispezione-ipotecaria-rogito` Ispezione ipotecaria | what, why, who, costTime, contract |
| `agibilita` Agibilità o SCA | what, why, who, costTime, contract |
| `impianti` Dichiarazioni di conformità degli impianti | what, why, who, costTime, contract |
| `destinazione-urbanistica` Certificato di destinazione urbanistica | what, why, who, costTime, contract |
| `vincoli` Vincoli | what, why, who, costTime, contract |
| `prelazione-agraria` Prelazione agraria | what, why, who, costTime, contract |
| `servitu` Servitù | what, why, who, costTime, contract |
| `condominio` Documenti del condominio | what, why, who, costTime, contract |
| `riolering-water` Autorizzazione allo scarico e pozzo | what, why, who, costTime, contract |

Bij `identiteit-verkoper` en `makelaar` is `nameIt` leeg omdat de bouwbrief geen Italiaanse naam geeft.

### Clausules

- `clauses` is nog leeg. De 19 clausules uit § 5.2.1 volgen in fase 2.

## 3. Rode vlaggen met voorgestelde ernst

| Document | Vlag-id | Tekst | Voorgestelde ernst | Uit de check |
| --- | --- | --- | --- | --- |
| Atto di provenienza | `verkregen-via-schenking` | De verkoper heeft de woning verkregen via een schenking | bespreken met je geometra |  |
| Atto di provenienza | `erfenis-niet-alle-erfgenamen` | De woning komt uit een erfenis en niet alle erfgenamen tekenen mee | niet tekenen |  |
| Visura catastale | `visura-naam-wijkt-af` | De naam op de _visura_ is niet die van de verkoper | eerst laten oplossen | `eigendom` |
| Visura catastale | `categorie-wijkt-af` | De kadastrale categorie klopt niet met het werkelijke gebruik | eerst laten oplossen | `bestemming` |
| Visura catastale | `meters-wijken-af` | De vierkante meters kloppen niet met het kadaster | bespreken met je geometra | `meters` |
| Planimetria catastale | `planimetria-wijkt-af` | De plattegrond wijkt af van de werkelijke indeling | eerst laten oplossen | `catastale` |
| Titoli edilizi e stato legittimo | `verbouwing-zonder-vergunning` | Voor een verbouwing is geen vergunning te vinden | eerst laten oplossen | `urbanistica` |
| Titoli edilizi e stato legittimo | `aanbouw-zonder-vergunning` | Een aanbouw, bijgebouw, veranda of zwembad is niet vergund of niet geregistreerd | eerst laten oplossen | `aanbouw` |
| Titoli edilizi e stato legittimo | `condono-niet-afgerond` | Er loopt een _condono_ (legalisatie achteraf) die nooit is afgerond | eerst laten oplossen |  |
| APE | `ape-ontbreekt` | Het energielabel ontbreekt of is verlopen | bespreken met je geometra | `ape` |
| Identiteit en bevoegdheid van de verkoper | `eigenaar-tekent-niet` | Een echtgenoot, mede-eigenaar of erfgenaam tekent niet mee | niet tekenen | `erfgenamen` |
| Inschrijving en provisie van de makelaar | `makelaar-niet-ingeschreven` | De makelaar is niet ingeschreven | eerst laten oplossen |  |
| Inschrijving en provisie van de makelaar | `provisie-niet-op-papier` | De provisie van de makelaar staat niet op papier | bespreken met je geometra |  |
| Locazione o comodato | `contract-loopt-door` | Er loopt een huur- of gebruikscontract dat niet eindigt vóór de overdracht | eerst laten oplossen |  |
| Relazione tecnica di conformità | `geen-eigen-controle` | Er is nog geen onafhankelijke controle door een eigen _geometra_ | bespreken met je geometra | `geometra` |
| Relazione tecnica di conformità | `afwijking-buiten-tolerantie` | Het rapport noemt afwijkingen buiten de wettelijke toleranties | eerst laten oplossen |  |
| Ispezione ipotecaria | `hypotheek-of-beslag` | Er staat een hypotheek, beslag of rechtsvordering ingeschreven | eerst laten oplossen | `ipoteca` |
| Ispezione ipotecaria | `hypotheek-of-beslag-rogito` | Er staat een hypotheek, beslag of rechtsvordering ingeschreven | niet tekenen |  |
| Agibilità o SCA | `agibilita-ontbreekt` | De _agibilità_ ontbreekt of is nooit aangevraagd | eerst laten oplossen | `agibilita` |
| Dichiarazioni di conformità degli impianti | `impianti-ontbreken` | Verklaringen voor elektra, gas of verwarming ontbreken | bespreken met je geometra | `impianti` |
| Certificato di destinazione urbanistica | `bestemming-blokkeert-plannen` | De bestemming van de grond blokkeert je plannen | bespreken met je geometra |  |
| Vincoli | `vincoli-onbekend` | Het is niet bekend welke beperkingen op het pand rusten | bespreken met je geometra | `vincoli` |
| Vincoli | `monument-voorkooprecht` | Het pand is een beschermd monument met voorkooprecht van de staat | bespreken met je geometra |  |
| Prelazione agraria | `geen-afstandsverklaring` | Er is geen afstandsverklaring van de rechthebbenden | eerst laten oplossen |  |
| Servitù | `toegang-niet-vastgelegd` | De toegangsweg loopt over andermans grond zonder vastgelegd recht | eerst laten oplossen | `servitu` |
| Documenti del condominio | `achterstallige-bijdragen` | Er zijn achterstallige bijdragen aan de VvE | eerst laten oplossen |  |
| Documenti del condominio | `grote-werken-besloten` | De VvE heeft al grote werken besloten | bespreken met je geometra |  |
| Autorizzazione allo scarico e pozzo | `scarico-zonder-vergunning` | Septic tank of put zonder vergunning of melding | eerst laten oplossen | `fognatura` |
