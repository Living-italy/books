# Nakijklijst inhoud

Gegenereerd uit `koopdossier/content.nl.json` (versie 2026-10-07). Alles hieronder wacht op controle door Stef.

## 1. Wat uit de gids komt

- **De vijf kopjes per document** komen letterlijk uit hoofdstuk 4.2 (en voor huur, verkoper en makelaar uit 5.2.1 en 5.2.3) van de gids "Huis kopen in Italië - Definitief". Een script heeft gecontroleerd dat elk van de 143 tekstfragmenten woordelijk in de gids staat. Waar een controle in de gids twee documenten dekt (*visura* en *planimetria*, *agibilità* en *APE*), zijn de zinnen over de twee documenten verdeeld. Per document staat de paragraaf in het veld `source`; de app toont die als "Bron: gids 4.2.x".
- **De 15 checkvragen, hulpteksten, gewichten, drempels en oordeelteksten** komen letterlijk uit `bron/conformiteitscheck.php`. Alleen de drie gedachtestreepjes zijn vervangen door een komma, dubbele punt of punt. Een test vergelijkt 5000 willekeurige antwoordcombinaties met de rekenregel van de oude check: score, niveau en aantal aandachtspunten zijn steeds gelijk.
- **Brevo** krijgt dezelfde velden en waarden als in de oude check: `RISICO_NIVEAU` als tekst ("Laag risico", "Verhoogd risico", "Hoog risico") en `BRON` = `conformiteitscheck`. Wil je in Brevo zien welke aanmeldingen uit de nieuwe tool komen, zet `bron` in `index.html` dan op een andere waarde.
- **Drie nieuwe documenten** uit 4.2, die niet in de bouwbrief stonden: *Usucapione* (gebruik door derden, 4.2.4), *Perizia tecnica* (bouwkundige keuring, 4.2.10, staat niet in de opvraagmail omdat je die zelf laat doen) en de verklaring voor een pand van vóór 1967 (4.2.8, alleen bij het profiel "vóór 1 september 1967").
- **Vóór 1967**: de gids zegt dat de verklaring de oorspronkelijke bouwtitel vervangt, maar dat latere aanbouwen wel vergund moeten zijn. Daarom vervallen de bouwvergunningen niet; de verklaring komt erbij.
- **Naam van het rapport**: de gids noemt het rapport over bouw en kadaster de *relazione di regolarità edilizia e catastale* (*RRE*). Die naam staat nu in de app. De gids raadt aan hem vóór het bod te laten maken; de bouwbrief zet hem in de fase vóór het *compromesso*. Ik heb de fase uit de bouwbrief laten staan. Kies zelf.
- **Paragraaf 5.2.1 heeft 20 punten**, niet 19. Alle 20 zijn een clausule geworden, plus 2 uit 5.2.3 (financieringsvoorbehoud en voorbehoud van een gunstige technische controle).

## 2. Niet letterlijk, graag controleren

- **Clausules, Nederlandse tekst**: de gids zegt wat je moet laten vastleggen ("Laat vastleggen dat de verkoper..."). Ik heb dat omgezet in contractzinnen ("De verkoper ...") zonder nieuwe inhoud toe te voegen. Bij het financieringsvoorbehoud en de technische controle staan invulplekken tussen [haken].
- **Clausules, Italiaanse tekst**: de eigen clausuleteksten zijn conceptvertalingen van mij, met de termen van het Italiaanse model. De letterlijke Italiaanse modeltekst staat er apart bij (paragraaf 5). Alle clausules staan op `reviewed: false`.
- **Tekst van rode vlaggen**: korte waarnemingen in eigen woorden, elk gebaseerd op een concreet punt uit 4.2 of 5.2.
- **Opvraagregels (`requestIt`) en de opvraagmail**: conceptvertaling, nog niet nagelezen.
- **Profielvragen en antwoordopties**: nog steeds afgeleid uit de bouwbrief (`profielen-conformiteitscheck.md` ontbreekt).
- **Profielen** (paragraaf 6): opgesteld uit de gids, omdat `profielen-conformiteitscheck.md` ontbreekt. Alle risicoteksten en acties zijn letterlijke zinnen uit de gids, op één samenvatting na. Of een profiel de juiste documenten extra belangrijk maakt, is een keuze van mij.
- **Meetgebeurtenissen**: gelijk aan de oude check (`check_voltooid`, `generate_lead`, `ConformiteitsCheckVoltooid`, `Lead`), plus `check_gestart` en `ConformiteitsCheckGestart`. Score en niveau gaan mee; `value` bij `generate_lead` is weggelaten, omdat GA4 dat als geldbedrag telt.

## 3. Lege velden

- `offerNext`: aanbod voor de volgende stap in de pdf

| Document | Lege velden |
| --- | --- |
| `dichiarazione-pre-1967` Dichiarazione sostitutiva di atto di notorietà | costTime, contract |
| `identiteit-verkoper` Identiteit en bevoegdheid van de verkoper | nameIt, what, why, who, costTime |
| `makelaar` Inschrijving en provisie van de makelaar | nameIt, what, who, costTime |
| `huur-gebruik` Locazione o comodato | what, costTime |
| `impianti` Dichiarazioni di conformità degli impianti | what, who, costTime, contract |
| `riolering-water` Autorizzazione allo scarico e pozzo | what |

Documenten die niet in de tabel staan, zijn volledig gevuld. Afkortingen: what = wat het is, why = waarom het nodig is, who = wie het opvraagt, costTime = kosten en duur, contract = wat je vastlegt in het compromesso.

## 4. Rode vlaggen met voorgestelde ernst

