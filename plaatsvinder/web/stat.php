<?php
declare(strict_types=1);
/*
 * POST stat.php  (navigator.sendBeacon vanuit assets/app.js)
 * {"s": sessiecode, "k": soort, ...}: anonieme gebeurtenis voor de statistiek (zie lib/statistiek.php).
 * Uitslagen met alle keuzes legt score.php zelf vast.
 */
require __DIR__ . '/lib/statistiek.php';

http_response_code(204);
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}
$in = json_decode((string)file_get_contents('php://input', false, null, 0, 2000), true);
if (!is_array($in) || !($s = stat_sessie($in['s'] ?? null)) || !in_array($in['k'] ?? '', STAT_SOORTEN, true) || $in['k'] === 'uitslag') {
    exit;
}
$r = ['s' => $s, 'k' => $in['k']];
switch ($in['k']) {
    case 'start':
    case 'gedeeld':
        $r['a'] = in_array($in['a'] ?? '', ['mobiel', 'tablet', 'computer'], true) ? $in['a'] : null;
        $b = is_string($in['b'] ?? null) ? strtolower($in['b']) : '';
        $r['b'] = preg_match('/^[a-z0-9.-]{1,80}$/', $b) ? $b : null;   // alleen de domeinnaam van de verwijzende site
        break;
    case 'stap':
        $r['n'] = max(0, min(10, (int)($in['n'] ?? 0)));
        break;
    case 'meer':
        $r['n'] = max(0, min(10000, (int)($in['n'] ?? 0)));
        break;
    case 'gemeente':
        if (!preg_match('/^\d{6}$/', (string)($in['i'] ?? ''))) {
            exit;
        }
        $r['i'] = (string)$in['i'];
        break;
}
stat_schrijf($r);
