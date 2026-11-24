<?php
declare(strict_types=1);
/*
 * POST score.php  (JSON-body met de antwoorden uit de wizard)
 * Antwoord: {"aantal": n, "top": [ {istat, naam, score, sterk[3], zwak[3], ...}, ... ]}
 */
require __DIR__ . '/lib/data.php';
@ini_set('memory_limit', '256M');   // heel Italië vraagt ca. 70 MB
require __DIR__ . '/lib/model.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['fout' => 'Gebruik POST']);
    exit;
}
$body = file_get_contents('php://input', false, null, 0, 20000);
$invoer = json_decode((string)$body, true);
if (!is_array($invoer)) {
    http_response_code(400);
    echo json_encode(['fout' => 'Ongeldige invoer']);
    exit;
}
try {
    $t = taal((string)($invoer['lang'] ?? 'nl'));
    $data = comuni();
    $res = scoor($invoer, $data['comuni'], $t);
    unset($res['gewichten']);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('plaatsvinder score.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['fout' => 'Serverfout']);
}
