<?php
declare(strict_types=1);

/*
 * Scoremodel uit PLAN.md
 *  1. filters (stap 1, 2, 6, 7, 8) sluiten plaatsen uit; dezelfde regels draaien in app.js voor de teller
 *  2. elke indicator is al een percentiel 0-100 (pipeline), laag-is-beter is omgedraaid
 *  3. gewichten: top 3 = 5, 4, 3; extra = 3; niet gekozen = 1
 *  4. profiel: +2 op de punten die bij het profiel horen
 *  5. betaalbaarheid met budget: hoe ver de geschatte woonlast onder het budget blijft
 *  6. ontbrekende data telt niet als nul: het gewicht gaat naar de andere punten
 *  7. top 25 met totaal en de 3 sterkste en 3 zwakste punten
 */

const LIGGING = ['kust', 'meer', 'heuvel', 'berg', 'vlakte'];
const GROOTTE = [1000, 5000, 20000, 100000];

/** Prioriteit -> percentielkolommen uit de pipeline (deelscore = gemiddelde van wat er is). */
const PRIORITEITEN = [
    'betaalbaar'     => ['koop'],
    'zorg'           => ['ziekenhuis', 'apotheken'],
    'economie'       => ['inkomen'],
    'internet'       => ['glasvezel'],
    'lucht'          => ['pm25'],
    'milieu'         => ['afval'],
    'trein'          => ['station'],
    'luchthaven'     => ['luchthaven'],
    'voorzieningen'  => ['voorzieningen'],
    'internationaal' => ['buitenlanders'],
    'groei'          => ['groei'],
    'scholen'        => ['school_basis', 'school_midden', 'school_hoog'],
];

const PROFIEL_BONUS = [
    'gepensioneerd' => ['zorg' => 2],
    'thuiswerker'   => ['internet' => 2],
    'forens'        => ['trein' => 2, 'werk' => 2],
    'werkzoekend'   => ['economie' => 2],
];

const TOP_GEWICHT = [5, 4, 3];
const EXTRA_GEWICHT = 3;
const BASIS_GEWICHT = 1;
const BUDGET_SCHAAL = 2;   // 50% onder budget = score 100
const MAX_RIJTIJD = 240;   // gelijk aan de pipeline: langer = "meer dan 4 uur"

function km(float $la1, float $lo1, float $la2, float $lo2): float
{
    $r = 6371.0;
    $dla = deg2rad($la2 - $la1);
    $dlo = deg2rad($lo2 - $lo1);
    $a = sin($dla / 2) ** 2 + cos(deg2rad($la1)) * cos(deg2rad($la2)) * sin($dlo / 2) ** 2;
    return 2 * $r * asin(min(1.0, sqrt($a)));
}

/** Controleert en normaliseert de antwoorden uit de wizard. */
function antwoorden(array $in): array
{
    $lijst = fn($v, array $mag) => array_values(array_intersect(array_map('strval', (array)($v ?? [])), $mag));
    $prio = array_keys(PRIORITEITEN);
    $top = array_slice($lijst($in['top'] ?? [], $prio), 0, 3);
    $extra = array_values(array_diff($lijst($in['extra'] ?? [], $prio), $top));
    $num = fn($v) => is_numeric($v) && $v > 0 ? (float)$v : null;
    $werk = null;
    if (!empty($in['werk']['istat']) && $num($in['werk']['km'] ?? null)) {
        $werk = ['istat' => preg_replace('/\D/', '', (string)$in['werk']['istat']), 'km' => (float)$in['werk']['km']];
    }
    $budget = null;
    if (!empty($in['budget']) && in_array($in['budget']['soort'] ?? '', ['huren', 'kopen'], true)
        && $num($in['budget']['max'] ?? null) && $num($in['budget']['m2'] ?? null)) {
        $budget = ['soort' => $in['budget']['soort'], 'max' => (float)$in['budget']['max'], 'm2' => (float)$in['budget']['m2']];
    }
    $scholen = $lijst($in['scholen'] ?? [], ['basis', 'midden', 'hoog']);
    return [
        'ligging' => $lijst($in['ligging'] ?? [], LIGGING),
        'grootte' => array_map('intval', $lijst($in['grootte'] ?? [], ['0', '1', '2', '3', '4'])),
        'profiel' => in_array($in['profiel'] ?? '', array_keys(PROFIEL_BONUS), true) ? $in['profiel'] : 'anders',
        'top' => $top,
        'extra' => $extra,
        'scholen' => $scholen ?: ['basis', 'midden', 'hoog'],
        'max_luchthaven' => $num($in['max_luchthaven'] ?? null),
        'max_station' => $num($in['max_station'] ?? null),
        'werk' => $werk,
        'budget' => $budget,
        'regios_uit' => array_map('intval', array_filter((array)($in['regios_uit'] ?? []), 'is_numeric')),
        // "alleen in": regio's en/of provincies (sigla). Een gekozen provincie beperkt haar regio tot die provincies.
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
        if ($inw >= $grens) {
            $k++;
        }
    }
    return $k;
}

