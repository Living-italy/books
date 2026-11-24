<?php
// php web/tests/test_model.php   (gebruikt web/data/score.json van de laatste pipeline-run)
declare(strict_types=1);
require __DIR__ . '/../lib/data.php';
require __DIR__ . '/../lib/model.php';
ini_set('memory_limit', '256M');

$fout = 0;
function check(bool $ok, string $wat): void
{
    global $fout;
    echo ($ok ? "OK    " : "FOUT  ") . $wat . "\n";
    $fout += $ok ? 0 : 1;
}

foreach (cfg('profielen')['profielen'] as $p) {
    check(array_sum($p['gewichten']) === 100, "gewichten van profiel {$p['id']} tellen op tot 100");
}
check(array_sum(startgewichten('gepensioneerde', 'verdelen')) === 100, 'gepensioneerde, het jaar verdelen: som 100');
check(startgewichten('gepensioneerde', 'verdelen')['bereikbaarheid'] === 18, 'verdelen: bereikbaarheid 18');

$c = comuni()['comuni'];
$r = scoor(['profiel' => 'gepensioneerde'], $c, 100000);
$max = max(array_map(fn($x) => $x['istat'], $r['top']) ? array_map(function ($x) use ($c) {
    foreach ($c as $k) { if ($k['istat'] === $x['istat']) { return $k['w']['min_ziekenhuis'] ?? 0; } }
    return 0;
}, $r['top']) : [0]);
check($max <= 45, "gepensioneerde: geen resultaat met meer dan 45 min naar spoedeisende hulp (max $max)");

$ercolano = array_values(array_filter($c, fn($k) => $k['naam'] === 'Ercolano'))[0]['istat'];
$ids = array_column(scoor(['profiel' => 'tweede_huis'], $c, 100000)['top'], 'istat');
check(!in_array($ercolano, $ids, true), 'Ercolano (rode zone Vesuvius) valt standaard weg');
$ids = array_column(scoor(['profiel' => 'tweede_huis', 'filters' => ['rode_zone_uit' => false]], $c, 100000)['top'], 'istat');
check(in_array($ercolano, $ids, true), 'Ercolano komt terug als het filter uit staat');

check(totaal(['a' => 80, 'b' => null], ['a' => 50, 'b' => 50]) === 80.0, 'ontbrekende data: gewicht gaat naar de andere criteria');

$alle = scoor(['profiel' => 'gezin'], $c, 100000)['top'];
$doel = $alle[200];
$r = scoor(['profiel' => 'gezin', 'alternatieven_voor' => $doel['istat']], $c, 1);
check(count($r['alternatieven']) === 3, "3 alternatieven voor {$doel['naam']}");
foreach ($r['alternatieven'] as $alt) {
    check($alt['score'] >= $doel['score'], "alternatief {$alt['naam']} ({$alt['score']}) scoort minstens zo hoog als {$doel['naam']} ({$doel['score']})");
}

echo $fout ? "\n$fout test(s) mislukt\n" : "\nalle tests geslaagd\n";
exit($fout ? 1 : 0);
