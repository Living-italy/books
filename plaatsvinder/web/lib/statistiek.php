<?php
declare(strict_types=1);

/*
 * Anonieme statistiek: hoe bezoekers de Italië-Verkenner invullen.
 * Geen cookies, geen IP-adres, geen browsergegevens. Per bezoek een willekeurige code die alleen in de
 * sessionStorage van de browser staat (weg zodra het tabblad sluit), zodat stappen van één bezoek bij elkaar horen.
 * Opslag: data/statistiek/JJJJ-MM.jsonl, één regel per gebeurtenis. Bestanden ouder dan 24 maanden worden gewist.
 */

const STAT_DIR = __DIR__ . '/../data/statistiek';
const STAT_SOORTEN = ['start', 'stap', 'uitslag', 'meer', 'pdf', 'kaart', 'gemeente', 'deel', 'gedeeld'];
const STAT_MAX_BESTAND = 200 * 1024 * 1024;   // rem tegen misbruik: hoogstens 200 MB per maand
const STAT_BEWAAR_MAANDEN = 24;

function stat_sessie($s): ?string
{
    return is_string($s) && preg_match('/^[a-z0-9]{8,32}$/', $s) ? $s : null;
}

/** Keuzes compact: per eigenschap "n2" (noodzakelijk, niveau 3), "m1:6" (meewegen, niveau 2, gewicht 6) of "o" (onbelangrijk). */
function stat_keuzes(array $a, array $invoer): array
{
    $e = [];
    foreach ($a['keuzes'] as $id => $k) {
        $e[$id] = match ($k['status']) {
            'noodzakelijk' => 'n' . $k['niveau'],
            'onbelangrijk' => 'o',
            default => 'm' . $k['niveau'] . ':' . $k['gewicht'],
        };
    }
    $z = $a['alleen_provs'] ? 'provincies' : ($a['alleen_regios'] ? 'regios' : 'overal');
    return ['p' => $a['profiel'], 'v' => $a['verblijf'], 'z' => $z, 'r' => $a['alleen_regios'], 'pr' => $a['alleen_provs'],
        'l' => $a['ligging'], 'g' => $a['grootte'], 'e' => $e];
}

/** Eén gebeurtenis wegschrijven. Fouten zijn nooit zichtbaar voor de bezoeker. */
function stat_schrijf(array $r): void
{
    try {
        if (!is_dir(STAT_DIR) && !@mkdir(STAT_DIR, 0775, true)) {
            return;
        }
        if (!is_file(STAT_DIR . '/.htaccess')) {
            @file_put_contents(STAT_DIR . '/.htaccess', "Require all denied\n");
        }
        $pad = STAT_DIR . '/' . gmdate('Y-m') . '.jsonl';
        if (!is_file($pad)) {
            stat_opruimen();
        } elseif (filesize($pad) > STAT_MAX_BESTAND) {
            return;
        }
        $r = ['t' => gmdate('Y-m-d\TH')] + $r;   // alleen datum en uur
        @file_put_contents($pad, json_encode($r, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {
        error_log('italie-verkenner statistiek: ' . $e->getMessage());
    }
}

function stat_opruimen(): void
{
    $grens = gmdate('Y-m', strtotime('-' . STAT_BEWAAR_MAANDEN . ' months'));
    foreach (glob(STAT_DIR . '/*.jsonl') ?: [] as $f) {
        if (basename($f, '.jsonl') < $grens) {
            @unlink($f);
        }
    }
}

/** Gebeurtenissen lezen, voor de maanden JJJJ-MM in $maanden (leeg = alles). */
function stat_lees(array $maanden = []): array
{
    $uit = [];
    foreach (glob(STAT_DIR . '/*.jsonl') ?: [] as $f) {
        if ($maanden && !in_array(basename($f, '.jsonl'), $maanden, true)) {
            continue;
        }
        foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $regel) {
            $r = json_decode($regel, true);
            if (is_array($r) && isset($r['k'], $r['s'])) {
                $uit[] = $r;
            }
        }
    }
    return $uit;
}

function stat_maanden(): array
{
    $m = array_map(fn($f) => basename($f, '.jsonl'), glob(STAT_DIR . '/*.jsonl') ?: []);
    rsort($m);
    return $m;
}