function woonlast(array $c, array $budget): ?float
{
    $m2prijs = $budget['soort'] === 'kopen' ? ($c['koop_m2'] ?? null) : ($c['huur_m2'] ?? null);
    return $m2prijs === null ? null : $m2prijs * $budget['m2'];
}

/** Geschatte woonlast voor de uitslag (alleen met budget): prijs, % onder budget en of het een provinciecijfer is. */
function woonlast_info(array $c, array $a): ?array
{
    if (!$a['budget']) {
        return null;
    }
    $prijs = woonlast($c, $a['budget']);
    $kort = $a['budget']['soort'] === 'kopen' ? 'koop' : 'huur';
    return [
        'soort' => $a['budget']['soort'],
        'm2' => (int)$a['budget']['m2'],
        'prijs' => $prijs === null ? null : (int)(round($prijs / ($kort === 'koop' ? 1000 : 10)) * ($kort === 'koop' ? 1000 : 10)),
        'm2_prijs' => $c[$kort . '_m2'] ?? null,
        'onder_pct' => $prijs === null ? null : (int)round(($a['budget']['max'] - $prijs) / $a['budget']['max'] * 100),
        'provinciecijfer' => in_array($kort, $c['provinciecijfer'] ?? [], true),
    ];
}

/** Filters. Onbekende waarden (null) sluiten een plaats niet uit; de uitslag meldt dan "geen gegevens". */
function voldoet(array $c, array $a, ?array $werk): bool
{
    if ($a['ligging']) {
        $ok = false;
        foreach ($a['ligging'] as $l) {
            $ok = $ok || !empty($c[$l]);
        }
        if (!$ok) {
            return false;
        }
    }
    if ($a['grootte'] && !in_array(grootteklasse($c['inwoners'] ?? null), $a['grootte'], true)) {
        return false;
    }
    if ($a['max_luchthaven'] !== null && $c['min_luchthaven'] !== null && $c['min_luchthaven'] > $a['max_luchthaven']) {
        return false;
    }
    if ($a['max_station'] !== null && $c['min_station'] !== null && $c['min_station'] > $a['max_station']) {
        return false;
    }
    if ($werk && km($c['lat'], $c['lon'], $werk['lat'], $werk['lon']) > $a['werk']['km']) {
        return false;
    }
    if ($a['budget']) {
        $prijs = woonlast($c, $a['budget']);
        if ($prijs !== null && $prijs > $a['budget']['max']) {
            return false;
        }
    }
    if (in_array((int)$c['regio_code'], $a['regios_uit'], true)) {
        return false;
    }
    if (($a['alleen_regios'] || $a['alleen_provs']) && !binnen_gebied($c, $a)) {
        return false;
    }
    return true;
}

/**
 * "Alleen in": een plaats telt mee als haar provincie gekozen is, of als haar regio gekozen is
 * en er in die regio geen afzonderlijke provincies gekozen zijn.
 */
function binnen_gebied(array $c, array $a): bool
{
    if (in_array($c['prov'], $a['alleen_provs'], true)) {
        return true;
    }
    return in_array((int)$c['regio_code'], $a['alleen_regios'], true)
        && !in_array((int)$c['regio_code'], $a['_regios_met_prov'] ?? [], true);
}

function gewichten(array $a): array
{
    $w = array_fill_keys(array_keys(PRIORITEITEN), BASIS_GEWICHT);
    $w['scholen'] = 0;                                   // alleen als gekozen (stap 5b)
    $w['werk'] = $a['werk'] ? BASIS_GEWICHT : 0;         // alleen met een werkplek
    foreach ($a['top'] as $i => $p) {
        $w[$p] = TOP_GEWICHT[$i];
    }
    foreach ($a['extra'] as $p) {
        $w[$p] = EXTRA_GEWICHT;
    }
    foreach (PROFIEL_BONUS[$a['profiel']] ?? [] as $p => $bonus) {
        if ($w[$p] > 0) {
            $w[$p] += $bonus;
        }
    }
    return $w;
}

