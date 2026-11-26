<?php
declare(strict_types=1);

/*
 * Rekenmodel van de Plaatsvinder (config/eigenschappen.json, docs/plan-eigenschappen.md)
 *  - per eigenschap: noodzakelijk (harde eis op het gekozen niveau, gewicht 10), meewegen (eigen gewicht 1-9)
 *    of onbelangrijk (gewicht 0, geen niveau)
 *  - deelscore = 85 x (behaald niveau / gekozen niveau, hoogstens 1) + 15 x percentiel over heel Italië
 *  - totaal = gewogen gemiddelde; ontbrekende data telt niet mee en sluit niet uit
 *  - zoekgebied, ligging en grootte zijn filters; de rode zone van de Vesuvius en de Campi Flegrei valt altijd af
 * De teller in assets/app.js volgt dezelfde filterregels.
 */

const LIGGING = ['kust', 'meer', 'heuvel', 'berg', 'vlakte'];
const GROOTTE = [1000, 5000, 20000, 100000];
const STATUSSEN = ['noodzakelijk', 'meewegen', 'onbelangrijk'];

function cfg(string $naam): array
{
    static $c = [];
    return $c[$naam] ??= json_decode((string)file_get_contents(__DIR__ . "/../config/$naam.json"), true, 512, JSON_THROW_ON_ERROR);
}

function eigenschappen(): array
{
    return cfg('eigenschappen')['eigenschappen'];
}

function profiel(string $id): array
{
    foreach (cfg('profielen')['profielen'] as $p) {
        if ($p['id'] === $id) {
            return $p;
        }
    }
    return cfg('profielen')['profielen'][0];
}

/** Gewichten per criterium van een profiel, met het effect van de vervolgvraag (permanent of verdelen). */
function startgewichten(string $profiel, ?string $verblijf = null): array
{
    $p = profiel($profiel);
    $g = $p['gewichten'];
    foreach ($p['vragen'] ?? [] as $v) {
        foreach ($v['opties'] as $o) {
            if ($o['id'] === $verblijf && isset($o['effect'])) {
                $g = array_merge($g, $o['effect']);
            }
        }
    }
    return $g;
}

/** Startkeuzes per eigenschap voor een profiel (gelijk aan startkeuzes() in app.js). */
function startkeuzes(string $profiel, ?string $verblijf = null): array
{
    $g = startgewichten($profiel, $verblijf);
    $gw = cfg('eigenschappen')['gewicht'];
    $uitz = cfg('eigenschappen')['profielen'][$profiel] ?? [];
    $k = [];
    foreach (eigenschappen() as $e) {
        $w = $g[$e['criterium']] ?? 0;
        $k[$e['id']] = $w > 0
            ? ['status' => 'meewegen', 'niveau' => $e['standaard_niveau'], 'gewicht' => max($gw['min'], min($gw['max'], (int)round($w / 2)))]
            : ['status' => 'onbelangrijk', 'niveau' => $e['standaard_niveau'], 'gewicht' => $gw['standaard']];
        if (isset($uitz[$e['id']])) {
            $k[$e['id']] = array_merge($k[$e['id']], $uitz[$e['id']]);
        }
    }
    return $k;
}

/** Keuzes van de bezoeker controleren; ontbrekende eigenschappen krijgen de startkeuze van het profiel. */
function keuzes(array $in, string $profiel, ?string $verblijf): array
{
    $gw = cfg('eigenschappen')['gewicht'];
    $k = startkeuzes($profiel, $verblijf);
    foreach (eigenschappen() as $e) {
        $x = $in[$e['id']] ?? null;
        if (!is_array($x)) {
            continue;
        }
        if (in_array($x['status'] ?? '', STATUSSEN, true)) {
            $k[$e['id']]['status'] = $x['status'];
        }
        if (isset($x['niveau']) && is_numeric($x['niveau'])) {
            $k[$e['id']]['niveau'] = max(0, min(count($e['niveaus']) - 1, (int)$x['niveau']));
        }
        if (isset($x['gewicht']) && is_numeric($x['gewicht'])) {
            $k[$e['id']]['gewicht'] = max($gw['min'], min($gw['max'], (int)$x['gewicht']));
        }
    }
    return $k;
}

