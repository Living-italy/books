<?php
declare(strict_types=1);

/*
 * Scoremodel (opdracht criteria-onderzoek, sectie 5 en 6)
 *  5a  harde filters: een gemeente valt af (ja/nee)
 *  5b  gewogen score 0-100 uit de deelscores per criterium (percentielen, berekend in de pipeline);
 *      ontbrekende data: het gewicht gaat naar de andere criteria en het criterium toont "geen data"
 *  6   profielen met startgewichten (som 100), aan te passen met schuifjes
 * De filters in assets/app.js volgen dezelfde regels voor de teller.
 */

const LIGGING = ['kust', 'meer', 'heuvel', 'berg', 'vlakte'];
const GROOTTE = [1000, 5000, 20000, 100000];
const STEDEN = ['utrecht' => 'uur_utrecht', 'brussel' => 'uur_brussel', 'keulen' => 'uur_keulen', 'munchen' => 'uur_munchen'];

function km(float $la1, float $lo1, float $la2, float $lo2): float
{
    $dla = deg2rad($la2 - $la1);
    $dlo = deg2rad($lo2 - $lo1);
    $a = sin($dla / 2) ** 2 + cos(deg2rad($la1)) * cos(deg2rad($la2)) * sin($dlo / 2) ** 2;
    return 2 * 6371 * asin(min(1.0, sqrt($a)));
}

