<?php
/* ---------------------------------------------------------------
 * Database
 * ------------------------------------------------------------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET time_zone = '+03:00'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute(array_values($params));
    return $st;
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r ?: null;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update(string $table, array $data, string $where, array $whereParams = []): int
{
    $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
    return q("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams))->rowCount();
}

/* ---------------------------------------------------------------
 * Settings
 * ------------------------------------------------------------- */
function setting(string $key, string $default = ''): string
{
    if (!isset($GLOBALS['__settings'])) {
        $GLOBALS['__settings'] = [];
        foreach (rows('SELECT skey, svalue FROM settings') as $r) {
            $GLOBALS['__settings'][$r['skey']] = (string) $r['svalue'];
        }
    }
    return $GLOBALS['__settings'][$key] ?? $default;
}

function set_setting(string $key, $value): void
{
    q('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$key, (string) $value]);
    $GLOBALS['__settings'][$key] = (string) $value;
}

/** While test mode is on, every record created is flagged so Go Live can remove it. */
function test_flag(): int
{
    return setting('test_mode', '1') === '1' ? 1 : 0;
}

/* ---------------------------------------------------------------
 * Output & URLs
 * ------------------------------------------------------------- */
function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = __DIR__ . '/../assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . $path) . '?v=' . $v;
}

/** Resolves a stored image (relative upload path or full URL). */
function img(?string $path, string $fallback = ''): string
{
    if (!$path) return $fallback;
    if (preg_match('#^https?://#i', $path)) return $path;
    return url($path);
}

function pretty(): bool
{
    return setting('pretty_urls', '1') === '1';
}

function tribute_url(array $f): string
{
    return pretty() ? url('t/' . rawurlencode($f['slug'])) : url('tribute.php?s=' . rawurlencode($f['slug']));
}

function programme_url(array $f): string
{
    return pretty() ? url('p/' . rawurlencode($f['slug'])) : url('programme.php?s=' . rawurlencode($f['slug']));
}

function form_url(array $f): string
{
    return pretty() ? url('f/' . rawurlencode($f['form_token'])) : url('tribute-form.php?t=' . rawurlencode($f['form_token']));
}

function post_url(array $p): string
{
    return pretty() ? url('blog/' . rawurlencode($p['slug'])) : url('post.php?s=' . rawurlencode($p['slug']));
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function back(string $fallback = ''): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref && strpos($ref, rtrim(BASE_URL, '/')) === 0) redirect($ref);
    redirect($fallback ?: url());
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function json_out(array $data, int $code = 200): void
{
    while (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $icon = ['success' => 'check-circle', 'danger' => 'exclamation-octagon', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'][$f['type']] ?? 'info-circle';
        $out .= '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show d-flex gap-2 align-items-start" role="alert"><i class="bi bi-' . $icon . ' mt-1"></i><div>' . e($f['msg']) . '</div><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if (!is_post()) return;
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), (string) $t)) {
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'json') !== false) {
            json_out(['ok' => false, 'message' => 'Your session expired. Please refresh the page.'], 419);
        }
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}

/* ---------------------------------------------------------------
 * Auth
 * ------------------------------------------------------------- */
function current_user(): ?array
{
    if (empty($_SESSION['uid'])) return null;
    if (!array_key_exists('__user', $GLOBALS)) {
        $GLOBALS['__user'] = row("SELECT * FROM users WHERE id = ? AND status = 'active'", [(int) $_SESSION['uid']]);
    }
    return $GLOBALS['__user'];
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    unset($GLOBALS['__user']);
    q('UPDATE users SET last_login = NOW() WHERE id = ?', [$u['id']]);
}

function safe_next(string $next, string $fallback): string
{
    // only allow local paths to avoid open redirects
    if ($next !== '' && $next[0] === '/' && substr($next, 0, 2) !== '//') {
        $scheme = parse_url(BASE_URL);
        return ($scheme['scheme'] ?? 'http') . '://' . ($scheme['host'] ?? '') . (isset($scheme['port']) ? ':' . $scheme['port'] : '') . $next;
    }
    return $fallback;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('warning', 'Please log in to continue.');
        redirect(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '')));
    }
    return $u;
}