/** Het gewicht dat telt: noodzakelijk vast maximaal, onbelangrijk minimaal. */
function telgewicht(array $k): int
{
    $gw = cfg('eigenschappen')['gewicht'];
    return match ($k['status']) {
        'noodzakelijk' => $gw['noodzakelijk'],
        'onbelangrijk' => $gw['onbelangrijk'],
        default => $k['gewicht'],
    };
}

function antwoorden(array $in): array
{
    $lijst = fn($v, array $mag) => array_values(array_intersect(array_map('strval', (array)($v ?? [])), $mag));
    $profiel = in_array($in['profiel'] ?? '', array_column(cfg('profielen')['profielen'], 'id'), true) ? $in['profiel'] : 'gepensioneerde';
    $verblijf = in_array($in['verblijf'] ?? '', ['permanent', 'verdelen'], true) ? $in['verblijf'] : null;
    $k = keuzes((array)($in['eigenschappen'] ?? []), $profiel, $verblijf);
    return [
        'profiel' => $profiel, 'verblijf' => $verblijf, 'keuzes' => $k,
        'gewichten' => array_map('telgewicht', $k),
        'ligging' => $lijst($in['ligging'] ?? [], LIGGING),
        'grootte' => array_map('intval', $lijst($in['grootte'] ?? [], ['0', '1', '2', '3', '4'])),
        'alleen_regios' => array_map('intval', array_filter((array)($in['alleen_regios'] ?? []), 'is_numeric')),
        'alleen_provs' => array_values(array_filter(array_map(fn($p) => strtoupper((string)$p), (array)($in['alleen_provs'] ?? [])),
            fn($p) => preg_match('/^[A-Z]{2}$/', $p))),
    ];
}

function grootteklasse(?float $inw): ?int
{
    if ($inw === null) {
        return null;
    }
    $k = 0;
    foreach (GROOTTE as $grens) {
        $k += $inw >= $grens ? 1 : 0;
    }
    return $k;
}

/** Behaald niveau van eigenschap nummer $i (0..5), of null zonder data. */
function behaald(array $c, int $i): ?int
{
    $ch = $c['e'][$i] ?? '-';
    return $ch === '-' ? null : (int)$ch;
}

/** Filters: zoekgebied, ligging, grootte, rode zone en de noodzakelijke eigenschappen. */
function voldoet(array $c, array $a, bool $metGebied = true): bool
{
    if ($metGebied && $a['alleen_regios'] && !in_array((int)$c['regio_code'], $a['alleen_regios'], true)) {
        return false;
    }
    if ($metGebied && $a['alleen_provs'] && !in_array($c['prov'], $a['alleen_provs'], true)) {
        return false;
    }
    if ($a['ligging'] && !array_filter($a['ligging'], fn($l) => !empty($c[$l]))) {
        return false;
    }
    if ($a['grootte'] && !in_array(grootteklasse($c['inwoners'] ?? null), $a['grootte'], true)) {
        return false;
    }
    if (($c['w']['vulkaan_deel'] ?? null) === 'geheel') {
        return false;
    }
    foreach (eigenschappen() as $i => $e) {
        $k = $a['keuzes'][$e['id']];
        if ($k['status'] !== 'noodzakelijk') {
            continue;
        }
        $n = behaald($c, $i);
        if ($n !== null && $n < $k['niveau'] + 1) {
            return false;
        }
    }
    return true;
}

/** Deelscore per eigenschap (0-100), null bij onbelangrijk of zonder data. */
function deelscores(array $c, array $a): array
{
    $sc = cfg('eigenschappen')['score'];
    $d = [];
    foreach (eigenschappen() as $i => $e) {
        $k = $a['keuzes'][$e['id']];
        $n = behaald($c, $i);
        if ($k['status'] === 'onbelangrijk' || $n === null) {
            $d[$e['id']] = null;
            continue;
        }
        $fit = min(1, $n / ($k['niveau'] + 1));
        $d[$e['id']] = $sc['niveau'] * $fit + $sc['percentiel'] * (($c['ep'][$i] ?? 0) / 100);
    }
    return $d;
}