/** Deelscores 0-100 per prioriteit; null = geen gegevens. */
function deelscores(array $c, array $a, ?array $werk): array
{
    $s = [];
    foreach (PRIORITEITEN as $p => $cols) {
        if ($p === 'scholen') {
            $cols = array_map(fn($n) => "school_$n", $a['scholen']);
        }
        if ($p === 'betaalbaar' && $a['budget'] && $a['budget']['soort'] === 'huren') {
            $cols = ['huur'];
        }
        $vals = [];
        foreach ($cols as $k) {
            if (isset($c['p'][$k])) {
                $vals[] = (float)$c['p'][$k];
            }
        }
        $s[$p] = $vals ? array_sum($vals) / count($vals) : null;
    }
    if ($a['budget']) {
        $prijs = woonlast($c, $a['budget']);
        // hoe ver de woonlast onder het budget blijft; 50% of meer onder budget = 100
        $s['betaalbaar'] = $prijs === null ? null
            : max(0.0, min(100.0, ($a['budget']['max'] - $prijs) / $a['budget']['max'] * 100 * BUDGET_SCHAAL));
    }
    $s['werk'] = null;
    if ($werk) {
        $d = km($c['lat'], $c['lon'], $werk['lat'], $werk['lon']);
        $s['werk'] = max(0.0, 100 * (1 - $d / $a['werk']['km']));
    }
    return $s;
}

function totaal(array $deel, array $w): ?float
{
    $som = 0.0;
    $gew = 0.0;
    foreach ($w as $p => $g) {
        if ($g > 0 && $deel[$p] !== null) {
            $som += $g * $deel[$p];
            $gew += $g;
        }
    }
    return $gew > 0 ? $som / $gew : null;
}

function fmt_getal(float $n, int $dec = 0): string
{
    return number_format($n, $dec, ',', '.');
}

/** Zin in gewone taal voor één prioriteit van één plaats. */
function zin(string $p, array $c, array $a, ?array $werk, array $t): string
{
    $z = $t['zinnen'];
    $r = fn(string $tpl, array $v) => strtr($tpl, array_combine(array_map(fn($k) => '{' . $k . '}', array_keys($v)), array_values($v)));
    $min = fn($m) => $m >= MAX_RIJTIJD ? $z['ver'] : strtr($z['minuten'], ['{n}' => $m]);
    $prov = fn(string $kort) => in_array($kort, $c['provinciecijfer'] ?? [], true) ? ' (' . $t['uitslag']['provinciecijfer'] . ')' : '';
    switch ($p) {
        case 'betaalbaar':
            if ($a['budget']) {
                $prijs = woonlast($c, $a['budget']);
                $pct = (int)round(($a['budget']['max'] - $prijs) / $a['budget']['max'] * 100);
                $tpl = $pct >= 5 ? $z['betaalbaar_budget'] : $z['betaalbaar_budget_krap'];
                return $r($tpl, ['prijs' => fmt_getal($prijs), 'pct' => $pct]) . $prov($a['budget']['soort'] === 'kopen' ? 'koop' : 'huur');
            }
            return $r($z['betaalbaar'], ['koop' => fmt_getal((float)$c['koop_m2'])]) . $prov('koop');
        case 'zorg':
            $n = (int)($c['apotheken_n'] ?? 0);
            $apo = $n === 0 ? $z['apotheek_0'] : ($n === 1 ? $z['apotheek_1'] : $r($z['apotheek_n'], ['n' => $n]));
            return $c['min_ziekenhuis'] === null ? $r($z['zorg_geen_rit'], ['apotheek' => $apo])
                : $r($z['zorg'], ['min' => $min($c['min_ziekenhuis']), 'apotheek' => $apo]);
        case 'economie':
            return $r($z['economie'], ['inkomen' => fmt_getal((float)$c['inkomen_eur'])]) . $prov('inkomen');
        case 'internet':
            return $r($z['internet'], ['pct' => fmt_getal((float)$c['glasvezel_pct'])]) . $prov('glasvezel');
        case 'lucht':
            return $r($z['lucht'], ['pm25' => fmt_getal((float)$c['pm25'], 1)]);
        case 'milieu':
            return $r($z['milieu'], ['pct' => fmt_getal((float)$c['afval_gescheiden_pct'])]) . $prov('afval');
        case 'trein':
            return $r($z['trein'], ['naam' => $c['station_naam'] ?? '', 'min' => $min($c['min_station'])]);
        case 'luchthaven':
            return $r($z['luchthaven'], ['naam' => $c['luchthaven_naam'] ?? '', 'min' => $min($c['min_luchthaven'])]);
        case 'voorzieningen':
            $n = (int)$c['voorzieningen_800m'];
            return $n === 0 ? $z['voorzieningen_0'] : $r($z['voorzieningen'], ['n' => $n]);
        case 'internationaal':
            return $r($z['internationaal'], ['pct' => fmt_getal((float)$c['buitenlanders_pct'], 1)]);
        case 'groei':
            $g = (float)$c['groei_pct'];
            return $r($z['groei'], ['pct' => ($g > 0 ? '+' : '') . fmt_getal($g, 1)]);
        case 'scholen':
            $wel = $niet = [];
            foreach ($a['scholen'] as $niv) {
                $naam = $z["school_$niv"];
                if (($c["scholen_{$niv}_5km"] ?? 0) > 0) {
                    $wel[] = $naam;
                } else {
                    $niet[] = $naam;
                }
            }
            $en = fn(array $l) => count($l) > 1 ? implode(', ', array_slice($l, 0, -1)) . ' ' . $z['en'] . ' ' . end($l) : ($l[0] ?? '');
            return $niet ? $r($z['scholen_mist'], ['lijst' => $en($niet)]) : $r($z['scholen_alle'], ['lijst' => $en($wel)]);
        case 'werk':
            return $r($z['werk'], ['km' => fmt_getal(km($c['lat'], $c['lon'], $werk['lat'], $werk['lon']))]);
    }
    return '';
}