function require_admin(): array
{
    $u = current_user();
    if (!$u) {
        redirect(url('login.php?admin=1&next=' . urlencode($_SERVER['REQUEST_URI'] ?? '')));
    }
    if ($u['role'] !== 'admin') {
        // Logged in as a planner: sending them back to login would loop forever
        flash('warning', 'That area is for administrators only. You are logged in as ' . $u['email'] . '.');
        redirect(url('account/'));
    }
    return $u;
}

/* ---------------------------------------------------------------
 * Formatting helpers
 * ------------------------------------------------------------- */
function money($n): string
{
    return setting('currency', 'KES') . ' ' . number_format((float) $n);
}

function fmt_date(?string $d, string $format = 'j M Y'): string
{
    if (!$d || strpos($d, '0000') === 0) return '';
    return date($format, strtotime($d));
}

function life_years(array $f): string
{
    $a = $f['birth_date'] ? date('Y', strtotime($f['birth_date'])) : '';
    $b = $f['death_date'] ? date('Y', strtotime($f['death_date'])) : '';
    return trim($a . ($a || $b ? ' – ' : '') . $b);
}

function time_ago(string $dt): string
{
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . ' min ago';
    if ($d < 86400) return floor($d / 3600) . ' hr ago';
    if ($d < 604800) return floor($d / 86400) . ' days ago';
    return fmt_date($dt);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim(preg_replace('/^(dr|hon|mr|mrs|ms|prof|rev)\.?\s+/i', '', $name)));
    $i = '';
    foreach (array_slice($parts, 0, 2) as $p) $i .= mb_strtoupper(mb_substr($p, 0, 1));
    return $i ?: '?';
}

function excerpt(?string $text, int $len = 140): string
{
    $t = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
    return mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : $t;
}

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s), '-'));
    return $s ?: 'item';
}

function unique_slug(string $table, string $base, int $ignoreId = 0): string
{
    $slug = $base = substr(slugify($base), 0, 140);
    $i = 2;
    while (val("SELECT id FROM `$table` WHERE slug = ? AND id <> ?", [$slug, $ignoreId])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function normalize_phone(?string $p): ?string
{
    $p = preg_replace('/\D+/', '', (string) $p);
    if (preg_match('/^0([17]\d{8})$/', $p, $m)) return '254' . $m[1];
    if (preg_match('/^254([17]\d{8})$/', $p)) return $p;
    if (preg_match('/^([17]\d{8})$/', $p, $m)) return '254' . $m[1];
    return null;
}

function valid_hex(?string $c, string $fallback): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $c) ? strtolower($c) : $fallback;
}

/** Fonts a planner can pick for their branded pages (all on Google Fonts). */
function brand_fonts(): array
{
    return [
        'Playfair Display' => 'serif', 'Cormorant Garamond' => 'serif', 'Lora' => 'serif',
        'Libre Baskerville' => 'serif', 'EB Garamond' => 'serif', 'Merriweather' => 'serif',
        'Montserrat' => 'sans-serif', 'Poppins' => 'sans-serif', 'Great Vibes' => 'cursive',
    ];
}

function brand_font_href(array $f): string
{
    $font = array_key_exists($f['brand_font'] ?? '', brand_fonts()) ? $f['brand_font'] : 'Playfair Display';
    return 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $font) . ':wght@400;600;700&display=swap';
}

/** CSS custom properties for a branded funeral page. */
function brand_style(array $f): string
{
    $fonts = brand_fonts();
    $font = array_key_exists($f['brand_font'] ?? '', $fonts) ? $f['brand_font'] : 'Playfair Display';
    return '--brand:' . valid_hex($f['brand_primary'] ?? '', '#1f2937') . ';--brand-accent:' . valid_hex($f['brand_accent'] ?? '', '#b8913a') . ";--brand-font:'" . $font . "'," . $fonts[$font] . ';';
}

