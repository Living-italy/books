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

// sectie 12e en 14: wensenlijst
$idx = array_column($c, null, 'istat');
$r = scoor(['profiel' => 'gezin', 'wensen' => ['must' => ['internet']]], $c, 100000);
$onder = array_filter($r['top'], fn($x) => ($idx[$x['istat']]['w']['glasvezel_pct'] ?? 100) < 80);
check(count($onder) === 0, "must-have internet: geen gemeente onder 80% glasvezel ({$r['aantal']} over)");
$zonder = scoor(['profiel' => 'gezin'], $c, 1)['aantal'];
check($r['aantal'] < $zonder, "een must-have sluit uit ($zonder naar {$r['aantal']})");
$nice = scoor(['profiel' => 'gezin', 'wensen' => ['nice' => ['internet']]], $c, 1)['aantal'];
check($nice === $zonder, "een nice-to-have sluit niets uit ($nice = $zonder)");
foreach (cfg('profielen')['profielen'] as $p) {
    $a = antwoorden(['profiel' => $p['id'], 'wensen' => ['nice' => ['luchthaven', 'natuur', 'n_klimaat']]]);
    check(array_sum($a['gewichten']) === 100 || ($a['expats'] === 'maakt_niet_uit' && array_sum($a['gewichten']) <= 100),
        "profiel {$p['id']}: gewichten na nice-to-haves tellen op tot 100 (" . array_sum($a['gewichten']) . ')');
}
$basis = antwoorden(['profiel' => 'gepensioneerde'])['gewichten'];
$met = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['nice' => ['n_klimaat']]])['gewichten'];
check($met['klimaat'] > $basis['klimaat'], "nice-to-have klimaat weegt zwaarder ({$basis['klimaat']} naar {$met['klimaat']})");
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['must' => ['luchthaven', 'internet', 'station', 'natuur']]]);
check(count($a['wensen']['must']) === 3, 'meer dan drie must-haves: alleen de eerste drie tellen');
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['dealbreakers' => ['luchthaven'], 'must' => ['aardbeving']]]);
check(!$a['wensen']['dealbreakers'] && !$a['wensen']['must'], 'een item kan alleen op zijn eigen lijst staan');
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['must' => ['natuur'], 'nice' => ['natuur']]]);
check($a['wensen']['must'] === ['natuur'] && !$a['wensen']['nice'], 'een item staat op precies één lijst');
// 14h: geen dealbreaker en must-have met hetzelfde onderwerp, tenzij ze elkaar blokkeren
$wl = cfg('wensenlijst');
$kolom = fn($i) => implode(',', array_column($i['regel']['of'] ?? [$i['regel']], 'kolom'));
$blok = [];
foreach ($wl['blokkades'] as $b) {
    $blok[$b['a'] . '|' . $b['b']] = $blok[$b['b'] . '|' . $b['a']] = true;
}
$dubbel = [];
foreach ($wl['items'] as $d) {
    foreach ($wl['items'] as $m) {
        if ($d['lijst'] === 'dealbreaker' && $m['lijst'] === 'musthave' && $kolom($d) === $kolom($m) && !isset($blok[$d['id'] . '|' . $m['id']])) {
            $dubbel[] = "{$d['id']}/{$m['id']}";
        }
    }
}
check(!$dubbel, 'geen dealbreaker en must-have met hetzelfde onderwerp (' . implode(', ', $dubbel) . ')');
check(count(array_unique(array_column($wl['items'], 'id'))) === count($wl['items']) && count($wl['items']) <= 30, 'items uniek en passen in het masker');
// 14e: geblokkeerde combinaties
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['dealbreakers' => ['expatbubbel'], 'must' => ['landgenoten']]]);
check(!in_array('landgenoten', $a['wensen']['must'], true), 'blokkade: veel buitenlanders en landgenoten niet tegelijk');
$a = antwoorden(['profiel' => 'gepensioneerde', 'wensen' => ['dealbreakers' => ['massatoerisme'], 'nice' => ['toeristische_vraag']]]);
check(!in_array('toeristische_vraag', $a['wensen']['nice'], true), 'blokkade: massatoerisme en toeristische vraag niet tegelijk');
$a = antwoorden(['profiel' => 'gepensioneerde', 'ligging' => ['berg', 'heuvel'], 'klimaat' => 'seizoenen', 'wensen' => ['dealbreakers' => ['strenge_winter']]]);
check($a['ligging'] === ['heuvel'] && $a['klimaat'] === 'zacht', 'blokkade: strenge winters haalt bergen en vier seizoenen weg');
// 14f: te weinig plaatsen
$r = scoor(['profiel' => 'gepensioneerde', 'alleen_provs' => ['PV'], 'wensen' => ['dealbreakers' => ['lucht', 'afgelegen'], 'must' => ['station']]], $c, 25);
check($r['aantal'] < 25 && count($r['wegstrepen'] ?? []) === 3 && $r['wegstrepen'][0]['weg'] >= $r['wegstrepen'][2]['weg'],
    "te weinig plaatsen ({$r['aantal']}): per eis hoeveel die wegstreept, strengste eerst");
// sectie 13: zoekgebied
$r = scoor(['profiel' => 'gepensioneerde', 'alleen_provs' => ['PV']], $c, 100000);
$provs = array_unique(array_column($r['top'], 'prov'));
check($provs === ['PV'] || array_values($provs) === ['PV'], 'zoekgebied provincie Pavia: alleen gemeenten uit Pavia');
check(in_array('Montecalvo Versiggia', array_column($r['top'], 'naam'), true), 'Montecalvo Versiggia staat erin');
$heel = array_column(scoor(['profiel' => 'gepensioneerde'], $c, 100000)['top'], 'score', 'istat');
$regio = array_column(scoor(['profiel' => 'gepensioneerde', 'alleen_regios' => [3]], $c, 100000)['top'], 'score', 'istat');
$verschil = array_filter(array_keys($regio), fn($i) => $regio[$i] !== ($heel[$i] ?? null));
check(!$verschil && count($regio) > 100, 'zelfde score bij heel Italië en bij de eigen regio (Lombardije, ' . count($regio) . ' gemeenten)');
$r = scoor(['profiel' => 'gepensioneerde', 'alleen_provs' => ['PV'], 'alternatieven_voor' => '018096'], $c, 1);
$alt = $r['alternatieven'] ?? [];
check($alt && !array_filter($alt, fn($x) => ($x['prov'] !== 'PV') !== $x['buiten_gebied']),
    'alternatieven buiten het zoekgebied zijn gemarkeerd (' . implode(', ', array_map(fn($x) => "{$x['naam']} {$x['prov']}", $alt)) . ')');

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


echo $fout ? "\n$fout test(s) mislukt\n" : "\nalle tests geslaagd\n";
exit($fout ? 1 : 0);
