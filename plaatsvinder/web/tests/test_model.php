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

$n = scoor(['profiel' => 'gepensioneerde', 'ligging' => ['heuvel']], $c, 1)['aantal'];
check($n > 1000, "ligging heuvel + gepensioneerde geeft plaatsen ($n)");
check(totaal(['a' => 80, 'b' => null], ['a' => 50, 'b' => 50]) === 80.0, 'ontbrekende data: gewicht gaat naar de andere criteria');

$alle = scoor(['profiel' => 'gezin'], $c, 100000)['top'];
$doel = $alle[200];
$r = scoor(['profiel' => 'gezin', 'alternatieven_voor' => $doel['istat']], $c, 1);
check(count($r['alternatieven']) === 3, "3 alternatieven voor {$doel['naam']}");
foreach ($r['alternatieven'] as $alt) {
    check($alt['score'] >= $doel['score'], "alternatief {$alt['naam']} ({$alt['score']}) scoort minstens zo hoog als {$doel['naam']} ({$doel['score']})");
}

// sectie 12e
$r = scoor(['profiel' => 'gezin', 'wensen' => ['dealbreakers' => ['internet']]], $c, 100000);
$onder = array_filter($r['top'], function ($x) use ($c) {
    static $idx = null;
    $idx ??= array_column($c, null, 'istat');
    $gv = $idx[$x['istat']]['w']['glasvezel_pct'] ?? null;
    return $gv !== null && $gv < 50;
});
check(count($onder) === 0, "dealbreaker internet: geen gemeente onder 50% glasvezel ({$r['aantal']} over)");
$zonder = scoor(['profiel' => 'gezin'], $c, 1)['aantal'];
check($r['aantal'] < $zonder, "dealbreaker internet haalt gemeenten weg ($zonder naar {$r['aantal']})");

foreach (cfg('profielen')['profielen'] as $p) {
    $a = antwoorden(['profiel' => $p['id'], 'wensen' => ['must' => ['prijs', 'zorg', 'natuur'], 'nice' => ['cultuur', 'rust', 'klimaat']]]);
    check(array_sum($a['gewichten']) === 100, "profiel {$p['id']}: gewichten na must/nice tellen op tot 100");
}
$basis = antwoorden(['profiel' => 'gepensioneerde'])['gewichten'];
$met = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['must' => ['prijs']]])['gewichten'];
check($met['prijs'] > $basis['prijs'], "must-have prijs weegt zwaarder ({$basis['prijs']} naar {$met['prijs']})");
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['must' => ['prijs', 'zorg', 'natuur', 'cultuur']]]);
check(array_sum($a['gewichten']) === 100, 'meer dan drie must-haves: alleen de eerste drie tellen, som 100');
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['dealbreakers' => ['internet']], 'filters' => ['min_glasvezel' => 75]]);
check($a['min_glasvezel'] == 75, 'een strengere eigen eis blijft staan naast de dealbreaker');

$gl = array_filter(cfg('gids_links')['verwijzingen'], fn($v) => !empty($v['url']));
check(count($gl) >= 1, 'gemeentepagina: minstens één gids-link met url (' . implode(', ', array_column($gl, 'paragraaf')) . ')');
$bijlageC = array_filter($gl, fn($v) => $v['id'] === 'kaarten');
check(count($bijlageC) === 1 && str_starts_with(array_values($bijlageC)[0]['url'], 'https://'), 'gids-link naar Bijlage C staat erin');

// labels (maffia, trend) veranderen de score niet
$x = $c[100];
$a = antwoorden(['profiel' => 'gepensioneerde']);
$s1 = totaal(deelscores($x, $a), $a['gewichten']);
$x['w']['sciolto_mafia_jaar'] = 2020;
$x['trend'] = !($x['trend'] ?? false);
check(totaal(deelscores($x, $a), $a['gewichten']) === $s1, 'labels (maffia, trend) veranderen de score niet');

$h = scoor(['profiel' => 'gepensioneerde', 'filters' => ['max_hoogte' => 500]], $c, 100000)['top'];
check(max(array_map(fn($k) => $k['hoogte_m'] ?? 0, $h)) <= 500, 'filter maximale hoogte 500 m werkt');
$z = antwoorden(['profiel' => 'gepensioneerde', 'klimaat' => 'seizoenen']);
$x = $c[100];
check(deelscores($x, $z)['klimaat'] === ($x['s']['klimaat_seizoenen'] ?? $x['s']['klimaat']) && !array_key_exists('klimaat_seizoenen', deelscores($x, $z)),
    'klimaatvoorkeur vier seizoenen gebruikt de omgedraaide klimaatscore');


// grenswaarde van een dealbreaker door de gebruiker
$r = scoor(['profiel' => 'gezin', 'wensen' => ['dealbreakers' => ['internet'], 'drempels' => ['internet' => 75]]], $c, 100000);
$idx = array_column($c, null, 'istat');
$onder = array_filter($r['top'], fn($x) => ($idx[$x['istat']]['w']['glasvezel_pct'] ?? 100) < 75);
check(count($onder) === 0, "eigen grenswaarde internet 75%: geen gemeente eronder ({$r['aantal']} over)");
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['dealbreakers' => ['prijs'], 'drempels' => ['prijs' => 2000]]]);
check($a['max_prijs_m2'] == 2000, 'eigen grenswaarde prijs 2.000 wordt gebruikt');
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['dealbreakers' => ['prijs'], 'drempels' => ['prijs' => 1234]]]);
check($a['max_prijs_m2'] == 1500, 'ongeldige grenswaarde valt terug op de standaard');
echo $fout ? "\n$fout test(s) mislukt\n" : "\nalle tests geslaagd\n";
exit($fout ? 1 : 0);