/* ---------------------------------------------------------------
 * Uploads
 * ------------------------------------------------------------- */
const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
const DOC_EXTS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'jpg', 'jpeg', 'png', 'webp'];

/**
 * Saves an uploaded file. $kind = 'image' (public) or 'doc' (stored under uploads/private, served via download.php).
 * Returns the relative path, or null if no file was sent. Throws RuntimeException on invalid files.
 */
function upload_file(?array $file, string $subdir, string $kind = 'image'): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (error ' . $file['error'] . '). The file may be too large.');
    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) throw new RuntimeException('File is larger than ' . MAX_UPLOAD_MB . 'MB.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($kind === 'image') {
        if (!isset(IMAGE_TYPES[$mime])) throw new RuntimeException('Please upload a JPG, PNG, WEBP or GIF image.');
        $ext = IMAGE_TYPES[$mime];
        $base = 'uploads/' . trim($subdir, '/');
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, DOC_EXTS, true)) throw new RuntimeException('Allowed documents: ' . implode(', ', DOC_EXTS));
        if (preg_match('#(php|html|javascript|x-sh|x-executable)#i', $mime)) throw new RuntimeException('This file type is not allowed.');
        $base = 'uploads/private/' . trim($subdir, '/');
    }
    $dir = __DIR__ . '/../' . $base;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Could not create upload folder.');
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not save the uploaded file.');
    return $base . '/' . $name;
}

/** Turns a multi-file input ($_FILES['x'] with name="x[]") into a list of single-file arrays. */
function files_list(string $field): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f || !is_array($f['name'])) return $f ? [$f] : [];
    $out = [];
    foreach ($f['name'] as $i => $n) {
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

function delete_upload(?string $path): void
{
    if ($path && strpos($path, 'uploads/') === 0 && strpos($path, '..') === false) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) @unlink($full);
    }
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..' || $item === '.htaccess') continue;
        $p = $dir . '/' . $item;
        if (is_dir($p)) {
            rrmdir($p);
            @rmdir($p);
        } else {
            @unlink($p);
        }
    }
}

/* ---------------------------------------------------------------
 * Funerals
 * ------------------------------------------------------------- */
/** Loads a funeral the current user may manage (owner or admin), or 404s. */
function managed_funeral(int $id): array
{
    $u = require_login();
    $f = row('SELECT * FROM funerals WHERE id = ?', [$id]);
    if (!$f || ((int) $f['user_id'] !== (int) $u['id'] && $u['role'] !== 'admin')) {
        http_response_code(404);
        exit('Funeral account not found.');
    }
    return $f;
}

function can_manage(array $f): bool
{
    $u = current_user();
    return $u && ((int) $f['user_id'] === (int) $u['id'] || $u['role'] === 'admin');
}

function funeral_codes(int $fid): array
{
    static $cache = [];
    if (!isset($cache[$fid])) {
        $cache[$fid] = array_column(rows(
            'SELECT s.code FROM services s LEFT JOIN funeral_services fs ON fs.service_id = s.id AND fs.funeral_id = ? WHERE fs.funeral_id IS NOT NULL OR s.is_standard = 1',
            [$fid]
        ), 'code');
    }
    return $cache[$fid];
}

function has_service(array $f, string $code): bool
{
    return in_array($code, funeral_codes((int) $f['id']), true);
}

function service_by_code(string $code): ?array
{
    return row('SELECT * FROM services WHERE code = ?', [$code]);
}

