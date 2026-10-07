<?php
/**
 * ============================================================================
 *  "IS DIT HUIS WEL LEGAAL?"  —  Conformiteitscheck Italiaans vastgoed
 *  Lead magnet / interactieve tool voor stefsmulders.nl
 *
 *  Stack: single-file PHP (Aruba / mijn.host) + Brevo + Meta Pixel + GA4
 *  -------------------------------------------------------------------------
 *  CONFIGUREER HIERONDER (3 dingen):
 *    1. BREVO_API_KEY   -> je Brevo v3 API-sleutel
 *    2. BREVO_LIST_ID   -> ID van de nieuwe lijst "Conformiteitscheck"
 *    3. META_PIXEL_ID / GA4_ID -> onderaan in de <head>
 * ============================================================================
 */

// ---------- CONFIG --------------------------------------------------------
const BREVO_API_KEY = 'XXXX-VUL-JE-BREVO-API-KEY-IN-XXXX';
const BREVO_LIST_ID = 0; // <-- vul het numerieke lijst-ID in, bv. 12
const FROM_TOOL     = 'conformiteitscheck';

// ---------- AJAX ENDPOINT: e-mail -> Brevo --------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'subscribe') {
    header('Content-Type: application/json; charset=utf-8');

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $voornaam = htmlspecialchars(trim($_POST['voornaam'] ?? ''), ENT_QUOTES);
    $score = (int)($_POST['score'] ?? 0);
    $niveau = preg_replace('/[^A-Za-z ]/', '', $_POST['niveau'] ?? '');
    $aandacht = (int)($_POST['aandacht'] ?? 0);

    if (!$email) {
        echo json_encode(['ok' => false, 'error' => 'Vul een geldig e-mailadres in.']);
        exit;
    }

    $payload = [
        'email'         => $email,
        'updateEnabled' => true,
        'listIds'       => [BREVO_LIST_ID],
        'attributes'    => [
            'VOORNAAM'       => $voornaam,
            'RISICO_SCORE'   => $score,
            'RISICO_NIVEAU'  => $niveau,
            'AANDACHTSPUNTEN'=> $aandacht,
            'BRON'           => FROM_TOOL,
        ],
    ];

    $ch = curl_init('https://api.brevo.com/v3/contacts');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . BREVO_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 201 = nieuw contact, 204 = bestaand contact bijgewerkt
    if ($code === 201 || $code === 204) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Er ging iets mis. Probeer het zo nog eens.', 'debug' => $code]);
    }
    exit;
}