function cfg(string $naam): array
{
    static $c = [];
    return $c[$naam] ??= json_decode((string)file_get_contents(__DIR__ . "/../config/$naam.json"), true, 512, JSON_THROW_ON_ERROR);
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

/** Startgewichten van een profiel, met het effect van de extra vraag (permanent of verdelen). */
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

/** Gewichten naar een som van precies 100 (grootste-rest-methode). */
function normaliseer(array $g): array
{
    $som = array_sum($g);
    if ($som <= 0) {
        return $g;
    }
    $exact = array_map(fn($v) => $v * 100 / $som, $g);
    $uit = array_map('floor', $exact);
    $rest = $exact;
    foreach ($rest as $k => $v) {
        $rest[$k] = $v - $uit[$k];
    }
    arsort($rest);
    foreach (array_slice(array_keys($rest), 0, 100 - (int)array_sum($uit)) as $k) {
        $uit[$k]++;
    }
    return array_map('intval', $uit);
}

/** Wensenlijst (gids 1.3): must-haves x1,5, nice-to-haves x1,2, daarna weer samen 100. */
function pas_wensen_toe(array $g, array $must, array $nice): array
{
    $w = cfg('criteria')['wensenlijst'];
    foreach ($must as $c) {
        if (isset($g[$c])) {
            $g[$c] *= $w['factor_must'];
        }
    }
    foreach ($nice as $c) {
        if (isset($g[$c])) {
            $g[$c] *= $w['factor_nice'];
        }
    }
    return normaliseer($g);
}

/** Grenswaarde van een dealbreaker: de keuze van de gebruiker als die geldig is, anders de standaard. */
function drempel(array $db, $gekozen)
{
    if (is_bool($db['waarde'])) {
        return $db['waarde'];
    }
    $opties = $db['opties'] ?? [];
    if (is_numeric($gekozen) && $gekozen > 0 && (!$opties || in_array((float)$gekozen, array_map('floatval', $opties), true))) {
        return (float)$gekozen;
    }
    return $db['waarde'];
}

/** Zet een filter op de strengste van de eigen waarde en de dealbreaker-waarde. */
function streng(array $f, string $k, $waarde): array
{
    $eigen = $f[$k] ?? null;
    if (is_bool($waarde)) {
        $f[$k] = $waarde || (bool)$eigen;
    } elseif (!is_numeric($eigen) || $eigen <= 0) {
        $f[$k] = $waarde;
    } else {
        $f[$k] = str_starts_with($k, 'min_') ? max($eigen, $waarde) : min($eigen, $waarde);
    }
    return $f;
}

/** Controleert en normaliseert de antwoorden. */
function antwoorden(array $in): array
{
    $lijst = fn($v, array $mag) => array_values(array_intersect(array_map('strval', (array)($v ?? [])), $mag));
    $num = fn($v) => is_numeric($v) && $v > 0 ? (float)$v : null;
    $crit = array_column(cfg('criteria')['criteria'], 'id');
    $profiel = in_array($in['profiel'] ?? '', array_column(cfg('profielen')['profielen'], 'id'), true)
        ? $in['profiel'] : 'gepensioneerde';
    $verblijf = in_array($in['verblijf'] ?? '', ['permanent', 'verdelen'], true) ? $in['verblijf'] : null;
    $g = startgewichten($profiel, $verblijf);
    if (is_array($in['gewichten'] ?? null)) {
        foreach ($crit as $c) {
            if (isset($in['gewichten'][$c]) && is_numeric($in['gewichten'][$c])) {
                $g[$c] = max(0, min(100, (int)$in['gewichten'][$c]));
            }
        }
    }
    $wensen = (array)($in['wensen'] ?? []);
    $kies = fn($k) => array_slice(array_values(array_intersect((array)($wensen[$k] ?? []), $crit)), 0, cfg('criteria')['wensenlijst']['aantal']);
    $dealbreakers = $kies('dealbreakers');
    $expats = in_array($in['expats'] ?? '', ['veel', 'maakt_niet_uit', 'weinig'], true) ? $in['expats'] : 'maakt_niet_uit';
    if ($expats === 'maakt_niet_uit') {
        $g['expats'] = 0;
    }
    if (!is_array($in['gewichten'] ?? null)) {
        $g = pas_wensen_toe($g, $kies('must'), $kies('nice'));
    }
    $f = (array)($in['filters'] ?? []);
    // dealbreakers worden harde eisen (drempel uit criteria.json); een strengere eigen eis blijft staan
    foreach (cfg('criteria')['criteria'] as $c) {
        if (!in_array($c['id'], $dealbreakers, true) || !isset($c['dealbreaker'])) {
            continue;
        }
        $f = streng($f, $c['dealbreaker']['filter'], drempel($c['dealbreaker'], $wensen['drempels'][$c['id']] ?? null));
        if ($c['id'] === 'risico') {
            $f['rode_zone_uit'] = true;
        }
    }
    $standaard = profiel($profiel)['filters_standaard'] ?? [];
    $budget = null;
    if (!empty($in['budget']) && in_array($in['budget']['soort'] ?? '', ['huren', 'kopen'], true)
        && $num($in['budget']['max'] ?? null) && $num($in['budget']['m2'] ?? null)) {
        $budget = ['soort' => $in['budget']['soort'], 'max' => (float)$in['budget']['max'], 'm2' => (float)$in['budget']['m2']];
    }
    $uur = null;
    if (!empty($f['max_uur']['stad']) && isset(STEDEN[$f['max_uur']['stad']]) && $num($f['max_uur']['uren'] ?? null)) {
        $uur = ['stad' => $f['max_uur']['stad'], 'uren' => (float)$f['max_uur']['uren']];
    }
    $bool = fn($k, $std) => array_key_exists($k, $f) ? (bool)$f[$k] : $std;
    return [
        'profiel' => $profiel, 'verblijf' => $verblijf, 'expats' => $expats, 'gewichten' => $g,
        'ligging' => $lijst($in['ligging'] ?? [], LIGGING),
        'grootte' => array_map('intval', $lijst($in['grootte'] ?? [], ['0', '1', '2', '3', '4'])),
        'regios_uit' => array_map('intval', array_filter((array)($in['regios_uit'] ?? []), 'is_numeric')),
        'alleen_regios' => array_map('intval', array_filter((array)($in['alleen_regios'] ?? []), 'is_numeric')),
        'alleen_provs' => array_values(array_filter(array_map(fn($p) => strtoupper((string)$p), (array)($in['alleen_provs'] ?? [])),
            fn($p) => preg_match('/^[A-Z]{2}$/', $p))),
        'budget' => $budget,
        'max_prijs_m2' => $num($f['max_prijs_m2'] ?? null),
        'max_luchthaven' => array_key_exists('max_luchthaven', $f) ? $num($f['max_luchthaven']) : ($standaard['max_luchthaven'] ?? null),
        'max_ziekenhuis' => array_key_exists('max_ziekenhuis', $f) ? $num($f['max_ziekenhuis']) : ($standaard['max_ziekenhuis'] ?? null),
        'max_uur' => $uur,
        'zone1_uit' => $bool('zone1_uit', false),
        'rode_zone_uit' => $bool('rode_zone_uit', true),
        'min_glasvezel' => array_key_exists('min_glasvezel', $f) ? $num($f['min_glasvezel']) : ($standaard['min_glasvezel_pct'] ?? null),
        'alleen_regime7' => $bool('alleen_regime7', false),
        'max_hoogte' => $num($f['max_hoogte'] ?? null),
        'klimaat' => ($in['klimaat'] ?? '') === 'seizoenen' ? 'seizoenen' : 'zacht',
        'dealbreakers' => $dealbreakers,
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

function woonlast(array $c, array $budget): ?float
{
    $m2 = $budget['soort'] === 'kopen' ? ($c['w']['koop_m2'] ?? null) : ($c['w']['huur_m2'] ?? null);
    return $m2 === null ? null : $m2 * $budget['m2'];
}

/** Harde filters (5a). Onbekende waarden sluiten niet uit, behalve bij de rode zone en het 7%-regime. */
function voldoet(array $c, array $a, array $regiosMetProv = []): bool
{
    $w = $c['w'];
    if ($a['ligging'] && !array_filter($a['ligging'], fn($l) => !empty($c[$l]))) {
        return false;
    }
    if ($a['grootte'] && !in_array(grootteklasse($c['inwoners'] ?? null), $a['grootte'], true)) {
        return false;
    }
    if (in_array((int)$c['regio_code'], $a['regios_uit'], true)) {
        return false;
    }
    if ($a['alleen_regios'] || $a['alleen_provs']) {
        $binnen = in_array($c['prov'], $a['alleen_provs'], true)
            || (in_array((int)$c['regio_code'], $a['alleen_regios'], true) && !in_array((int)$c['regio_code'], $regiosMetProv, true));
        if (!$binnen) {
            return false;
        }
    }
    $max = fn($veld, $grens) => $grens !== null && isset($w[$veld]) && $w[$veld] > $grens;
    if ($max('koop_m2', $a['max_prijs_m2']) || $max('min_luchthaven_jaarrond', $a['max_luchthaven'])
        || $max('min_ziekenhuis', $a['max_ziekenhuis'])) {
        return false;
    }
    if ($a['max_uur'] && $max(STEDEN[$a['max_uur']['stad']], $a['max_uur']['uren'])) {
        return false;
    }
    if ($a['zone1_uit'] && ($w['seismische_zone'] ?? null) === 1) {
        return false;
    }
    if ($a['rode_zone_uit'] && ($w['vulkaan_deel'] ?? null) === 'geheel') {
        return false;
    }
    if ($a['min_glasvezel'] !== null && isset($w['glasvezel_pct']) && $w['glasvezel_pct'] < $a['min_glasvezel']) {
        return false;
    }
    if ($a['max_hoogte'] !== null && ($c['hoogte_m'] ?? null) !== null && $c['hoogte_m'] > $a['max_hoogte']) {
        return false;
    }
    if ($a['alleen_regime7'] && ($w['regime7_status'] ?? '') !== 'ja') {
        return false;
    }
    if ($a['budget']) {
        $prijs = woonlast($c, $a['budget']);
        if ($prijs !== null && $prijs > $a['budget']['max']) {
            return false;
        }
    }
    return true;
}

/** Deelscores per criterium (0-100 of null) na de keuzes van de gebruiker (expats-richting). */
function deelscores(array $c, array $a): array
{
    $s = $c['s'];
    if ($a['klimaat'] === 'seizoenen' && array_key_exists('klimaat_seizoenen', $s)) {
        $s['klimaat'] = $s['klimaat_seizoenen'];
    }
    unset($s['klimaat_seizoenen']);
    if ($a['expats'] === 'weinig' && ($s['expats'] ?? null) !== null) {
        $s['expats'] = 100 - $s['expats'];
    }
    return $s;
}

function totaal(array $deel, array $g): ?float
{
    $som = 0.0;
    $gew = 0.0;
    foreach ($g as $crit => $w) {
        if ($w > 0 && ($deel[$crit] ?? null) !== null) {
            $som += $w * $deel[$crit];
            $gew += $w;
        }
    }
    return $gew > 0 ? $som / $gew : null;
}

function regios_met_prov(array $comuni, array $provs): array
{
    $r = [];
    foreach ($comuni as $c) {
        if (in_array($c['prov'], $provs, true)) {
            $r[(int)$c['regio_code']] = true;
        }
    }
    return array_keys($r);
}

/** Alle plaatsen die door de filters komen, met totaalscore, aflopend gesorteerd. */
function kandidaten(array $comuni, array $a): array
{
    $rmp = $a['alleen_provs'] ? regios_met_prov($comuni, $a['alleen_provs']) : [];
    $uit = [];
    foreach ($comuni as $c) {
        if (!voldoet($c, $a, $rmp)) {
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

/** Drie vergelijkbare plaatsen met een hogere score: zelfde grootteklasse, meest gelijkend profiel van deelscores. */
function alternatieven(array $kand, string $istat, int $n = 3): array
{
    $doel = null;
    foreach ($kand as $k) {
        if ($k['c']['istat'] === $istat) {
            $doel = $k;
            break;
        }
    }
    if (!$doel) {
        return [];
    }
    $gk = grootteklasse($doel['c']['inwoners'] ?? null);
    $afst = [];
    foreach ($kand as $k) {
        if ($k['score'] <= $doel['score'] || grootteklasse($k['c']['inwoners'] ?? null) !== $gk) {
            continue;
        }
        $d = 0.0;
        $m = 0;
        foreach ($doel['deel'] as $crit => $v) {
            if ($v !== null && ($k['deel'][$crit] ?? null) !== null) {
                $d += ($v - $k['deel'][$crit]) ** 2;
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

function samenvatting(array $k, array $a): array
{
    $c = $k['c'];
    $deel = array_map(fn($v) => $v === null ? null : (int)round($v), $k['deel']);
    $met = array_filter($deel, fn($v, $crit) => $v !== null && ($a['gewichten'][$crit] ?? 0) > 0, ARRAY_FILTER_USE_BOTH);
    arsort($met);
    $sterk = array_slice(array_keys($met), 0, 3);
    $zwak = array_slice(array_reverse(array_diff(array_keys($met), $sterk)), 0, 3);
    $budget = null;
    if ($a['budget']) {
        $prijs = woonlast($c, $a['budget']);
        $budget = ['soort' => $a['budget']['soort'], 'm2' => (int)$a['budget']['m2'],
                   'prijs' => $prijs === null ? null : (int)round($prijs, $a['budget']['soort'] === 'kopen' ? -3 : -1),
                   'onder_pct' => $prijs === null ? null : (int)round(($a['budget']['max'] - $prijs) / $a['budget']['max'] * 100)];
    }
    return [
        'istat' => $c['istat'], 'naam' => $c['naam'], 'prov' => $c['prov'], 'regio' => $c['regio'],
        'inwoners' => $c['inwoners'], 'hoogte_m' => $c['hoogte_m'], 'lat' => $c['lat'], 'lon' => $c['lon'],
        'trend' => $c['trend'], 'score' => (int)round($k['score']), 'deel' => $deel,
        'sterk' => $sterk, 'zwak' => $zwak,
        'geen_data' => array_values(array_keys(array_filter($deel, fn($v, $crit) => $v === null && ($a['gewichten'][$crit] ?? 0) > 0, ARRAY_FILTER_USE_BOTH))),
        'winter' => $c['tw'], 'tachtig' => $c['tt'], 'woonlast' => $budget,
    ];
}

/** Hoofdfunctie: antwoorden in, top N uit (plus eventueel alternatieven voor één gemeente). */
function scoor(array $invoer, array $comuni, int $n = 25): array
{
    $a = antwoorden($invoer);
    $kand = kandidaten($comuni, $a);
    $uit = ['aantal' => count($kand), 'gewichten' => $a['gewichten'], 'dealbreakers' => $a['dealbreakers'],
            'top' => array_map(fn($k) => samenvatting($k, $a), array_slice($kand, 0, $n))];
    if (!empty($invoer['alternatieven_voor'])) {
        $uit['alternatieven'] = array_map(fn($k) => samenvatting($k, $a), alternatieven($kand, (string)$invoer['alternatieven_voor']));
    }
    return $uit;
}