function status_badge(string $status): string
{
    $map = [
        'active' => 'success', 'paid' => 'success', 'success' => 'success', 'approved' => 'success', 'received' => 'success', 'published' => 'success',
        'pending' => 'warning', 'pending_payment' => 'warning', 'pledged' => 'warning', 'draft' => 'secondary',
        'failed' => 'danger', 'suspended' => 'danger', 'cancelled' => 'secondary', 'timeout' => 'secondary', 'hidden' => 'secondary', 'archived' => 'secondary',
    ];
    return '<span class="badge rounded-pill text-bg-' . ($map[$status] ?? 'secondary') . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

function page(string $slug): ?array
{
    return row('SELECT * FROM pages WHERE slug = ? AND is_published = 1', [$slug]);
}

/* ---------------------------------------------------------------
 * Mail (best effort; works on most cPanel hosts)
 * ------------------------------------------------------------- */
function send_mail(string $to, string $subject, string $html): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $from = setting('mail_from', 'no-reply@' . (parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost'));
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: " . setting('site_name') . " <$from>\r\n";
    $body = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#222">' . $html . '<hr><small>' . e(setting('site_name')) . ' · ' . e(setting('company_phone')) . '</small></div>';
    return @mail($to, $subject, $body, $headers);
}

/* ---------------------------------------------------------------
 * Simple rate limiting (per session)
 * ------------------------------------------------------------- */
function throttle(string $key, int $seconds): bool
{
    $now = time();
    if (!empty($_SESSION['throttle'][$key]) && $now - $_SESSION['throttle'][$key] < $seconds) return false;
    $_SESSION['throttle'][$key] = $now;
    return true;
}

function paginate(int $total, int $perPage, int $page, string $baseUrl): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) return '';
    $sep = strpos($baseUrl, '?') === false ? '?' : '&';
    $h = '<nav><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $pages; $i++) {
        $h .= '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
    }
    return $h . '</ul></nav>';
}

/* ---------------------------------------------------------------
 * Shared view snippets
 * ------------------------------------------------------------- */
function portrait(array $f, string $class = 'tc-photo', int $size = 104): string
{
    if (!empty($f['photo'])) {
        return '<img src="' . e(img($f['photo'])) . '" alt="' . e($f['deceased_name']) . '" class="' . e($class) . ' object-cover" width="' . $size . '" height="' . $size . '" loading="lazy">';
    }
    return '<div class="' . e($class) . ' monogram" style="width:' . $size . 'px;height:' . $size . 'px;font-size:' . round($size / 2.6) . 'px">' . e(initials($f['deceased_name'])) . '</div>';
}

function tribute_card(array $f): string
{
    $count = (int) ($f['tribute_count'] ?? 0);
    return '<a class="tribute-card hover-lift" href="' . e(tribute_url($f)) . '" style="--c1:' . e(valid_hex($f['brand_primary'], '#1f2937')) . ';--c2:' . e(valid_hex($f['brand_accent'], '#b8913a')) . '">'
        . '<div class="tc-top"' . (!empty($f['cover']) ? ' style="background:url(' . e(img($f['cover'])) . ') center/cover"' : '') . '></div>'
        . portrait($f)
        . '<div class="tc-body"><small class="text-uppercase text-muted" style="letter-spacing:.14em;font-size:.68rem">' . e($f['heading']) . '</small>'
        . '<h3>' . e($f['deceased_name']) . '</h3><div class="tc-years">' . e(life_years($f)) . '</div>'
        . '<p class="tc-quote">' . e(excerpt($f['quote'] ?: $f['obituary'], 110)) . '</p>'
        . '<div class="tc-meta"><span><i class="bi bi-chat-heart"></i> ' . $count . ' tributes</span><span><i class="bi bi-fire"></i> ' . (int) $f['candles'] . ' candles</span></div>'
        . '</div></a>';
}

/** Public, active funerals with tribute counts. */
function public_funerals(string $extraWhere = '', array $params = [], int $limit = 12, int $offset = 0): array
{
    return rows("SELECT f.*, (SELECT COUNT(*) FROM tributes t WHERE t.funeral_id = f.id AND t.status = 'approved') AS tribute_count
        FROM funerals f WHERE f.status = 'active' AND f.visibility = 'public' $extraWhere
        ORDER BY f.is_featured DESC, f.created_at DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset, $params);
}
