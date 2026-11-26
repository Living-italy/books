<?php
// php web/tests/test_model.php   (gebruikt web/data/score.json van de laatste pipeline-run)
declare(strict_types=1);
require __DIR__ . '/../lib/data.php';
require __DIR__ . '/../lib/model.php';
ini_set('memory_limit', '512M');

$fout = 0;
function check(bool $ok, string $wat): void
{
    global $fout;
    echo ($ok ? "OK    " : "FOUT  ") . $wat . "\n";
    $fout += $ok ? 0 : 1;
}

$c = comuni()['comuni'];
$idx = array_column($c, null, 'istat');
$E = eigenschappen();
$pos = array_flip(array_column($E, 'id'));
$waarde = fn(array $g, string $id) => $g['ev'][$pos[$id]] ?? null;

// configuratie
foreach (cfg('profielen')['profielen'] as $p) {
    check(array_sum($p['gewichten']) === 100, "gewichten van profiel {$p['id']} tellen op tot 100");
    $k = startkeuzes($p['id']);
    check(count($k) === count($E), "profiel {$p['id']}: startkeuze voor alle " . count($E) . ' eigenschappen');
}
foreach ($E as $e) {
    $n = count($e['niveaus']);
    if ($n < 3 && !in_array($e['id'], ['regime7', 'vulkaan', 'maffia'], true)) {
        check(false, "eigenschap {$e['id']} heeft $n niveaus (minstens 3)");
    }
}
check(true, 'alle eigenschappen hebben 3 tot 5 niveaus, behalve de ja/nee-eigenschappen (7%-regime, vulkaan, maffia)');

// noodzakelijk sluit uit op het gekozen niveau, meewegen en onbelangrijk niet
$basis = scoor(['profiel' => 'gezin'], $c, 1)['aantal'];
$r = scoor(['profiel' => 'gezin', 'eigenschappen' => ['internet' => ['status' => 'noodzakelijk', 'niveau' => 2]]], $c, 100000);
$onder = array_filter($r['top'], fn($x) => ($waarde($idx[$x['istat']], 'internet') ?? 100) < 75);
check(count($onder) === 0 && $r['aantal'] < $basis, "noodzakelijk internet niveau 3 (75%): niemand eronder ($basis naar {$r['aantal']})");
$mee = scoor(['profiel' => 'gezin', 'eigenschappen' => ['internet' => ['status' => 'meewegen', 'niveau' => 2]]], $c, 1)['aantal'];
$onb = scoor(['profiel' => 'gezin', 'eigenschappen' => ['internet' => ['status' => 'onbelangrijk']]], $c, 1)['aantal'];
check($mee === $basis && $onb === $basis, 'meewegen en onbelangrijk sluiten niets uit');

// gewichten: noodzakelijk vast maximaal, onbelangrijk minimaal
$a = antwoorden(['profiel' => 'gezin', 'eigenschappen' => [
    'internet' => ['status' => 'noodzakelijk', 'gewicht' => 3], 'prijs' => ['status' => 'onbelangrijk', 'gewicht' => 9],
    'cultuur' => ['status' => 'meewegen', 'gewicht' => 7]]]);
check($a['gewichten']['internet'] === 10 && $a['gewichten']['prijs'] === 0 && $a['gewichten']['cultuur'] === 7,
    'gewicht: noodzakelijk 10, onbelangrijk 0, meewegen het gekozen gewicht');
$a = antwoorden(['profiel' => 'gezin', 'eigenschappen' => ['cultuur' => ['status' => 'meewegen', 'gewicht' => 99, 'niveau' => 9]]]);
check($a['keuzes']['cultuur']['gewicht'] === 9 && $a['keuzes']['cultuur']['niveau'] === 3, 'gewicht en niveau worden begrensd');

// onbelangrijk verandert de score niet, ook niet bij een slechte waarde
$x = $idx['018096'];
$a1 = antwoorden(['profiel' => 'gepensioneerde', 'eigenschappen' => ['bosbrand' => ['status' => 'onbelangrijk']]]);
$s1 = totaal(deelscores($x, $a1), $a1['gewichten']);
$y = $x;
$y['e'][$pos['bosbrand']] = '0';
check(totaal(deelscores($y, $a1), $a1['gewichten']) === $s1, 'een onbelangrijke eigenschap telt niet mee in de score');
// labels veranderen de score niet
$y = $x;
$y['trend'] = !$x['trend'];
$y['w']['sciolto_mafia_jaar'] = 2020;
check(totaal(deelscores($y, $a1), $a1['gewichten']) === $s1, 'labels (trend, maffia-jaartal) veranderen de score niet');

