<?php
declare(strict_types=1);
/*
 * statistiek.php: overzicht van hoe bezoekers de Italië-Verkenner invullen (alleen voor de beheerder).
 * Wachtwoord: kopieer lib/statistiek-wachtwoord.voorbeeld.php naar lib/statistiek-wachtwoord.php en vul het in.
 * ?maand=JJJJ-MM kiest een maand (standaard alles); ?csv=1 downloadt de keuzes per bezoek als CSV.
 */
require __DIR__ . '/lib/data.php';
require __DIR__ . '/lib/model.php';
require __DIR__ . '/lib/statistiek.php';

header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$wwBestand = __DIR__ . '/lib/statistiek-wachtwoord.php';
$wachtwoord = is_file($wwBestand) ? (string)(include $wwBestand) : '';

function pagina(string $titel, string $inhoud): void
{
    $css = 'assets/style.css?v=' . (int)@filemtime(__DIR__ . '/assets/style.css');
    echo "<!doctype html><html lang=\"nl\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
        . "<meta name=\"robots\" content=\"noindex\"><title>$titel</title><link rel=\"stylesheet\" href=\"$css\"><style>"
        . ".st-kaarten{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin:12px 0}"
        . ".st-kaart{background:var(--pv-vlak);border-radius:var(--pv-radius);padding:10px 14px;border-left:6px solid var(--pv-groen)}"
        . ".st-kaart strong{display:block;font-size:1.7rem;color:var(--pv-groen)}.st-kaart span{font-size:.85rem;color:var(--pv-zacht)}"
        . ".st-blok{background:var(--pv-vlak);border-radius:var(--pv-radius);padding:12px 14px;margin:14px 0;border-left:6px solid var(--pv-blauw)}"
        . ".st-blok h2{font-size:1.15rem;margin:0 0 8px}.st-rij{display:grid;grid-template-columns:minmax(0,1fr) 110px 70px;gap:8px;align-items:center;padding:3px 0;font-size:.92rem}"
        . ".st-balk{height:10px;background:var(--pv-lijn);border-radius:5px;overflow:hidden}.st-balk i{display:block;height:100%;background:var(--pv-blauw)}"
        . ".st-getal{text-align:right;font-variant-numeric:tabular-nums}.st-drie{display:flex;height:14px;border-radius:7px;overflow:hidden;min-width:120px;background:var(--pv-lijn)}"
        . ".st-drie i{display:block;height:100%}.st-n{background:var(--pv-rood)}.st-m{background:var(--pv-blauw)}.st-o{background:var(--pv-lijn)}"
        . ".st-tabel{width:100%;border-collapse:collapse;font-size:.88rem}.st-tabel th,.st-tabel td{text-align:left;padding:6px;border-top:1px solid var(--pv-lijn);vertical-align:top}"
        . ".st-tabel td.r,.st-tabel th.r{text-align:right;font-variant-numeric:tabular-nums}.st-legenda i{display:inline-block;width:12px;height:12px;border-radius:3px;margin:0 4px 0 10px;vertical-align:-1px}"
        . "@media (max-width:700px){.st-eig thead{display:none}.st-eig tr{display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;padding:10px 0;border-top:1px solid var(--pv-lijn)}"
        . ".st-eig th,.st-eig td{border:0;padding:0}.st-eig th,.st-eig td:nth-child(2){grid-column:1/-1}.st-eig td.r{text-align:left}"
        . ".st-eig td[data-l]::before{content:attr(data-l) \": \";color:var(--pv-zacht);font-size:.85em}.st-eig .st-drie{margin:4px 0}}"
        . "form.st-login{display:grid;gap:8px;max-width:320px}form.st-login input{padding:10px;font:inherit;border:1.5px solid var(--pv-lijn);border-radius:8px}"
        . "</style></head><body><main class=\"pv\" style=\"max-width:980px\"><header class=\"pv-kop\"><h1><span class=\"pv-titel-1\">Italië</span><span class=\"pv-titel-2\">-Verkenner</span></h1>"
        . "<p class=\"pv-kop-sub\">Statistiek: hoe bezoekers de verkenner invullen</p></header>$inhoud</main></body></html>";
}

