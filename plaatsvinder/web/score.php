<?php
declare(strict_types=1);
/*
 * POST score.php  (JSON-body met de antwoorden uit de wizard; optioneel "alternatieven_voor": istat)
 * Antwoord: {"aantal": n, "gewichten": {...}, "top": [...], "alternatieven": [...]}
 */
require __DIR__ . '/lib/data.php';
require __DIR__ . '/lib/model.php';
@ini_set('memory_limit', '256M');   // heel Italië vraagt ca. 45 MB

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['fout' => 'Gebruik POST']);
    exit;
}
$invoer = json_decode((string)file_get_contents('php://input', false, null, 0, 20000), true);
if (!is_array($invoer)) {
    http_response_code(400);
    echo json_encode(['fout' => 'Ongeldige invoer']);
    exit;
}
try {
    $n = min(25, max(1, (int)($invoer['n'] ?? 10)));
    echo json_encode(scoor($invoer, comuni()['comuni'], $n), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('plaatsvinder score.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['fout' => 'Serverfout']);
}
