<?php
declare(strict_types=1);
/* GET zoek.php?q=pav  -> max. 10 comuni voor het werkplekveld: [{istat, naam, prov, i}] (i = positie in de lite-tabel) */
require __DIR__ . '/lib/data.php';
@ini_set('memory_limit', '256M');   // heel Italië vraagt ca. 70 MB

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$norm = function (string $s): string {
    $s = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s);
    return trim((string)preg_replace('/[^a-z0-9]+/', ' ', $s));
};
$q = $norm(substr((string)($_GET['q'] ?? ''), 0, 60));
if (strlen($q) < 2) {
    echo '[]';
    exit;
}
$begin = $midden = [];
foreach (comuni()['comuni'] as $i => $c) {
    $n = $norm($c['naam'] . ' ' . ($c['naam_alt'] ?? ''));
    $pos = strpos($n, $q);
    if ($pos === 0) {
        $begin[] = ['istat' => $c['istat'], 'naam' => $c['naam'], 'prov' => $c['prov'], 'i' => $i, 'lat' => $c['lat'], 'lon' => $c['lon'], 'inw' => $c['inwoners']];
    } elseif ($pos !== false) {
        $midden[] = ['istat' => $c['istat'], 'naam' => $c['naam'], 'prov' => $c['prov'], 'i' => $i, 'lat' => $c['lat'], 'lon' => $c['lon'], 'inw' => $c['inwoners']];
    }
}
$sort = fn($a, $b) => ($b['inw'] ?? 0) <=> ($a['inw'] ?? 0);
usort($begin, $sort);
usort($midden, $sort);
echo json_encode(array_slice(array_merge($begin, $midden), 0, 10), JSON_UNESCAPED_UNICODE);
