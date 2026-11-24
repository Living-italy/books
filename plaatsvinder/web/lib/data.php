<?php
declare(strict_types=1);

const DATA_DIR = __DIR__ . '/../data';

/** Taalbestand (één bestand met alle teksten). */
function taal(string $lang = 'nl'): array
{
    static $cache = [];
    $lang = preg_replace('/[^a-z]/', '', $lang) ?: 'nl';
    if (!isset($cache[$lang])) {
        $pad = __DIR__ . "/../lang/$lang.json";
        if (!is_file($pad)) {
            $pad = __DIR__ . '/../lang/nl.json';
        }
        $cache[$lang] = json_decode((string)file_get_contents($pad), true, 512, JSON_THROW_ON_ERROR);
    }
    return $cache[$lang];
}

/**
 * Compacte tabel voor score.php (score.json). Met opcache wordt die één keer omgezet naar een PHP-bestand,
 * zodat opcache hem in het geheugen houdt; zonder opcache is json_decode zuiniger.
 */
function comuni(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $json = DATA_DIR . '/score.json';
    $php = DATA_DIR . '/score.cache.php';
    $opcache = PHP_SAPI !== 'cli' && function_exists('opcache_get_status') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN);
    if ($opcache && is_file($php) && filemtime($php) >= filemtime($json)) {
        $data = require $php;
        return $data;
    }
    $data = json_decode((string)file_get_contents($json), true, 512, JSON_THROW_ON_ERROR);
    if ($opcache) {
        $tmp = $php . '.' . getmypid();
        if (@file_put_contents($tmp, '<?php return ' . var_export($data, true) . ';') !== false) {
            @rename($tmp, $php);
        }
    }
    return $data;
}

/** Alle gegevens van één gemeente (uit data/detail/<provincie>.json). */
function gemeente(string $istat): ?array
{
    $istat = preg_replace('/\D/', '', $istat);
    foreach (comuni()['comuni'] as $c) {
        if ($c['istat'] === $istat) {
            $f = DATA_DIR . '/detail/' . preg_replace('/[^A-Z]/', '', $c['prov']) . '.json';
            $d = json_decode((string)@file_get_contents($f), true);
            return $d[$istat] ?? null;
        }
    }
    return null;
}

function meta(): array
{
    return json_decode((string)file_get_contents(DATA_DIR . '/meta.json'), true) ?? [];
}
