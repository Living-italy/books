<?php
declare(strict_types=1);
require __DIR__ . '/lib/data.php';
$lang = 'nl';
$t = taal($lang);
$cfg = [];
foreach (['criteria', 'profielen', 'bronnen', 'aandachtspunten', 'regime7', 'trends', 'gids_links', 'gids_prijzen', 'sciolti_mafia', 'luchthavens', 'eigenschappen'] as $n) {
    $cfg[$n] = json_decode((string)file_get_contents(__DIR__ . "/config/$n.json"), true);
}
$cfg['bron_van'] = meta()['bron_van'] ?? [];
$cfg['zonder_data'] = meta()['zonder_data'] ?? [];
$v = @filemtime(__DIR__ . '/data/comuni-lite.json') ?: time();
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$j = fn($x) => json_encode($x, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?><!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($t['meta']['titel']) ?></title>
<meta name="description" content="<?= $e($t['meta']['ondertitel']) ?>">
<link rel="stylesheet" href="assets/style.css?v=<?= (int)@filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body>
<main id="app" class="pv" aria-live="polite">
  <header class="pv-kop"><h1><?= $e($t['meta']['titel']) ?></h1><p class="pv-kop-sub"><?= $e($t['meta']['kop_sub']) ?></p></header>
  <p class="pv-laden"><?= $e($t['meta']['laden']) ?></p>
  <noscript><p>Deze plaatsvinder heeft JavaScript nodig.</p></noscript>
</main>
<script>
window.PV = {
  taal: <?= $j($t) ?>,
  cfg: <?= $j($cfg) ?>,
  lang: <?= $j($lang) ?>,
  lite: "data/comuni-lite.json?v=<?= (int)$v ?>",
  kaart: "data/kaart.json?v=<?= (int)@filemtime(__DIR__ . "/data/kaart.json") ?>",
  score: "score.php",
  gemeente: "gemeente.php"
};
</script>
<script src="assets/app.js?v=<?= (int)@filemtime(__DIR__ . '/assets/app.js') ?>"></script>
</body>
</html>