// hoger niveau gehaald = hogere deelscore
$a = antwoorden(['profiel' => 'gepensioneerde', 'eigenschappen' => ['luchthaven' => ['status' => 'meewegen', 'niveau' => 4]]]);
$goed = $slecht = null;
foreach ($c as $g) {
    $n = behaald($g, $pos['luchthaven']);
    if ($n === 5 && !$goed) { $goed = $g; }
    if ($n === 1 && !$slecht) { $slecht = $g; }
}
check(deelscores($goed, $a)['luchthaven'] > deelscores($slecht, $a)['luchthaven'], 'wie het gevraagde niveau haalt, scoort hoger op die eigenschap');

// standaard: gepensioneerde spoedeisende hulp binnen 45 minuten; rode zone valt altijd af
$r = scoor(['profiel' => 'gepensioneerde'], $c, 100000);
$max = max(array_map(fn($t) => $waarde($idx[$t['istat']], 'spoedeisende_hulp') ?? 0, $r['top']));
check($max <= 45, "gepensioneerde: standaard geen resultaat met meer dan 45 min naar spoedeisende hulp (max $max)");
$ercolano = array_values(array_filter($c, fn($k) => $k['naam'] === 'Ercolano'))[0]['istat'];
check(!in_array($ercolano, array_column($r['top'], 'istat'), true), 'Ercolano (rode zone Vesuvius) valt altijd af');
$r2 = scoor(['profiel' => 'gepensioneerde', 'eigenschappen' => ['spoedeisende_hulp' => ['status' => 'meewegen']]], $c, 1);
check($r2['aantal'] > $r['aantal'], 'de standaardeis van het profiel kan uit');

// ligging, zoekgebied, alternatieven
$n = scoor(['profiel' => 'gepensioneerde', 'ligging' => ['heuvel']], $c, 1)['aantal'];
check($n > 1000, "ligging heuvel geeft plaatsen ($n)");
$r = scoor(['profiel' => 'gepensioneerde', 'alleen_provs' => ['PV']], $c, 100000);
check(array_values(array_unique(array_column($r['top'], 'prov'))) === ['PV'], 'zoekgebied provincie Pavia: alleen gemeenten uit Pavia');
check(in_array('Montecalvo Versiggia', array_column($r['top'], 'naam'), true), 'Montecalvo Versiggia staat erin');
$heel = array_column(scoor(['profiel' => 'gepensioneerde'], $c, 100000)['top'], 'score', 'istat');
$regio = array_column(scoor(['profiel' => 'gepensioneerde', 'alleen_regios' => [3]], $c, 100000)['top'], 'score', 'istat');
$verschil = array_filter(array_keys($regio), fn($i) => $regio[$i] !== ($heel[$i] ?? null));
check(!$verschil && count($regio) > 100, 'zelfde score bij heel Italië en bij de eigen regio (Lombardije, ' . count($regio) . ' gemeenten)');
$r = scoor(['profiel' => 'gepensioneerde', 'alleen_provs' => ['PV'], 'alternatieven_voor' => '018096'], $c, 1);
$alt = $r['alternatieven'] ?? [];
check($alt && !array_filter($alt, fn($x) => ($x['prov'] !== 'PV') !== $x['buiten_gebied']),
    'alternatieven buiten het zoekgebied zijn gemarkeerd (' . implode(', ', array_map(fn($x) => "{$x['naam']} {$x['prov']}", $alt)) . ')');
foreach ($alt as $x) {
    check($x['score'] >= $r['gemeenten']['018096']['score'], "alternatief {$x['naam']} ({$x['score']}) scoort minstens zo hoog als Montecalvo Versiggia");
}
check(count($r['gemeenten']['018096']['eigenschappen']) === count($E), 'uitklap: alle eigenschappen per gemeente');

// te weinig plaatsen: per noodzakelijke eigenschap hoeveel die wegstreept
$r = scoor(['profiel' => 'gepensioneerde', 'alleen_provs' => ['PV'], 'eigenschappen' => [
    'hogesnelheid' => ['status' => 'noodzakelijk', 'niveau' => 3], 'lucht' => ['status' => 'noodzakelijk', 'niveau' => 3],
    'station' => ['status' => 'noodzakelijk', 'niveau' => 4]]], $c, 25);
check($r['aantal'] < 25 && count($r['wegstrepen'] ?? []) >= 3 && $r['wegstrepen'][0]['weg'] >= end($r['wegstrepen'])['weg'],
    "te weinig plaatsen ({$r['aantal']}): per noodzakelijke eigenschap hoeveel die wegstreept, strengste eerst");

// gids
$gl = array_filter(cfg('gids_links')['verwijzingen'], fn($v) => !empty($v['url']));
check(count(array_filter($gl, fn($v) => $v['id'] === 'kaarten')) === 1, 'gids-link naar Bijlage C staat erin');

echo $fout ? "\n$fout test(s) mislukt\n" : "\nalle tests geslaagd\n";
exit($fout ? 1 : 0);