/** Hoofdfunctie: antwoorden in, top N uit. */
function scoor(array $invoer, array $comuni, array $t, int $n = 25): array
{
    $a = antwoorden($invoer);
    $werk = null;
    if ($a['werk']) {
        foreach ($comuni as $c) {
            if ($c['istat'] === $a['werk']['istat']) {
                $werk = ['lat' => $c['lat'], 'lon' => $c['lon'], 'naam' => $c['naam']];
                break;
            }
        }
        if (!$werk) {
            $a['werk'] = null;
        }
    }
    if ($a['alleen_provs']) {
        $met = [];
        foreach ($comuni as $c) {
            if (in_array($c['prov'], $a['alleen_provs'], true)) {
                $met[(int)$c['regio_code']] = true;
            }
        }
        $a['_regios_met_prov'] = array_keys($met);
    }
    $w = gewichten($a);
    $kandidaten = [];
    foreach ($comuni as $c) {
        if (!voldoet($c, $a, $werk)) {
            continue;
        }
        $deel = deelscores($c, $a, $werk);
        $tot = totaal($deel, $w);
        if ($tot !== null) {
            $kandidaten[] = [$tot, $c, $deel];
        }
    }
    usort($kandidaten, fn($x, $y) => $y[0] <=> $x[0] ?: strcmp($x[1]['naam'], $y[1]['naam']));

    $uit = [];
    foreach (array_slice($kandidaten, 0, $n) as [$tot, $c, $deel]) {
        $met = $zonder = [];
        foreach ($w as $p => $g) {
            if ($g <= 0) {
                continue;
            }
            if ($deel[$p] === null) {
                $zonder[] = $p;
            } else {
                $met[$p] = $deel[$p];
            }
        }
        $lijst = $met;
        if ($a['budget']) {
            unset($lijst['betaalbaar']);   // staat al in het woonlast-blok
        }
        arsort($lijst);
        $sterk = array_slice(array_keys($lijst), 0, 3);
        $zwak = array_slice(array_reverse(array_diff(array_keys($lijst), $sterk)), 0, 3);
        $punt = fn($p) => ['punt' => $p, 'score' => (int)round($met[$p]), 'tekst' => zin($p, $c, $a, $werk, $t)];
        $uit[] = [
            'istat' => $c['istat'], 'naam' => $c['naam'], 'prov' => $c['prov'], 'regio' => $c['regio'],
            'inwoners' => $c['inwoners'], 'hoogte_m' => $c['hoogte_m'], 'lat' => $c['lat'], 'lon' => $c['lon'],
            'score' => (int)round($tot),
            'sterk' => array_map($punt, $sterk),
            'zwak' => array_map($punt, $zwak),
            'geen_gegevens' => array_values(array_filter($zonder, fn($p) => $w[$p] > BASIS_GEWICHT)),
            'woonlast' => woonlast_info($c, $a),
        ];
    }
    return ['aantal' => count($kandidaten), 'top' => $uit, 'gewichten' => $w, 'werk' => $werk];
}