// ---------- VRAGEN (één bron van waarheid) --------------------------------
// answer: "ja" = geruststellend (0 risico), "nee" = vol gewicht, "weet niet" = 0.7x gewicht
$VRAGEN = [
    ['id'=>'catastale','gewicht'=>10,
     'vraag'=>'Komt de kadastrale plattegrond (<em>planimetria catastale</em>) exact overeen met de werkelijke indeling van het huis?',
     'help'=>'Een afwijking betekent dat een verbouwing nooit is gemeld. Dit moet kloppen vóór de notaris.'],
    ['id'=>'urbanistica','gewicht'=>12,
     'vraag'=>'Is voor élke verbouwing en aanbouw een bouwvergunning aanwezig (<em>permesso di costruire / SCIA / DIA</em>)?',
     'help'=>'Onvergunde bouw (<em>abuso edilizio</em>) is de meest voorkomende en duurste valkuil op het platteland.'],
    ['id'=>'agibilita','gewicht'=>7,
     'vraag'=>'Is er een geldig bewoonbaarheidscertificaat (<em>certificato di agibilità / abitabilità</em>)?',
     'help'=>'Zonder dit certificaat is het huis formeel niet bewoonbaar verklaard.'],
    ['id'=>'eigendom','gewicht'=>7,
     'vraag'=>'Heb je de <em>visura catastale</em> én de eigendomsakte (<em>atto di provenienza</em>) gezien, en komen die overeen met de verkoper?',
     'help'=>'Controleer of de verkoper ook echt de juridische eigenaar is.'],
    ['id'=>'ipoteca','gewicht'=>10,
     'vraag'=>'Is uit de hypotheekinspectie (<em>ispezione ipotecaria</em>) gebleken dat er géén hypotheek, beslag (<em>pignoramento</em>) of schuld op het pand rust?',
     'help'=>'Schulden volgen het pand, niet de verkoper. Dit moet schoon zijn.'],
    ['id'=>'erfgenamen','gewicht'=>7,
     'vraag'=>'Staan álle eigenaren en erfgenamen achter de verkoop (vaak een issue bij geërfde huizen)?',
     'help'=>'Eén ontbrekende erfgenaam kan de hele verkoop maanden of jaren blokkeren.'],
    ['id'=>'meters','gewicht'=>6,
     'vraag'=>'Komen de werkelijke vierkante meters overeen met de oppervlakte in het kadaster?',
     'help'=>'Afwijkende m² wijzen op niet-geregistreerde wijzigingen en raken de belasting.'],
    ['id'=>'aanbouw','gewicht'=>9,
     'vraag'=>'Zijn alle bijgebouwen, veranda&rsquo;s, overkappingen, pergola&rsquo;s en een eventueel zwembad officieel geregistreerd?',
     'help'=>'Juist deze "kleine" toevoegingen zijn vaak nooit aangegeven.'],
    ['id'=>'bestemming','gewicht'=>7,
     'vraag'=>'Komt het werkelijke gebruik (woonhuis) overeen met de bestemming in het kadaster &mdash; dus geen schuur of garage stiekem omgebouwd tot woonruimte?',
     'help'=>'Wijziging van bestemming zonder vergunning is een serieuze onregelmatigheid.'],
    ['id'=>'impianti','gewicht'=>4,
     'vraag'=>'Zijn er conformiteitsverklaringen voor de elektra- en gasinstallatie (<em>dichiarazione di conformità impianti</em>)?',
     'help'=>'Zonder verklaring moet je installaties mogelijk volledig laten certificeren.'],
    ['id'=>'ape','gewicht'=>2,
     'vraag'=>'Is er een geldig energiecertificaat (<em>APE</em>)?',
     'help'=>'Verplicht bij verkoop; het ontbreken is een signaal van slordige administratie.'],
    ['id'=>'fognatura','gewicht'=>4,
     'vraag'=>'Is de afvoer / septic tank (<em>fognatura / fossa biologica</em>) in orde en officieel gemeld?',
     'help'=>'Op het platteland vaak niet aangesloten of niet conform &mdash; dure verrassing.'],
    ['id'=>'servitu','gewicht'=>5,
     'vraag'=>'Is de toegangsweg eigendom van het pand, of via een vastgelegde erfdienstbaarheid (<em>servitù</em>) geregeld?',
     'help'=>'Een niet-vastgelegde toegangsweg kan je letterlijk insluiten.'],
    ['id'=>'vincoli','gewicht'=>4,
     'vraag'=>'Weet je of er landschaps- of monumentenbeperkingen (<em>vincolo paesaggistico</em>) of agrarische beperkingen gelden?',
     'help'=>'Beperkingen kunnen verbouwen of uitbreiden onmogelijk maken.'],
    ['id'=>'geometra','gewicht'=>6,
     'vraag'=>'Heeft een onafhankelijke <em>geometra</em> het pand juridisch-bouwkundig gecontroleerd vóórdat je een bod doet?',
     'help'=>'De geometra is je belangrijkste bondgenoot. Liever vooraf dan achteraf.'],
];

/*
 * Rest van het bestand (HTML, CSS en JavaScript) weggelaten. Relevant daaruit:
 *
 *  score:  nee = gewicht, weet = gewicht * 0.7, ja = 0; score = Math.round(som)
 *  niveau: score <= 15  'Laag risico'      kleur #5f6a3a
 *            verdict 'Een geruststellend beeld'
 *            sub     'Er springen weinig rode vlaggen uit. Laat tóch een onafhankelijke geometra de papieren controleren vóór je tekent — de laatste 10% zit in de details.'
 *          score <= 40  'Verhoogd risico'  kleur #c98a2b
 *            verdict 'Let op een paar punten'
 *            sub     'Er zijn zaken die je echt moet uitzoeken vóór je een bod doet. Loop de aandachtspunten hieronder na en leg ze voor aan een geometra.'
 *          anders       'Hoog risico'      kleur #c0623f
 *            verdict 'Wees voorzichtig'
 *            sub     'Dit pand vraagt om grondige controle. Zet geen onomkeerbare stappen (compromesso) voordat een geometra en notaris alles hebben nagelopen.'
 *  AANDACHTSPUNTEN = aantal vragen met nee of weet
 *  RISICO_NIVEAU   = de tekst 'Laag risico', 'Verhoogd risico' of 'Hoog risico'
 *  BRON            = 'conformiteitscheck'
 *  Meta:  trackCustom 'ConformiteitsCheckVoltooid' {score, niveau}; track 'Lead' na aanmelding
 *  GA4:   event 'check_voltooid' {score, niveau}; event 'generate_lead' {value: score}
 */