function totaal(array $deel, array $g): ?float
{
    $som = 0.0;
    $gew = 0.0;
    foreach ($g as $id => $w) {
        if ($w > 0 && ($deel[$id] ?? null) !== null) {
            $som += $w * $deel[$id];
            $gew += $w;
        }
    }
    return $gew > 0 ? $som / $gew : null;
}

function kandidaten(array $comuni, array $a, bool $metGebied = true): array
{
    $uit = [];
    foreach ($comuni as $c) {
        if (!voldoet($c, $a, $metGebied)) {
            continue;
        }
        $deel = deelscores($c, $a);
        $tot = totaal($deel, $a['gewichten']);
        if ($tot !== null) {
            $uit[] = ['score' => $tot, 'c' => $c, 'deel' => $deel];
        }
    }
    usort($uit, fn($x, $y) => $y['score'] <=> $x['score'] ?: strcmp($x['c']['naam'], $y['c']['naam']));
    return $uit;
}

/** Drie vergelijkbare plaatsen met een minstens zo hoge score: zelfde grootteklasse, meest gelijkend profiel. */
function alternatieven(array $kand, array $doel, int $n = 3): array
{
    $gk = grootteklasse($doel['c']['inwoners'] ?? null);
    $afst = [];
    foreach ($kand as $k) {
        if ($k['c']['istat'] === $doel['c']['istat'] || $k['score'] < ($doel['score'] ?? 0) || grootteklasse($k['c']['inwoners'] ?? null) !== $gk) {
            continue;
        }
        $d = 0.0;
        $m = 0;
        foreach ($doel['deel'] as $id => $v) {
            if ($v !== null && ($k['deel'][$id] ?? null) !== null) {
                $d += ($v - $k['deel'][$id]) ** 2;
                $m++;
            }
        }
        if ($m) {
            $afst[] = [sqrt($d / $m), $k];
        }
    }
    usort($afst, fn($x, $y) => $x[0] <=> $y[0]);
    return array_map(fn($x) => $x[1], array_slice($afst, 0, $n));
}

/** Bij te weinig plaatsen: per noodzakelijke eigenschap hoeveel gemeenten die op zichzelf wegstreept. */
function wat_streept_weg(array $comuni, array $a): array
{
    $zonder = $a;
    foreach ($zonder['keuzes'] as $id => $k) {
        if ($k['status'] === 'noodzakelijk') {
            $zonder['keuzes'][$id]['status'] = 'meewegen';
        }
    }
    $tel = function (array $x) use ($comuni): int {
        $n = 0;
        foreach ($comuni as $c) {
            $n += voldoet($c, $x) ? 1 : 0;
        }
        return $n;
    };
    $alle = $tel($zonder);
    $uit = [];
    foreach ($a['keuzes'] as $id => $k) {
        if ($k['status'] !== 'noodzakelijk') {
            continue;
        }
        $een = $zonder;
        $een['keuzes'][$id]['status'] = 'noodzakelijk';
        $uit[] = ['id' => $id, 'weg' => $alle - $tel($een), 'van' => $alle];
    }
    usort($uit, fn($x, $y) => $y['weg'] <=> $x['weg']);
    return $uit;
}

