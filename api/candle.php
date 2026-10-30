<?php
/** "Light a candle" on a tribute page – one per visitor session per funeral. */
require __DIR__ . '/../includes/bootstrap.php';
if (!is_post()) json_out(['ok' => false], 405);
$f = row("SELECT id, candles FROM funerals WHERE id = ? AND status = 'active'", [(int) input('id')]);
if (!$f) json_out(['ok' => false], 404);
if (!empty($_SESSION['candles'][$f['id']])) json_out(['ok' => true, 'candles' => (int) $f['candles'], 'already' => true]);
$_SESSION['candles'][$f['id']] = 1;
q('UPDATE funerals SET candles = candles + 1 WHERE id = ?', [$f['id']]);
json_out(['ok' => true, 'candles' => (int) $f['candles'] + 1]);