// ---------------------------------------------------------------- toegang
if ($wachtwoord === '') {
    pagina('Statistiek', '<p>Er is nog geen wachtwoord ingesteld. Kopieer <code>lib/statistiek-wachtwoord.voorbeeld.php</code> naar '
        . '<code>lib/statistiek-wachtwoord.php</code> en zet daarin een wachtwoord. Zie INSTALLEREN.txt.</p>');
    exit;
}
session_name('iv_beheer');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
if (isset($_GET['uit'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: statistiek.php');
    exit;
}
if (empty($_SESSION['ok'])) {
    $fout = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (hash_equals($wachtwoord, (string)($_POST['wachtwoord'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['ok'] = true;
            header('Location: statistiek.php');
            exit;
        }
        sleep(2);
        $fout = '<p class="pv-fout">Onjuist wachtwoord.</p>';
    }
    pagina('Statistiek', "<h2>Inloggen</h2>$fout<form method=\"post\" class=\"st-login\"><label for=\"ww\">Wachtwoord</label>"
        . '<input type="password" id="ww" name="wachtwoord" autofocus autocomplete="current-password"><button class="pv-knop pv-knop-hoofd">Inloggen</button></form>');
    exit;
}

// ---------------------------------------------------------------- gegevens
$alleMaanden = stat_maanden();
$maand = in_array($_GET['maand'] ?? '', $alleMaanden, true) ? $_GET['maand'] : '';
$ev = stat_lees($maand ? [$maand] : []);
$t = taal();
$E = eigenschappen();
$profielLabel = array_column(cfg('profielen')['profielen'], 'label', 'id');
$namen = [];
$kj = json_decode((string)@file_get_contents(__DIR__ . '/data/kaart.json'), true);
foreach ($kj['gemeenten'] ?? [] as $istat => $g) {
    $namen[$istat] = $g[0] . ' (' . $g[1] . ')';
}

$sessies = $perDag = $stappen = $acties = $apparaat = $herkomst = $gemeenteBekeken = [];
$laatste = [];          // laatste uitslag per bezoek = de keuzes waarmee het bezoek eindigde
$uitslagen = 0;
foreach ($ev as $r) {
    $s = $r['s'];
    $dag = substr($r['t'], 0, 10);
    $sessies[$s] = true;
    $perDag[$dag]['bezoek'][$s] = true;
    switch ($r['k']) {
        case 'start':
        case 'gedeeld':
            $apparaat[$r['a'] ?? 'onbekend'][$s] = true;
            $herkomst[$r['b'] ?? '(direct of onbekend)'][$s] = true;
            if ($r['k'] === 'gedeeld') {
                $acties['gedeeld'][$s] = true;
            }
            break;
        case 'stap':
            $stappen[(int)$r['n']][$s] = true;
            break;
        case 'uitslag':
            $uitslagen++;
            $laatste[$s] = $r;
            $perDag[$dag]['uitslag'][$s] = true;
            break;
        case 'gemeente':
            $gemeenteBekeken[$r['i']] = ($gemeenteBekeken[$r['i']] ?? 0) + 1;
            $acties['gemeente'][$s] = true;
            break;
        default:
            $acties[$r['k']][$s] = true;
    }
}
$nBezoek = count($sessies);
$nUitslag = count($laatste);

// ---------------------------------------------------------------- CSV
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="italie-verkenner-keuzes-' . ($maand ?: 'alles') . '.csv"');
    $f = fopen('php://output', 'w');
    fwrite($f, "\xEF\xBB\xBF");
    fputcsv($f, array_merge(['datum_uur (UTC)', 'profiel', 'verblijf', 'zoekgebied', 'regios', 'provincies', 'ligging', 'grootte', 'aantal_gemeenten', 'top10'],
        array_column($E, 'label')), ';');
    foreach ($laatste as $r) {
        $rij = [$r['t'], $profielLabel[$r['p']] ?? $r['p'], $r['v'] ?? '', $r['z'], implode(' ', array_map(fn($x) => $t['regios'][(string)$x] ?? $x, $r['r'] ?? [])),
            implode(' ', $r['pr'] ?? []), implode(' ', $r['l'] ?? []), implode(' ', array_map(fn($x) => $t['stappen']['grootte']['opties'][(string)$x][0] ?? $x, $r['g'] ?? [])),
            $r['n'] ?? '', implode(' | ', array_map(fn($i) => $namen[$i] ?? $i, $r['top'] ?? []))];
        foreach ($E as $x) {
            $c = $r['e'][$x['id']] ?? '';     // leesbaar: "noodzakelijk: <niveau>", "meewegen 6: <niveau>" of "onbelangrijk"
            $nv = $c !== '' && $c[0] !== 'o' ? ($x['niveaus'][(int)substr($c, 1)]['label'] ?? '') : '';
            $rij[] = match ($c[0] ?? '') { 'n' => "noodzakelijk: $nv", 'm' => 'meewegen ' . explode(':', $c)[1] . ": $nv", 'o' => 'onbelangrijk', default => '' };
        }
        fputcsv($f, $rij, ';');
    }
    exit;
}

// ---------------------------------------------------------------- weergave
$pct = fn($a, $b) => $b ? round($a / $b * 100) : 0;
$getal = fn($n) => number_format((float)$n, 0, ',', '.');
function balken(array $telling, int $totaal, $label, int $max = 0): string
{
    arsort($telling);
    if ($max) {
        $telling = array_slice($telling, 0, $max, true);
    }
    $top = max($telling ?: [1]);
    $h = '';
    foreach ($telling as $k => $n) {
        $p = $totaal ? round($n / $totaal * 100) : 0;
        $h .= '<div class="st-rij"><span>' . htmlspecialchars((string)$label($k), ENT_QUOTES) . '</span><span class="st-balk"><i style="width:'
            . round($n / $top * 100) . '%"></i></span><span class="st-getal">' . number_format($n, 0, ',', '.') . ($totaal ? " ($p%)" : '') . '</span></div>';
    }
    return $h ?: '<p class="pv-noot">Nog geen gegevens.</p>';
}
$tel = function (callable $f) use ($laatste): array {
    $c = [];
    foreach ($laatste as $r) {
        foreach ((array)$f($r) as $k) {
            $c[(string)$k] = ($c[(string)$k] ?? 0) + 1;
        }
    }
    return $c;
};

$h = '<form method="get" class="pv-knoprij" style="align-items:center;gap:8px;flex-wrap:wrap"><label for="maand">Periode</label>'
    . '<select id="maand" name="maand" onchange="this.form.submit()"><option value="">alles</option>';
foreach ($alleMaanden as $m) {
    $h .= '<option' . ($m === $maand ? ' selected' : '') . ">$m</option>";
}
$h .= '</select><a class="pv-knop pv-knop-klein" href="?csv=1' . ($maand ? "&amp;maand=$maand" : '') . '">Download keuzes (CSV)</a>'
    . '<a class="pv-link" href="?uit=1">Uitloggen</a></form>';
$h .= '<p class="pv-noot">Een bezoek is één geopend tabblad (zonder cookies, dus iemand die later terugkomt telt opnieuw). '
    . 'De keuzes per bezoek zijn de laatste keuzes waarmee een uitslag werd bekeken. Tijden in UTC.</p>';

$h .= '<div class="st-kaarten">'
    . "<div class=\"st-kaart\"><strong>{$getal($nBezoek)}</strong><span>bezoeken</span></div>"
    . "<div class=\"st-kaart\"><strong>{$getal(count($stappen[0] ?? []))}</strong><span>begonnen met invullen</span></div>"
    . "<div class=\"st-kaart\"><strong>{$getal($nUitslag)}</strong><span>bekeken een uitslag ({$pct($nUitslag, $nBezoek)}%)</span></div>"
    . "<div class=\"st-kaart\"><strong>{$getal($uitslagen)}</strong><span>uitslagen in totaal (ook na aanpassen)</span></div>"
    . '</div>';

// trechter
$tr = [];
foreach (['profiel', 'gebied', 'ligging', 'grootte', 'eigenschappen', 'gewichten'] as $i => $st) {
    $tr['Stap ' . ($i + 1) . ': ' . $t['stappen'][$st]['titel']] = count($stappen[$i] ?? []);
}
$tr['Uitslag bekeken'] = $nUitslag;
$h .= '<section class="st-blok"><h2>Hoe ver komen bezoekers?</h2>';
foreach ($tr as $k => $n) {
    $h .= '<div class="st-rij"><span>' . $e($k) . '</span><span class="st-balk"><i style="width:' . $pct($n, max(1, $nBezoek)) . '%"></i></span><span class="st-getal">'
        . $getal($n) . ' (' . $pct($n, $nBezoek) . '%)</span></div>';
}
$h .= '</section>';

// per dag
krsort($perDag);
$h .= '<section class="st-blok"><h2>Per dag</h2><div class="pv-tabelrol"><table class="st-tabel"><thead><tr><th>Dag</th><th class="r">Bezoeken</th><th class="r">Met uitslag</th></tr></thead><tbody>';
foreach (array_slice($perDag, 0, 62, true) as $d => $x) {
    $h .= "<tr><td>$d</td><td class=\"r\">" . count($x['bezoek'] ?? []) . '</td><td class="r">' . count($x['uitslag'] ?? []) . '</td></tr>';
}
$h .= '</tbody></table></div></section>';

// profiel, gebied, ligging, grootte
$h .= '<section class="st-blok"><h2>Profiel</h2>' . balken($tel(fn($r) => $r['p'] . ($r['v'] && $r['p'] === 'gepensioneerde' ? '|' . $r['v'] : '')), $nUitslag,
    fn($k) => ($profielLabel[explode('|', $k)[0]] ?? $k) . (str_contains($k, '|') ? ', ' . explode('|', $k)[1] : '')) . '</section>';
$h .= '<section class="st-blok"><h2>Zoekgebied</h2>' . balken($tel(fn($r) => $r['z']), $nUitslag, fn($k) => ['overal' => 'Heel Italië', 'regios' => "Bepaalde regio's", 'provincies' => 'Bepaalde provincies'][$k] ?? $k)
    . '<h3>Gekozen regio\'s</h3>' . balken($tel(fn($r) => $r['z'] === 'regios' ? $r['r'] : []), count(array_filter($laatste, fn($r) => $r['z'] === 'regios')), fn($k) => $t['regios'][$k] ?? $k)
    . '<h3>Gekozen provincies</h3>' . balken($tel(fn($r) => $r['z'] === 'provincies' ? $r['pr'] : []), count(array_filter($laatste, fn($r) => $r['z'] === 'provincies')), fn($k) => $k, 25) . '</section>';
$h .= '<section class="st-blok"><h2>Ligging</h2>' . balken($tel(fn($r) => $r['l'] ?: ['geen']), $nUitslag, fn($k) => $k === 'geen' ? 'Geen voorkeur' : ($t['stappen']['ligging']['opties'][$k][0] ?? $k)) . '</section>';
$h .= '<section class="st-blok"><h2>Grootte</h2>' . balken($tel(fn($r) => $r['g'] ?: ['geen']), $nUitslag, fn($k) => $k === 'geen' ? 'Geen voorkeur' : ($t['stappen']['grootte']['opties'][$k][0] ?? $k)) . '</section>';

// eigenschappen
$h .= '<section class="st-blok"><h2>Eigenschappen</h2><p class="st-legenda pv-noot">Per eigenschap het aandeel bezoeken met'
    . '<i class="st-n"></i>noodzakelijk<i class="st-m"></i>meewegen<i class="st-o"></i>onbelangrijk. '
    . '"Aangepast" is het aandeel dat iets anders koos dan de startkeuze van het profiel.</p><table class="st-tabel st-eig"><thead><tr>'
    . '<th>Eigenschap</th><th>Verdeling</th><th class="r">Nood&shy;zakelijk</th><th class="r">Onbe&shy;langrijk</th><th>Meest gekozen niveau</th><th class="r">Gem. gewicht</th><th class="r">Aan&shy;gepast</th></tr></thead><tbody>';
$start = [];
foreach ($E as $x) {
    $c = ['n' => 0, 'm' => 0, 'o' => 0];
    $niv = [];
    $gw = [];
    $anders = 0;
    foreach ($laatste as $s => $r) {
        $code = $r['e'][$x['id']] ?? null;
        if ($code === null) {
            continue;
        }
        $c[$code[0]]++;
        if ($code[0] !== 'o') {
            $n = (int)substr($code, 1);
            $niv[$n] = ($niv[$n] ?? 0) + 1;
        }
        if ($code[0] === 'm') {
            $gw[] = (int)explode(':', $code)[1];
        }
        $sk = $start[$r['p'] . '|' . ($r['v'] ?? '')] ??= stat_keuzes(antwoorden(['profiel' => $r['p'], 'verblijf' => $r['v'] ?? null]), [])['e'];
        $anders += ($sk[$x['id']] ?? '') !== $code ? 1 : 0;
    }
    $tot = array_sum($c);
    arsort($niv);
    $vaak = $niv ? ($x['niveaus'][array_key_first($niv)]['label'] ?? '') . ' (' . $pct(reset($niv), array_sum($niv)) . '%)' : '';
    $h .= '<tr><th>' . $e($x['label']) . '</th><td><span class="st-drie">' . ($tot ? '<i class="st-n" style="width:' . ($c['n'] / $tot * 100) . '%"></i><i class="st-m" style="width:'
        . ($c['m'] / $tot * 100) . '%"></i><i class="st-o" style="width:' . ($c['o'] / $tot * 100) . '%"></i>' : '') . '</span></td>'
        . '<td class="r" data-l="Noodzakelijk">' . $pct($c['n'], $tot) . '%</td><td class="r" data-l="Onbelangrijk">' . $pct($c['o'], $tot) . '%</td>'
        . '<td data-l="Meest gekozen niveau">' . $e($vaak ?: '-') . '</td><td class="r" data-l="Gemiddeld gewicht">'
        . ($gw ? number_format(array_sum($gw) / count($gw), 1, ',', '') : '-') . '</td><td class="r" data-l="Aangepast">' . $pct($anders, $tot) . '%</td></tr>';
}
$h .= '</tbody></table></section>';

// uitkomst
$ns = array_map(fn($r) => (int)($r['n'] ?? 0), $laatste);
sort($ns);
$med = $ns ? $ns[intdiv(count($ns), 2)] : 0;
$h .= '<section class="st-blok"><h2>Uitkomst</h2><p>Aantal gemeenten dat overblijft: mediaan <strong>' . $getal($med) . '</strong>; '
    . 'geen enkele gemeente bij <strong>' . $pct(count(array_filter($ns, fn($n) => $n === 0)), count($ns)) . '%</strong>, minder dan 10 bij <strong>'
    . $pct(count(array_filter($ns, fn($n) => $n < 10)), count($ns)) . '%</strong> van de bezoeken.</p>'
    . '<h3>Vaakst in de top 10</h3>' . balken($tel(fn($r) => $r['top'] ?? []), 0, fn($k) => $namen[$k] ?? $k, 20)
    . '<h3>Vaakst bekeken gemeentepagina\'s</h3>' . balken($gemeenteBekeken, 0, fn($k) => $namen[$k] ?? $k, 20) . '</section>';

// gebruik
$actieLabel = ['meer' => 'Volgende 10 bekeken', 'pdf' => 'Pdf gedownload', 'kaart' => 'Kaart geopend', 'gemeente' => 'Gemeentepagina geopend',
    'deel' => 'Deellink gekopieerd', 'gedeeld' => 'Binnengekomen via een gedeelde link'];
$h .= '<section class="st-blok"><h2>Wat doen bezoekers bij de uitslag?</h2>' . balken(array_map('count', $acties), $nBezoek, fn($k) => $actieLabel[$k] ?? $k) . '</section>';
$h .= '<section class="st-blok"><h2>Apparaat</h2>' . balken(array_map('count', $apparaat), $nBezoek, fn($k) => ucfirst($k)) . '</section>';
$h .= '<section class="st-blok"><h2>Herkomst</h2>' . balken(array_map('count', $herkomst), $nBezoek, fn($k) => $k, 20) . '</section>';

pagina('Statistiek Italië-Verkenner', $h);