| Document | Vlag-id | Tekst | Voorgestelde ernst | Uit de check |
| --- | --- | --- | --- | --- |
| Atto di provenienza | `verkregen-via-schenking` | De verkoper heeft de woning verkregen via een schenking (_donazione_) | bespreken met je geometra |  |
| Atto di provenienza | `schenking-vordering-ingeschreven` | Er is een vordering tot inkorting of een verzet tegen de schenking ingeschreven (_domanda di riduzione_, _opposizione alla donazione_) | eerst laten oplossen |  |
| Atto di provenienza | `erfenis-niet-alle-erfgenamen` | De woning komt uit een erfenis en niet alle erfgenamen werken mee | niet tekenen |  |
| Atto di provenienza | `aanvaarding-niet-ingeschreven` | De aanvaarding van de nalatenschap (_accettazione dell'eredità_) is niet ingeschreven | eerst laten oplossen |  |
| Atto di provenienza | `voltura-niet-bijgewerkt` | Het pand staat in het kadaster nog op naam van een overledene (geen _voltura catastale_) | eerst laten oplossen |  |
| Atto di provenienza | `meerdere-successies` | Er is meer dan één nalatenschap niet afgewikkeld | eerst laten oplossen |  |
| Atto di provenienza | `successie-onvolledig` | In de successieaangifte is een perceel of aandeel vergeten, zoals de grond om het huis, de oprit of de garage | eerst laten oplossen |  |
| Atto di provenienza | `geen-copia-conforme` | De verkoper levert alleen een scan van de akte, geen gewaarmerkte kopie (_copia conforme_) | bespreken met je geometra |  |
| Usucapione | `buurman-gebruikt-grond` | Een buurman gebruikt al jaren een stuk grond van het perceel | eerst laten oplossen |  |
| Usucapione | `bouwwerk-van-ander` | Er staat een schuur, muur of hek van een ander op het perceel | bespreken met je geometra |  |
| Usucapione | `oprit-door-anderen` | Een oprit of tuin wordt structureel door iemand anders gebruikt | bespreken met je geometra |  |
| Usucapione | `grensakkoord-niet-ingeschreven` | Er is ooit een akkoord over de grenzen gesloten dat niet is ingeschreven | eerst laten oplossen |  |
| Visura catastale | `visura-naam-wijkt-af` | De naam op de _visura_ is niet die van de verkoper | eerst laten oplossen | `eigendom` |
| Visura catastale | `categorie-wijkt-af` | De kadastrale categorie klopt niet met het werkelijke gebruik | eerst laten oplossen | `bestemming` |
| Visura catastale | `meters-wijken-af` | De vierkante meters kloppen niet met het kadaster | bespreken met je geometra | `meters` |
| Visura catastale | `rendita-proposta` | De _rendita_ staat nog als _proposta_, niet als _validata_ of _rettificata_ | bespreken met je geometra |  |
| Visura catastale | `ruralita` | Bij een schuur, stal of hooiberg staat een aantekening van _ruralità_, of het gebouw staat in categorie D/10 | bespreken met je geometra |  |
| Visura catastale | `grond-niet-op-naam` | De grond, oprit of binnenplaats staat niet op naam van de verkoper, of zijn aandeel ontbreekt | eerst laten oplossen |  |
| Planimetria catastale | `planimetria-wijkt-af` | De plattegrond wijkt af van de werkelijke indeling | eerst laten oplossen | `catastale` |
| Planimetria catastale | `planimetria-ontbreekt` | Er is geen _planimetria_ ingeschreven | eerst laten oplossen |  |
| Planimetria catastale | `mansarda-hoogte` | Een _mansarda_ of zolder wordt als woonruimte aangeboden; de binnenhoogte op de tekening bepaalt of dat klopt | bespreken met je geometra |  |
| Planimetria catastale | `grenzen-niet-geverifieerd` | De grenzen van het perceel zijn niet ter plaatse geverifieerd | bespreken met je geometra |  |
| Titoli edilizi e stato legittimo | `verbouwing-zonder-vergunning` | Voor een verbouwing is geen vergunning te vinden | eerst laten oplossen | `urbanistica` |
| Titoli edilizi e stato legittimo | `aanbouw-zonder-vergunning` | Een aanbouw, bijgebouw, veranda, serre of zwembad staat niet in de vergunning | eerst laten oplossen | `aanbouw` |
| Titoli edilizi e stato legittimo | `condono-niet-afgerond` | Er loopt nog een _condono_-aanvraag; een lopende aanvraag is geen legalisatie | eerst laten oplossen |  |
| Titoli edilizi e stato legittimo | `titel-bestaat-niet` | De genoemde bouwtitel bestaat niet, is bij de gemeente niet terug te vinden of hoort bij een ander pand | niet tekenen |  |
| Titoli edilizi e stato legittimo | `lottizzazione-abusiva` | Er is een maatregel of inschrijving wegens illegale verkaveling (_lottizzazione abusiva_) | niet tekenen |  |
| Titoli edilizi e stato legittimo | `kamer-niet-vergund` | Een ruimte wordt als slaap- of woonkamer aangeboden, maar staat in de vergunning als kelder, berging of zolder (_taverna_, _ripostiglio_, _sottotetto_) | eerst laten oplossen |  |
| Titoli edilizi e stato legittimo | `te-dicht-op-de-weg` | Het huis staat binnen de strook langs de weg (_fascia di rispetto stradale_) en is zo niet vergund | eerst laten oplossen |  |
| Titoli edilizi e stato legittimo | `controsoffitto` | Een afwijking wordt alleen weggewerkt met een verlaagd plafond (_controsoffitto_) | bespreken met je geometra |  |
| Dichiarazione sostitutiva di atto di notorietà | `pre1967-zonder-onderbouwing` | Alleen de mededeling dat het huis "oud" is, zonder onderbouwing met luchtfoto's, kaarten of kadaster | eerst laten oplossen |  |
| APE | `ape-ontbreekt` | Het energielabel ontbreekt of is verlopen | bespreken met je geometra | `ape` |
| APE | `ape-klasse-past-niet` | De energieklasse past niet bij het pand dat je hebt gezien | bespreken met je geometra |  |
| APE | `ape-zonder-bezoek` | Het _APE_ is opgesteld zonder bezoek ter plaatse, op basis van foto's of een videogesprek | eerst laten oplossen |  |
| APE | `libretto-niet-bijgehouden` | De installatieboekjes (_libretto di impianto_) zijn niet bijgehouden | bespreken met je geometra |  |
| Identiteit en bevoegdheid van de verkoper | `eigenaar-tekent-niet` | Een echtgenoot, mede-eigenaar of erfgenaam tekent niet mee | niet tekenen | `erfgenamen` |
| Inschrijving en provisie van de makelaar | `makelaar-niet-ingeschreven` | De makelaar is niet ingeschreven | eerst laten oplossen |  |
| Inschrijving en provisie van de makelaar | `provisie-niet-op-papier` | De provisie van de makelaar staat niet op papier | bespreken met je geometra |  |
| Inschrijving en provisie van de makelaar | `provisie-bij-bod` | Het formulier van de makelaar maakt de provisie al opeisbaar bij aanvaarding van het bod | bespreken met je geometra |  |
| Inschrijving en provisie van de makelaar | `makelaar-niet-geverifieerd` | De makelaar geeft informatie door die hij niet zelf heeft gecontroleerd | bespreken met je geometra |  |
| Locazione o comodato | `contract-loopt-door` | Er loopt een huur- of gebruikscontract dat niet eindigt vóór de overdracht | eerst laten oplossen |  |
| Locazione o comodato | `geregistreerd-huurcontract` | Er is een geregistreerd huurcontract met vaste datum | bespreken met je geometra |  |
| Perizia tecnica | `scheuren-fundering` | Scheuren in dragende muren, verzakkingen of aanwijzingen van aardbevingsschade | eerst laten oplossen |  |
| Perizia tecnica | `dak-slecht` | Het dak is verzakt of lekt | bespreken met je geometra |  |
| Perizia tecnica | `vocht` | Vochtplekken, optrekkend vocht of slechte afwatering rondom het huis | bespreken met je geometra |  |
| Perizia tecnica | `installaties-verouderd` | Verouderde elektra zonder aardlekschakelaar, of oude loden of koperen waterleidingen | bespreken met je geometra |  |
| Perizia tecnica | `houtaantasting` | Aantasting door houtworm of boktor in balken of dakconstructie | bespreken met je geometra |  |
| Perizia tecnica | `asbest` | Er zijn asbesthoudende materialen (_amianto_) | eerst laten oplossen |  |
| Relazione di regolarità edilizia e catastale (RRE) | `geen-eigen-controle` | Er is nog geen onafhankelijke controle door een eigen _geometra_ | bespreken met je geometra | `geometra` |
| Relazione di regolarità edilizia e catastale (RRE) | `afwijking-buiten-tolerantie` | Het rapport noemt afwijkingen buiten de toleranties van _Salva Casa_ | eerst laten oplossen |  |
| Ispezione ipotecaria | `hypotheek-of-beslag` | Er staat een hypotheek, beslag of rechtsvordering ingeschreven | eerst laten oplossen | `ipoteca` |
| Ispezione ipotecaria | `ipoteca-giudiziale` | Er staat een _ipoteca giudiziale_ of _ipoteca legale_ ingeschreven | eerst laten oplossen |  |
| Ispezione ipotecaria | `hypotheek-of-beslag-rogito` | Er staat een hypotheek, beslag of rechtsvordering ingeschreven | niet tekenen |  |
| Ispezione ipotecaria | `doorhaling-ontbreekt` | Een afgeloste hypotheek staat nog ingeschreven omdat de doorhaling ontbreekt | eerst laten oplossen |  |
| Agibilità o SCA | `agibilita-ontbreekt` | De _agibilità_ ontbreekt of is nooit aangevraagd | eerst laten oplossen | `agibilita` |
| Agibilità o SCA | `sca-niet-geactualiseerd` | Na een verbouwing is de verklaring niet geactualiseerd | eerst laten oplossen |  |
| Dichiarazioni di conformità degli impianti | `impianti-ontbreken` | Verklaringen voor elektra, gas of verwarming ontbreken | bespreken met je geometra | `impianti` |
| Certificato di destinazione urbanistica | `bestemming-blokkeert-plannen` | De bestemming van de grond blokkeert je plannen | bespreken met je geometra |  |
| Certificato di destinazione urbanistica | `deels-landbouwgrond` | Het perceel is (deels) landbouwgrond, dus geen nieuwbouw mogelijk | bespreken met je geometra |  |
| Certificato di destinazione urbanistica | `risicozone` | De grond ligt in een risicozone | bespreken met je geometra |  |
| Certificato di destinazione urbanistica | `bebouwingsindex-te-laag` | De bebouwingsindex is te laag voor je plannen | bespreken met je geometra |  |
| Certificato di destinazione urbanistica | `cdu-verlopen` | De _CDU_ is ouder dan een jaar, of het bestemmingsplan is sindsdien gewijzigd | eerst laten oplossen |  |
| Vincoli | `vincoli-onbekend` | Het is niet bekend welke beperkingen op het pand rusten | bespreken met je geometra | `vincoli` |
| Vincoli | `monument-voorkooprecht` | Het pand is aangewezen als beschermd monument (_bene culturale_), met voorkooprecht van ministerie, regio en gemeente | bespreken met je geometra |  |
| Vincoli | `usi-civici` | Op de grond rust een oud collectief gebruiksrecht (_uso civico_) | eerst laten oplossen |  |
| Vincoli | `landschap-blokkeert-verbouwing` | Een landschapsbeperking (_vincolo paesaggistico_) maakt je verbouwplannen onmogelijk | bespreken met je geometra |  |
| Prelazione agraria | `geen-afstandsverklaring` | Er is geen kennisgeving gedaan aan de rechthebbenden, of geen schriftelijke afstand na die kennisgeving | eerst laten oplossen |  |
| Prelazione agraria | `pachter-of-boer-buurman` | Er zit een pachter op het land, of de buurman met een aangrenzend perceel is landbouwer | bespreken met je geometra |  |
| Prelazione agraria | `strook-landbouwgrond` | Bij de tuin hoort een strook landbouwgrond | bespreken met je geometra |  |
| Servitù | `toegang-niet-vastgelegd` | De toegangsweg loopt over andermans grond zonder vastgelegd recht | eerst laten oplossen | `servitu` |
| Servitù | `servitu-door-gebruik` | Anderen gebruiken een pad, leiding of toegang over het perceel dat niet in de registers staat | bespreken met je geometra |  |
| Servitù | `strada-vicinale` | De toegangsweg is een _strada vicinale_ met bijdrageplicht, of er zijn achterstanden | bespreken met je geometra |  |
| Documenti del condominio | `achterstallige-bijdragen` | Er zijn achterstallige bijdragen aan de VvE | eerst laten oplossen |  |
| Documenti del condominio | `grote-werken-besloten` | De VvE heeft al grote werken besloten | bespreken met je geometra |  |
| Documenti del condominio | `rei-ontbreekt` | De brandveiligheidscertificering (_REI_) ontbreekt | bespreken met je geometra |  |
| Documenti del condominio | `reglement-beperkt-verhuur` | Het reglement beperkt korte verhuur | bespreken met je geometra |  |
| Documenti del condominio | `geen-verklaring-amministratore` | Er is geen verklaring van de _amministratore_ dat alle bijdragen zijn betaald | eerst laten oplossen |  |
| Autorizzazione allo scarico e pozzo | `scarico-zonder-vergunning` | Septic tank of put zonder vergunning of melding | eerst laten oplossen | `fognatura` |
| Autorizzazione allo scarico e pozzo | `put-zonder-concessione` | Het water van de put wordt gebruikt voor irrigatie, zwembad of agriturismo zonder _concessione_ | eerst laten oplossen |  |

## 5. Clausules

Bij 15 van de 22 clausules staat nu de letterlijke tekst van het standaardmodel erbij: Italiaans uit `bron/contratto-tipo-preliminare.md` (`modelIt`) en Nederlands uit bijlage G (`modelNl`), door een script gecontroleerd tegen beide bestanden. De eigen clausuletekst (`textNl`, `textIt`) volgt het advies uit de gids en gaat vaak verder dan het model; de Italiaanse versie daarvan blijft een conceptvertaling, nu met de termen van het model (_Parte Promittente Venditrice_, _Parte Promissaria Acquirente_). Waar het model afwijkt van de gids, staat dat in `modelDiff` en in de laatste kolom.

| Id | Titel | In het model | Verschil met de gids |
| --- | --- | --- | --- |
| `partijen-gegevens` | Gegevens van koper en verkoper | Bijlage G, aanhef (partijen) |  |
| `omschrijving-object` | Omschrijving van de woning en alle kadastrale nummers | Bijlage G, art. 1 en 2 (a corpo) | Het model kiest vast voor a corpo (prijs voor het geheel). De gids raadt aan bewust te kiezen en elk kadastraal nummer met het aandeel apart te vermelden. |
| `eigendomstitel-vrij` | Eigendomstitel en vrije overdraagbaarheid | Bijlage G, art. 1 (herkomst) en art. 2 |  |
| `vrij-van-hypotheken` | Vrij van hypotheken en beslagen | Bijlage G, art. 2 | Het model hecht de uitkomst van de ispezione ipotecaria niet aan het contract. |
| `servitu` | Erfdienstbaarheden, toegangsweg en put | Bijlage G, art. 2 (alleen "indien en voor zover aanwezig") | Het model noemt erfdienstbaarheden alleen "indien en voor zover aanwezig". De gids vraagt een volledige opgave, ook van niet-geregistreerde, en de papieren van een put. |
| `geen-bezit-derden` | Geen gebruik of bezit door derden | **niet in het model** |  |
| `vergunningen` | Verklaring over de vergunningen | Bijlage G, art. 3 |  |
| `kadastrale-conformiteit` | Kadastrale conformiteit op kosten van de verkoper | Bijlage G, art. 1 (plattegrond) en art. 4 |  |
| `prelazione-agraria` | Agrarisch voorkooprecht en usi civici | **niet in het model** |  |
| `vincoli` | Belemmeringen (vincoli) | Bijlage G, art. 2 (vrij van lasten en beperkingen) |  |
| `ape` | Energiecertificaat (APE) | Bijlage G, art. 2 | Het model levert het APE pas bij de akte. De gids raadt aan het ruim vóór de overdracht te krijgen. |
| `koopprijs` | Koopprijs en betaling | Bijlage G, art. 6 |  |
| `datum-rogito` | Datum van de akte | Bijlage G, art. 4 (daar als fatale termijn) | Het model maakt de datum fataal (essenziale). De gids raadt aan bewust te kiezen of de termijn fataal is. |
| `opschortende-voorwaarden` | Opschortende, geen ontbindende voorwaarden | **niet in het model** | Het model heeft geen opschortende voorwaarden en maakt in art. 8 juist van elke afspraak een ontbindende voorwaarde (clausola risolutiva espressa). |
| `caparra` | Aanbetaling (caparra confirmatoria) | Bijlage G, art. 5, 6 en 7 |  |
| `aanvullende-afspraken` | Aanvullende afspraken | **niet in het model** |  |
| `geplande-ingreep` | Geplande verbouwing is mogelijk | **niet in het model** |  |
| `vrij-van-huur` | Levering vrij van huur en gebruik | Bijlage G, art. 2 (vrij van personen of huurcontract) |  |
| `condominio` | Bijdragen en werken van de VvE | Bijlage G, art. 2 (VvE) | Het model legt alleen kosten uit besluiten tot de datum van het compromesso bij de verkoper. De gids raadt aan dat ook werken die vóór de akte worden besloten voor de verkoper blijven, en vraagt een verklaring van de amministratore. |
| `eigendomstitel` | Eigendomstitel en vererving | Bijlage G, art. 1 (herkomst) | Het model noemt de successie, maar niet de ingeschreven aanvaarding en de voltura catastale. |
| `financieringsvoorbehoud` | Financieringsvoorbehoud | **niet in het model** |  |
| `technische-controle` | Voorbehoud van een gunstige technische controle | **niet in het model** |  |

## 6. Profielen

De bouwbrief noemt tien basisprofielen in `profielen-conformiteitscheck.md`. Dat bestand ontbreekt, dus de profielen zijn opgesteld uit de gids. Er zijn er twaalf geworden, één per profielvlag die iets verandert, plus de uitzondering voor een getekend _compromesso_ en een basisset die altijd geldt. Het dossier toont bovenaan hoogstens drie risicoteksten (laagste rang eerst) en een uitklapbare lijst met acties per fase. De pdf toont dezelfde acties in de actielijst.

### Risicoteksten

| Id | Geldt als | Rang | Bron | Tekst |
| --- | --- | --- | --- | --- |
| `compromesso-getekend` | je hebt het _compromesso_ al getekend | 1 | gids 5.2.7 | Er kunnen na het _compromesso_ bouwovertredingen opduiken. Is legalisatie niet mogelijk, of levert de verkoper de vereiste stukken niet, dan is er sprake van wanprestatie aan zijn kant. Bij wezenlijke of niet-geregulariseerde afwijkingen kun je weigeren te passeren, ontbinding van de overeenkomst vorderen, prijsvermindering eisen of schadevergoeding vragen. Je zit dus niet automatisch vast en je bent je _caparra_ niet automatisch kwijt. |
| `erfgenamen` | erfgenamen verkopen | 2 | gids 4.2.1 | Erfenis of verdeling van een nalatenschap vraagt om drie controles. Ten eerste of alle erfgenamen hebben meegewerkt en of de nalatenschap correct is afgewikkeld, met een ingediende _dichiarazione di successione_. Ten tweede of de aanvaarding van de nalatenschap is ingeschreven (_accettazione dell'eredità_). Ten derde of de kadastrale tenaamstelling daadwerkelijk is bijgewerkt (_voltura catastale_). |
| `verbouwd-notaris` | het pand is verbouwd of uitgebreid | 2 | gids 5.3, 4.2.8 | De notaris controleert niet zelf de _conformità urbanistica_, dus of het huis overeenstemt met de gemeentelijke bouwvergunningen, en hij doet geen bouwkundige controle ter plaatse. Daardoor kunnen onregelmatigheden gewoon meeverkopen. |
| `monument` | het pand is een beschermd monument | 2 | gids 4.2.7 | Elke eigendomsovergang moet binnen 30 dagen worden gemeld bij de _soprintendenza_, in de praktijk door de notaris. Vanaf die melding hebben het ministerie, de regio en de gemeente 60 dagen om hun voorkooprecht uit te oefenen en het pand tegen dezelfde prijs zelf te kopen. In die periode is de koop wel geldig maar nog niet werkzaam, en mag de verkoper je de sleutels niet geven. |
| `voor1967` | het pand is gebouwd vóór 1 september 1967 | 3 | gids 4.2.8 | Voor woningen waarvan de bouw vóór 1 september 1967 is begonnen, gelden andere regels. Veel van die gebouwen hebben geen formele bouwvergunning, omdat die destijds nog niet verplicht was. |
| `verbouwd-agibilita` | het pand is verbouwd of uitgebreid | 3 | gids 4.2.9 | Bij recente verbouwingen betekent een ontbrekende, niet-geactualiseerde verklaring meestal dat de aannemer of de eigenaar de procedure nooit heeft afgerond. Dat is op zichzelf een reden om door te vragen, ook als het huis er verder prima uitziet. |
| `grond-voorkooprecht` | er hoort landbouwgrond bij | 3 | gids 4.2.6 | Is de rechthebbende niet of niet correct geïnformeerd, dan kan hij tot één jaar na de inschrijving van de koopakte de koop betwisten en het perceel tegen dezelfde prijs opeisen. Je kunt het perceel dus achteraf kwijtraken. |
| `grond-pachter` | de landbouwgrond wordt bewerkt of verpacht | 3 | gids 4.2.6 | Het komt in de eerste plaats toe aan de pachter, de _affittuario_ die het land al minstens twee jaar als _coltivatore diretto_ bewerkt. |
| `monument-toleranties` | het pand is een beschermd monument | 3 | gids 4.2.8 | Op panden die op grond van _D.Lgs_. 42/2004 beschermd zijn, geldt het tolerantieregime niet. Daar moet elke afwijking langs de gewone weg worden rechtgezet. |
| `hypotheek-caparra` | je financiert met een hypotheek | 3 | gids 5.2, 5.2.3 | Zonder financieringsclausule loop je anders het risico je aanbetaling kwijt te raken als de bank onverhoopt niet financiert. In alle gevallen moet je de _caparra_ zelf voorschieten want een eventuele hypotheek op het huis krijg je pas na de overdracht. |
| `grond-belasting` | er hoort landbouwgrond bij | 4 | gids 5.4 | Is de grond géén _pertinenza_, dan bestaat je akte fiscaal uit twee delen: het gebouw tegen 2 of 9 % van de lage kadastrale waarde, en de grond tegen 9 % van de werkelijke prijs, of tegen 15 % als het landbouwgrond is en jij geen erkend landbouwer bent. |
| `vincolo` | het pand ligt in een beschermd gebied | 4 | gids 4.2.7, 5.2.2 | Het _vincolo paesaggistico_ beschermt het landschap en raakt vooral wat je mag verbouwen, niet de verkoop zelf. Wil je bouwen terwijl er belemmeringen zijn dan heb je toestemming nodig van de _soprintendenza_, de territoriale dienst van het Italiaanse ministerie van Cultuur. |
| `condominio` | de woning hoort bij een _condominio_ (VvE) | 4 | gids 5.3 | Naar Italiaans recht ben je als koper hoofdelijk aansprakelijk voor de VvE-bijdragen over het lopende en het voorafgaande beheersjaar. De VvE mag jou aanspreken voor schulden van de verkoper, en die regel is dwingend. |
| `verhuurd` | de woning is verhuurd of in gebruik | 4 | gids 5.2.1 | Is er een lopende huurovereenkomst, dan neem jij die als koper over: koop breekt naar Italiaans recht geen huur. Bij een geregistreerd huurcontract met vaste datum kun je de huurder niet zomaar buiten de deur zetten. |
| `vennootschap-btw` | een vennootschap verkoopt | 5 | gids 5.4 | Let op: btw geldt alleen als je van een bouwbedrijf (_impresa costruttrice/ristrutturatrice_) koopt binnen vijf jaar na voltooiing van de werken (of als het bedrijf uitdrukkelijk voor btw kiest). Koop je daarbuiten van een bedrijf, dan is de verkoop meestal btw-vrij en betaal je juist de _imposta di registro_ (9 %, of 2% bij _prima casa_). Dat scheelt meer dan het lijkt, want de grondslag verandert mee. |
| `prima-casa` | het wordt je hoofdverblijf (_prima casa_) | 5 | gids hoofdstuk 6 | Heb je gekocht met de _prima casa_-korting op de overdrachtsbelasting, dan moet je je binnen achttien maanden na de akte laten inschrijven in de gemeente waar het huis staat. Doe je dat niet, dan vordert de fiscus het belastingvoordeel terug, verhoogd met rente en een boete van 30 %. |
| `afstand` | je koopt op afstand | 6 | gids 2.2.6 | Kun je niet zelf bij het _rogito_ zijn, dan kun je iemand volmacht geven om namens jou te tekenen. Dat gebeurt met een _procura speciale_: een volmacht voor precies deze ene aankoop, met zo smal mogelijk omschreven bevoegdheden. |
| `taal` | je volgt de akte niet in het Italiaans | 6 | gids 2.2.6 | Notariële akten worden in het Italiaans opgesteld. Spreek je onvoldoende Italiaans, dan moet de notaris ervoor zorgen dat je de akte begrijpt. |

### Acties

| Id | Geldt als | Fase | Bron | Tekst |
| --- | --- | --- | --- | --- |
| `basis-keuring-voor-bod` | altijd (basisset) | bod | gids 4.2.10 | Plan de keuring vóór je bod als het even kan. Dan onderhandel je met het rapport in de hand, in plaats van achteraf te moeten proberen de prijs nog aangepast te krijgen. |
| `erfgenamen-successies` | erfgenamen verkopen | bod | gids 4.2.1 | Let er vooral op of er méér dan één niet-geregulariseerde successie is. Reken op maanden, en vraag hier daarom naar vóór je een leveringsdatum afspreekt. |
| `vennootschap-btw-vragen` | een vennootschap verkoopt | bod | gids 5.4 | Vraag daarom bij een nieuwbouwwoning die al langer te koop staat vóór je bod wanneer de werken zijn voltooid en of de verkoper met of zonder btw verkoopt. Laat het antwoord in de _proposta_ opnemen. |
| `vennootschap-visura-camerale` | een vennootschap verkoopt | bod | gids 5.2.6 | Vraag een _visura camerale_ op: daarin zie je hoe lang het bedrijf bestaat en of er een insolventieprocedure loopt. |
| `voor1967-bewijs` | het pand is gebouwd vóór 1 september 1967 | bod | gids 4.2.8 | De _geometra_ en de notaris baseren zich in zulke gevallen op ander bewijs: historische luchtfoto's; oude kaarten; registraties in het kadaster. Vraag als koper altijd om die onderbouwing wanneer deze route wordt gebruikt, en neem geen genoegen met de enkele mededeling dat het huis "oud" is. |
| `verbouwd-titel` | het pand is verbouwd of uitgebreid | bod | gids 4.2.8 | Neem dus geen genoegen met het feit dat er een titel in de akte staat. Laat je _geometra_ controleren dat die titel daadwerkelijk bestaat, bij de gemeente is terug te vinden, en op dít pand betrekking heeft. |
| `verbouwd-kamers` | het pand is verbouwd of uitgebreid | bod | gids 4.2.8 | Vraag de makelaar daarom bij elke kamer onder de grond of onder het dak naar de bestemming volgens de vergunning, en laat de _geometra_ de situatie met de tekeningen vergelijken. |
| `grond-berekening` | er hoort landbouwgrond bij | bod | gids 5.4 | Vraag de berekening vóór je bod, want bij veel grond bepaalt deze post of het pand binnen je budget past. |
| `grond-grenzen` | er hoort landbouwgrond bij | bod | gids 4.2.2 | Laat bij een perceel van enige omvang een _geometra_ de grenzen ter plaatse verifiëren voordat je tekent, zoals ook beschreven in sectie 9.6.1. |
| `vincolo-usi-civici` | het pand ligt in een beschermd gebied | bod | gids 4.2.6 | Wat je moet doen: vraag de gemeente om een verklaring over de aanwezigheid van _usi civici_ op de betrokken _particelle_, en laat de notaris dit uitdrukkelijk nagaan. |
| `condominio-vragen` | de woning hoort bij een _condominio_ (VvE) | bod | gids 1.2.1 | Vraag altijd naar de hoogte van de condominiumkosten, eventuele achterstallige betalingen door de verkoper, en de notulen van recente vergaderingen van eigenaren. |
| `hypotheek-toezegging` | je financiert met een hypotheek | bod | gids 5.2.3 | Vraag daarom vooraf bij de bank een voorlopige financieringstoezegging (_delibera reddituale_ of _parere di fattibilità_): daarmee maak je je bod sterker en kun je het voorbehoud kort houden of laten vervallen. |
| `verhuurd-contract` | de woning is verhuurd of in gebruik | bod | gids 5.2.1 | Vraag om een kopie van het contract en van de registratie, en laat in het _compromesso_ vastleggen wanneer en in welke staat het pand wordt opgeleverd. |
| `basis-zelf-laten-opnemen` | altijd (basisset) | compromesso | gids 5.2.1 | Ontbindende en opschortende voorwaarden, garanties over vergunningen en afspraken over de vindplaats van de betaling moet je zelf laten opnemen. |
| `getekend-jurist` | je hebt het _compromesso_ al getekend | compromesso | gids 5.2.7 | Laat je in zo'n situatie wel bijstaan door een Italiaanse jurist. |
| `erfgenamen-compromesso` | erfgenamen verkopen | compromesso | gids 5.2.1 | Is het vererfd, laat dan opnemen dat de _dichiarazione di successione_ is ingediend, dat de aanvaarding van de nalatenschap, de _accettazione dell'eredità_, is ingeschreven, en dat de kadastrale tenaamstelling via een _voltura catastale_ is bijgewerkt vóór de akte, alles op kosten van de verkoper. |
| `erfgenamen-leveringsdatum` | erfgenamen verkopen | compromesso | gids 5.2.1 | Staat het pand nog op naam van een overledene en is er meer dan één successie niet afgewikkeld, spreek dan geen krappe leveringsdatum af: het opsporen van alle rechthebbenden kost maanden. |
| `mede-eigenaren-toestemming` | er zijn meerdere eigenaren | compromesso | gids 5.3 | Ga na dat alle eigenaren meetekenen, ook een echtgenoot of mede-erfgenaam. De notaris controleert bij meerdere verkopers of iedereen toestemming heeft gegeven. |
| `mede-eigenaren-caparra` | er zijn meerdere eigenaren | compromesso | gids 5.2.1 | De _caparra confirmatoria_: het bedrag, de betaalwijze, aan wie precies wordt betaald, de verwijzing naar de uitgeschreven cheques of overboekingen, en bij meerdere verkopers de verdeling over de mede-eigenaren. |
| `verbouwd-termijn` | het pand is verbouwd of uitgebreid | compromesso | gids 5.2.3 | Houd bij zulke voorwaarden rekening met doorlooptijden bij de gemeente: die heeft wettelijk 30 dagen om inzage te geven in het bouwarchief (_accesso agli atti_), en in de praktijk duurt het vaak langer. |
| `grond-particella` | er hoort landbouwgrond bij | compromesso | gids 4.2.6 | Het voorkooprecht kent geen minimumoppervlakte. Vraag je notaris daarom per _particella_ of het recht geldt, ook als de buurman zegt geen belangstelling te hebben. |
| `grond-planning` | er hoort landbouwgrond bij | compromesso | gids 4.2.6 | De kennisgeving aan de rechthebbende gaat per aangetekende brief, met het voorlopige koopcontract erbij. Vanaf ontvangst heeft hij 30 dagen om te reageren. Reken die 30 dagen dus in je planning mee, bovenop de 7 tot 30 dagen voor de _CDU_. |
| `vincolo-voorwaarde` | het pand ligt in een beschermd gebied | compromesso | gids 5.2.2 | Eventueel neem je een opschortende voorwaarde die je beschermt tegen belemmeringen die in het compromesso niet of niet voldoende vermeld zijn. |
| `hypotheek-aanvraag` | je financiert met een hypotheek | compromesso | gids 5.2.3 | Dien je aanvraag dus meteen in, bewaar elke ontvangstbevestiging en vraag bij afwijzing een schriftelijke weigering met datum. |
| `hypotheek-volmacht` | je financiert met een hypotheek én je koopt op afstand | compromesso | gids 2.2.6 | Koop je met een Italiaanse hypotheek, overleg dan tijdig met bank en notaris. Een volmacht om te kopen geeft niet vanzelf de bevoegdheid om ook de hypotheekakte te tekenen, en banken stellen eigen eisen. |
| `afstand-volmacht` | je koopt op afstand | compromesso | gids 2.2.6 | Begin bij de Italiaanse notaris die de akte zal verlijden. Laat hem de tekst opstellen of vooraf goedkeuren, en laat de volmacht pas daarna tekenen. |
| `afstand-gevolmachtigde` | je koopt op afstand én je volgt de akte niet in het Italiaans | compromesso | gids 2.2.6 | Kies je een gevolmachtigde die Italiaans spreekt, dan is er bij het _rogito_ geen tolk en geen schriftelijke vertaling nodig. |
| `basis-conceptakte` | altijd (basisset) | rogito | gids 5.3 | Vraag de ontwerpakte enkele dagen vooraf op en laat hem doornemen door je eigen jurist of tolk. |
| `getekend-verlenging` | je hebt het _compromesso_ al getekend | rogito | gids 5.2.7 | Is de overtreding herstelbaar, spreek dan met de verkoper een verlenging van de termijn af zodat de legalisatieprocedure kan worden afgerond (zie sectie 5.2.4). |
| `getekend-verlenging-schriftelijk` | je hebt het _compromesso_ al getekend | rogito | gids 5.2.4 | Leg een verlenging daarom altijd schriftelijk vast, met verwijzing naar het oorspronkelijke contract en de oorspronkelijke termijn, de nieuwe datum, of die nieuwe termijn wél of niet fataal is, wat er met de _caparra_ gebeurt, en eventuele nieuwe voorwaarden. |
| `grond-uitsplitsen` | er hoort landbouwgrond bij | rogito | gids 5.4 | Laat de notaris de prijs in de akte uitsplitsen over de woning, de _pertinenze_ en de grond, en vraag hem vóór het tekenen om een berekening per onderdeel. |
| `grond-aanschrijving` | er hoort landbouwgrond bij | rogito | gids 5.3 | Laat de notaris de bewijzen van de aanschrijving daarom aan het dossier hechten en bewaar ze zelf ook. |
| `monument-melding` | het pand is een beschermd monument | rogito | gids 5.3 | Laat de notaris de melding daarom zelf verzorgen en vraag om het ontvangstbewijs. |
| `monument-planning` | het pand is een beschermd monument | rogito | gids 5.3 | Zolang de termijn loopt mag de verkoper het pand niet aan je leveren. Plan verhuizing, aannemer en opzegging van je huidige woning pas na afloop van de termijn. |
| `condominio-verklaring` | de woning hoort bij een _condominio_ (VvE) | rogito | gids 5.3, 4.2.11 | Koop je een appartement, dan is er één document dat je niet mag overslaan: een recente verklaring van de _amministratore_ over de stand van de betalingen en over besloten maar nog niet uitgevoerde werkzaamheden. Vraag de verklaring één tot twee weken vóór de akte aan, en niet later. |
| `condominio-depot` | de woning hoort bij een _condominio_ (VvE) | rogito | gids 5.3 | Laat de verklaring aan de akte hechten en laat, als er iets openstaat, dat bedrag bij de notaris in depot houden tot het is voldaan. |
| `hypotheek-bank` | je financiert met een hypotheek | rogito | gids 5.2.3 | Informeer je bank tijdig over de geplande datum, zodat de cheque klaarligt of de transactie kan worden uitgevoerd. |
| `afstand-origineel` | je koopt op afstand | rogito | gids 5.3 | Het origineel moet tijdig fysiek bij de notaris liggen; een scan per mail volstaat alleen om de tekst vooraf te laten controleren. |
| `taal-bozza` | je volgt de akte niet in het Italiaans | rogito | gids 5.3 | Vraag minimaal een week van tevoren een conceptakte (_bozza_) op, zodat je deze rustig kunt (laten) vertalen en weet wat je verklaart. |
| `taal-tolk` | je volgt de akte niet in het Italiaans | rogito | gids 5.4 | Vertaler/tolk bij de akte (als je geen Italiaans spreekt): €500 - €800, omdat de tolk de hele akte moet vertalen en aanwezig moet zijn. |
| `prima-casa-pertinenze` | het wordt je hoofdverblijf (_prima casa_) | rogito | gids 5.4 | Laat je notaris de situatie m.b.t. de _pertinenze_ uitdrukkelijk in de akte vastleggen, dat is later je belangrijkste bewijs. |

### Extra belangrijke documenten per profiel

| Document | Extra belangrijk als |
| --- | --- |
| Atto di provenienza | erfgenamen verkopen |
| Usucapione | er hoort landbouwgrond bij |
| Visura catastale | erfgenamen verkopen |
| Planimetria catastale | het pand is verbouwd of uitgebreid |
| Titoli edilizi e stato legittimo | het pand is gebouwd vóór 1 september 1967, het pand is verbouwd of uitgebreid, je financiert met een hypotheek |
| Identiteit en bevoegdheid van de verkoper | erfgenamen verkopen, er zijn meerdere eigenaren, een vennootschap verkoopt |
| Locazione o comodato | de woning is verhuurd of in gebruik |
| Relazione di regolarità edilizia e catastale (RRE) | het pand is verbouwd of uitgebreid, je financiert met een hypotheek |
| Agibilità o SCA | het pand is verbouwd of uitgebreid |
| Certificato di destinazione urbanistica | er hoort landbouwgrond bij |
| Vincoli | het pand ligt in een beschermd gebied, het pand is een beschermd monument |
| Prelazione agraria | de landbouwgrond wordt bewerkt of verpacht |
| Servitù | er hoort landbouwgrond bij |

### Niet letterlijk uit de gids

- "Ga na dat alle eigenaren meetekenen, ook een echtgenoot of mede-erfgenaam. De notaris controleert bij meerdere verkopers of iedereen toestemming heeft gegeven." (samenvatting van gids 5.3 (controles notaris))
