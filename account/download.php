<?php
/** Streams a private preparations document to its owner (or an admin). */
require __DIR__ . '/../includes/bootstrap.php';
require_login();
$d = row('SELECT * FROM prep_documents WHERE id = ?', [(int) input('id')]);
if (!$d) { http_response_code(404); exit('Not found'); }
managed_funeral((int) $d['funeral_id']); // 404s if not allowed
$path = realpath(__DIR__ . '/../' . $d['file']);
$root = realpath(UPLOAD_DIR . '/private');
if (!$path || !$root || strpos($path, $root) !== 0 || !is_file($path)) { http_response_code(404); exit('File missing'); }
while (ob_get_level()) ob_end_clean();
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $d['original_name'] ?: basename($path)) . '"');
readfile($path);