/** Samenvatting voor de ranglijst, met alle eigenschappen voor de uitklap. */
function samenvatting(array $k, array $a): array
{
    $c = $k['c'];
    $eig = [];
    foreach (eigenschappen() as $i => $e) {
        $kz = $a['keuzes'][$e['id']];
        $eig[] = ['id' => $e['id'], 'waarde' => $c['ev'][$i] ?? null, 'behaald' => behaald($c, $i),
                  'status' => $kz['status'], 'gevraagd' => $kz['status'] === 'onbelangrijk' ? null : $kz['niveau'] + 1,
                  'deel' => $k['deel'][$e['id']] === null ? null : (int)round($k['deel'][$e['id']])];
    }
    $met = array_filter(array_column($eig, 'deel', 'id'), fn($v, $id) => $v !== null && $a['gewichten'][$id] > 0, ARRAY_FILTER_USE_BOTH);
    arsort($met);
    return [
        'istat' => $c['istat'], 'naam' => $c['naam'], 'prov' => $c['prov'], 'regio' => $c['regio'],
        'inwoners' => $c['inwoners'], 'hoogte_m' => $c['hoogte_m'], 'trend' => $c['trend'],
        'score' => $k['score'] === null ? null : (int)round($k['score']),
        'sterk' => array_slice(array_keys($met), 0, 3), 'zwak' => array_slice(array_reverse(array_keys($met)), 0, 3),
        'winter' => $c['tw'], 'tachtig' => $c['tt'], 'eigenschappen' => $eig,
        'buiten_gebied' => $k['buiten'] ?? false, 'voldoet' => $k['voldoet'] ?? true,
    ];
}

function los(array $c, array $a): array
{
    $deel = deelscores($c, $a);
    return ['score' => totaal($deel, $a['gewichten']), 'c' => $c, 'deel' => $deel, 'voldoet' => voldoet($c, $a)];
}

/** Hoofdfunctie: antwoorden in, top N uit; optioneel alternatieven voor één gemeente en losse gemeenten. */
function scoor(array $invoer, array $comuni, int $n = 25): array
{
    $a = antwoorden($invoer);
    $kand = kandidaten($comuni, $a);
    $uit = ['aantal' => count($kand), 'gewichten' => $a['gewichten'],
            'top' => array_map(fn($k) => samenvatting($k, $a), array_slice($kand, 0, $n))];
    if (count($kand) < cfg('eigenschappen')['min_resultaten']
        && array_filter($a['keuzes'], fn($k) => $k['status'] === 'noodzakelijk')) {
        $uit['wegstrepen'] = wat_streept_weg($comuni, $a);
    }
    if (!empty($invoer['kaart'])) {
        // scores van alle gemeenten die door de keuzes komen, voor de kaart (istat => score)
        $uit['kaart'] = [];
        foreach ($kand as $k) {
            $uit['kaart'][$k['c']['istat']] = (int)round($k['score']);
        }
    }
    $gevraagd = array_values(array_filter(array_map('strval', (array)($invoer['gemeenten'] ?? [])), fn($i) => preg_match('/^\d{6}$/', $i)));
    $voor = preg_match('/^\d{6}$/', (string)($invoer['alternatieven_voor'] ?? '')) ? (string)$invoer['alternatieven_voor'] : null;
    if ($voor) {
        $gevraagd[] = $voor;
    }
    $idx = $gevraagd ? array_column($comuni, null, 'istat') : [];
    if ($gevraagd) {
        $uit['gemeenten'] = [];
        foreach (array_slice(array_values(array_unique($gevraagd)), 0, 4) as $istat) {
            if (isset($idx[$istat])) {
                $uit['gemeenten'][$istat] = samenvatting(los($idx[$istat], $a), $a);
            }
        }
    }
    if ($voor && isset($idx[$voor])) {
        // alternatieven mogen buiten het zoekgebied liggen; die krijgen een markering
        $doel = los($idx[$voor], $a);
        $breed = ($a['alleen_regios'] || $a['alleen_provs']) ? kandidaten($comuni, $a, false) : $kand;
        $binnen = array_flip(array_map(fn($k) => $k['c']['istat'], $kand));
        $alt = alternatieven($breed, $doel);
        foreach ($alt as &$k) {
            $k['buiten'] = !isset($binnen[$k['c']['istat']]);
        }
        unset($k);
        $uit['alternatieven'] = array_map(fn($k) => samenvatting($k, $a), $alt);
    }
    return $uit;
}
