<?php
declare(strict_types=1);
/* GET gemeente.php?istat=018110  -> alle gegevens van één gemeente (waarden, deelscores, tests) */
require __DIR__ . '/lib/data.php';
@ini_set('memory_limit', '256M');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
$g = gemeente((string)($_GET['istat'] ?? ''));
if (!$g) {
    http_response_code(404);
    echo json_encode(['fout' => 'onbekend']);
    exit;
}
echo json_encode($g, JSON_UNESCAPED_UNICODE);
