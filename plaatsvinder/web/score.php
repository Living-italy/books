<?php
declare(strict_types=1);
/*
 * POST score.php  (JSON-body met de antwoorden uit de wizard; optioneel "alternatieven_voor": istat)
 * Antwoord: {"aantal": n, "gewichten": {...}, "top": [...], "alternatieven": [...]}
 * Met "stat": sessiecode (alleen bij de eerste pagina van een uitslag) worden de keuzes anoniem vastgelegd.
 */
require __DIR__ . '/lib/data.php';
require __DIR__ . '/lib/model.php';
require __DIR__ . '/lib/statistiek.php';
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
    $res = scoor($invoer, comuni()['comuni'], $n);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    $sessie = stat_sessie($invoer['stat'] ?? null);
    if ($sessie && empty($invoer['van']) && empty($invoer['kaart']) && empty($invoer['alternatieven_voor'])) {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        stat_schrijf(['s' => $sessie, 'k' => 'uitslag'] + stat_keuzes(antwoorden($invoer), $invoer)
            + ['n' => $res['aantal'], 'top' => array_slice(array_column($res['top'], 'istat'), 0, 10)]);
    }
} catch (Throwable $e) {
    error_log('plaatsvinder score.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['fout' => 'Serverfout']);
}
