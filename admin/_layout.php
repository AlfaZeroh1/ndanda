<?php
/** Admin layout helpers. Every admin page: require bootstrap, require_admin(), admin_header(), ..., admin_footer(). */
function admin_header(string $title, string $active = ''): void
{
    $u = current_user();
    $unread = (int) val('SELECT COUNT(*) FROM messages WHERE is_read = 0');
    $pendingTributes = (int) val("SELECT COUNT(*) FROM tributes WHERE status = 'pending'");
    $groups = [
        'Overview' => ['index' => ['Dashboard', 'bi-speedometer2', 'index.php']],
        'Funerals' => [
            'funerals' => ['Funeral accounts', 'bi-flower1', 'funerals.php'],
            'tributes' => ['Tributes', 'bi-chat-heart', 'tributes.php', $pendingTributes],
            'users'    => ['Users', 'bi-people', 'users.php'],
        ],
        'Revenue' => [
            'orders'       => ['Orders', 'bi-receipt', 'orders.php'],
            'transactions' => ['M-Pesa transactions', 'bi-phone', 'transactions.php'],
            'services'     => ['Services & pricing', 'bi-tags', 'services.php'],
        ],
        'Content' => [
            'pages'    => ['Website pages', 'bi-file-richtext', 'pages.php'],
            'posts'    => ['Blog', 'bi-journal-text', 'posts.php'],
            'messages' => ['Inbox', 'bi-inbox', 'messages.php', $unread],
        ],
        'System' => [
            'settings' => ['Settings & M-Pesa', 'bi-gear', 'settings.php'],
            'golive'   => ['Go live / clean data', 'bi-rocket-takeoff', 'go-live.php'],
        ],
    ];
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Admin · <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<?= $GLOBALS['adminHead'] ?? '' ?>
<script>window.APP = {base: <?= json_encode(rtrim(BASE_URL, '/')) ?>, csrf: <?= json_encode(csrf_token()) ?>};</script>
</head><body class="admin-body">
<aside class="admin-side p-3">
  <a href="<?= e(url('admin/')) ?>" class="d-flex align-items-center gap-2 text-decoration-none mb-3 px-2"><span class="brand-mark"><i class="bi bi-flower2"></i></span><span class="text-white fw-semibold">Admin<br><small class="text-secondary fw-normal"><?= e(setting('site_name')) ?></small></span></a>
  <?php foreach ($groups as $g => $items): ?>
    <div class="side-label"><?= e($g) ?></div>
    <?php foreach ($items as $k => $it): ?>
      <a href="<?= e(url('admin/' . $it[2])) ?>" class="nav-link<?= $active === $k ? ' active' : '' ?>"><i class="bi <?= $it[1] ?>"></i> <?= e($it[0]) ?><?php if (!empty($it[3])): ?><span class="badge rounded-pill text-bg-danger ms-auto"><?= (int) $it[3] ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  <?php endforeach; ?>
  <div class="side-label">Site</div>
  <a href="<?= e(url()) ?>" target="_blank" class="nav-link"><i class="bi bi-box-arrow-up-right"></i> View website</a>
  <a href="<?= e(url('logout.php')) ?>" class="nav-link"><i class="bi bi-box-arrow-right"></i> Log out</a>
</aside>
<div class="admin-main">
  <div class="admin-top d-flex align-items-center gap-3">
    <button class="btn btn-light d-lg-none" data-toggle-side><i class="bi bi-list"></i></button>
    <h1 class="h5 mb-0 me-auto"><?= e($title) ?></h1>
    <?php if (test_flag()): ?><a href="<?= e(url('admin/go-live.php')) ?>" class="badge text-bg-warning text-decoration-none"><i class="bi bi-cone-striped"></i> TEST MODE</a><?php else: ?><span class="badge text-bg-success"><i class="bi bi-broadcast"></i> LIVE</span><?php endif; ?>
    <span class="badge text-bg-<?= setting('mpesa_env') === 'live' ? 'success' : 'secondary' ?>">M-Pesa: <?= e(setting('mpesa_env')) ?></span>
    <span class="small text-muted d-none d-md-inline"><i class="bi bi-person-circle"></i> <?= e($u['name']) ?></span>
  </div>
  <div class="p-3 p-lg-4">
  <?= flashes() ?>
<?php
}

function admin_footer(string $extra = ''): void
{
    ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?= $extra ?>
</body></html>
<?php
}

/** Streams rows as a CSV download. */
function csv_download(string $filename, array $header, array $data): void
{
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, $header);
    foreach ($data as $r) fputcsv($out, array_values($r));
    exit;
}
