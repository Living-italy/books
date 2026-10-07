<?php
/**
 * Koopdossier Italië: aanmelding na de conformiteitscheck doorgeven aan Brevo.
 *
 * Alleen hier staan de API-sleutel en het lijst-ID. Ze komen nooit in index.html.
 * De app stuurt alleen: e-mailadres, VOORNAAM, RISICO_SCORE, RISICO_NIVEAU,
 * AANDACHTSPUNTEN en BRON. Antwoorden en dossiergegevens gaan nooit naar de server.
 *
 * Fase 3 voegt hier de toegangscode toe (veld "actie").
 */
declare(strict_types=1);

// ---- INSTELLINGEN ---------------------------------------------------------
const BREVO_API_KEY = '';          // Brevo > SMTP & API > API Keys
const BREVO_LIST_ID = 0;           // ID van de lijst van de bestaande check
const ALLOWED_ORIGINS = [];        // leeg = alleen hetzelfde domein, bijv. ['https://www.example.nl']
// ---------------------------------------------------------------------------

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function reply(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    reply(405, ['ok' => false, 'error' => 'method']);
}

// Alleen verzoeken vanaf de eigen site.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
    if ($originHost !== $host && !in_array($origin, ALLOWED_ORIGINS, true)) {
        reply(403, ['ok' => false, 'error' => 'origin']);
    }
}

$raw = file_get_contents('php://input', false, null, 0, 4096);
$in = json_decode((string) $raw, true);
if (!is_array($in)) {
    reply(400, ['ok' => false, 'error' => 'json']);
}

$actie = $in['actie'] ?? 'aanmelden';
if ($actie !== 'aanmelden') {
    reply(400, ['ok' => false, 'error' => 'actie']);
}

// Honeypot: bots vullen dit verborgen veld in. Doe alsof het gelukt is.
if (!empty($in['website'])) {
    reply(200, ['ok' => true]);
}

$email = trim((string) ($in['email'] ?? ''));
if ($email === '' || strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    reply(422, ['ok' => false, 'error' => 'email']);
}

$voornaam = trim(strip_tags((string) ($in['VOORNAAM'] ?? '')));
$voornaam = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', $voornaam) ?? '', 0, 60);

$score = filter_var($in['RISICO_SCORE'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
$niveau = (string) ($in['RISICO_NIVEAU'] ?? '');
$punten = filter_var($in['AANDACHTSPUNTEN'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
$bron = (string) ($in['BRON'] ?? '');

// RISICO_NIVEAU is de tekst zoals in de oude check: 'Laag risico', 'Verhoogd risico' of 'Hoog risico'.
if ($score === false || !in_array($niveau, ['Laag risico', 'Verhoogd risico', 'Hoog risico'], true) || $punten === false || !preg_match('/^[a-z0-9-]{1,40}$/', $bron)) {
    reply(422, ['ok' => false, 'error' => 'velden']);
}

if (BREVO_API_KEY === '' || BREVO_LIST_ID === 0) {
    reply(503, ['ok' => false, 'error' => 'niet-ingesteld']);
}

$payload = [
    'email' => $email,
    'attributes' => [
        'VOORNAAM' => $voornaam,
        'RISICO_SCORE' => $score,
        'RISICO_NIVEAU' => $niveau,
        'AANDACHTSPUNTEN' => $punten,
        'BRON' => $bron,
    ],
    'listIds' => [BREVO_LIST_ID],
    'updateEnabled' => true,
];

$ch = curl_init('https://api.brevo.com/v3/contacts');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'accept: application/json',
        'content-type: application/json',
        'api-key: ' . BREVO_API_KEY,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);
$response = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 201 = nieuw contact, 204 = bestaand contact bijgewerkt.
if ($response !== false && ($status === 201 || $status === 204)) {
    reply(200, ['ok' => true]);
}

error_log('Koopdossier Brevo-fout ' . $status . ': ' . substr((string) $response, 0, 300));
reply(502, ['ok' => false, 'error' => 'brevo']);
