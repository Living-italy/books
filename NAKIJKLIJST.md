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
- **Clausules, Italiaanse tekst**: conceptvertalingen van mij. Bijlage G (model *compromesso* met vertaling, op stefsmulders.nl) kon ik niet openen. Staat daar Italiaanse tekst voor dezelfde afspraak, dan moet die er letterlijk in. Alle clausules staan op `reviewed: false`.
- **Tekst van rode vlaggen**: korte waarnemingen in eigen woorden, elk gebaseerd op een concreet punt uit 4.2 of 5.2.
- **Opvraagregels (`requestIt`) en de opvraagmail**: conceptvertaling, nog niet nagelezen.
- **Profielvragen en antwoordopties**: nog steeds afgeleid uit de bouwbrief (`profielen-conformiteitscheck.md` ontbreekt).
- **Meetgebeurtenissen**: de oude check stuurde `check_voltooid` en `generate_lead` naar GA4 en `ConformiteitsCheckVoltooid` en `Lead` naar Meta, met score en niveau erbij. De nieuwe app stuurt volgens de bouwbrief `check_gestart`, `check_afgerond` en `check_aangemeld` (bij Meta `Lead`), zonder score. Pas dat aan als je rapportages op de oude namen draaien.

## 3. Lege velden

- `check.riskTexts`: nog geen risicoteksten (bron: `profielen-conformiteitscheck.md`)
- `check.actions`: nog geen acties per profiel
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

| Id | Titel | Bron | Voorgesteld bij profiel |
| --- | --- | --- | --- |
| `partijen-gegevens` | Gegevens van koper en verkoper | gids 5.2.1 | altijd |
| `omschrijving-object` | Omschrijving van de woning en alle kadastrale nummers | gids 5.2.1 | altijd |
| `eigendomstitel-vrij` | Eigendomstitel en vrije overdraagbaarheid | gids 5.2.1 | altijd |
| `vrij-van-hypotheken` | Vrij van hypotheken en beslagen | gids 5.2.1 | altijd |
| `servitu` | Erfdienstbaarheden, toegangsweg en put | gids 5.2.1 | altijd |
| `geen-bezit-derden` | Geen gebruik of bezit door derden | gids 5.2.1 | altijd |
| `vergunningen` | Verklaring over de vergunningen | gids 5.2.1 | altijd |
| `kadastrale-conformiteit` | Kadastrale conformiteit op kosten van de verkoper | gids 5.2.1 | altijd |
| `prelazione-agraria` | Agrarisch voorkooprecht en usi civici | gids 5.2.1 | grond |
| `vincoli` | Belemmeringen (vincoli) | gids 5.2.1 | vincolo, monument |
| `ape` | Energiecertificaat (APE) | gids 5.2.1 | altijd |
| `koopprijs` | Koopprijs en betaling | gids 5.2.1 | altijd |
| `datum-rogito` | Datum van de akte | gids 5.2.1 | altijd |
| `opschortende-voorwaarden` | Opschortende, geen ontbindende voorwaarden | gids 5.2.1 | altijd |
| `caparra` | Aanbetaling (caparra confirmatoria) | gids 5.2.1 | altijd |
| `aanvullende-afspraken` | Aanvullende afspraken | gids 5.2.1 | altijd |
| `geplande-ingreep` | Geplande verbouwing is mogelijk | gids 5.2.1 | altijd |
| `vrij-van-huur` | Levering vrij van huur en gebruik | gids 5.2.1 | altijd |
| `condominio` | Bijdragen en werken van de VvE | gids 5.2.1 | condominio |
| `eigendomstitel` | Eigendomstitel en vererving | gids 5.2.1 | erfgenamen |
| `financieringsvoorbehoud` | Financieringsvoorbehoud | gids 5.2.3 | hypotheek |
| `technische-controle` | Voorbehoud van een gunstige technische controle | gids 4.2.8, 4.2.10, 5.2.3 | altijd |
