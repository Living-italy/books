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
 * Alle comuni.
 * Met opcache (shared memory, gewone webserver) wordt comuni.json één keer omgezet naar comuni.cache.php;
 * opcache houdt de tabel dan in het geheugen en een verzoek kost vrijwel niets.
 * Zonder opcache is gewoon json_decode zuiniger (ca. 70 MB en 0,1 s voor heel Italië).
 */
function comuni(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $json = DATA_DIR . '/comuni.json';
    $php = DATA_DIR . '/comuni.cache.php';
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
