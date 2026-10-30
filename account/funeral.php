<?php
/**
 * Funeral account dashboard. Each tab lives in account/tabs/{tab}.php and is included twice:
 * once with $mode = 'post' to handle form actions (must redirect), then with $mode = 'view' to render.
 */
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$f = managed_funeral((int) input('id'));
$fid = (int) $f['id'];

// tab => [label, icon, services that unlock it (any), null = always available]
$tabs = [
    'overview'      => ['Overview', 'bi-grid', null],
    'details'       => ['Tribute details', 'bi-person-vcard', null],
    'gallery'       => ['Photo gallery', 'bi-images', ['tribute_page']],
    'tributes'      => ['Tributes', 'bi-chat-heart', ['tribute_page']],
    'programme'     => ['Programme & venues', 'bi-signpost-split', ['programme']],
    'livestream'    => ['Live stream', 'bi-broadcast', ['livestream']],
    'branding'      => ['Branding', 'bi-palette', null],
    'qr'            => ['QR codes & prints', 'bi-qr-code', ['qr_code', 'qr_plaque', 'memorial_print', 'programme']],
    'contributions' => ['Contributions', 'bi-cash-coin', null],
    'preparations'  => ['Preparations', 'bi-folder2-open', ['preparations']],
    'billing'       => ['Services & billing', 'bi-receipt', null],
];
$tab = array_key_exists(input('tab'), $tabs) ? input('tab') : 'overview';
$need = $tabs[$tab][2];
$locked = $need !== null && !array_filter($need, fn($c) => has_service($f, $c));
$base = url('account/funeral.php?id=' . $fid);
function tab_url(string $t, string $extra = ''): string
{
    global $base;
    return $base . '&tab=' . $t . $extra;
}

if (is_post()) {
    if ($locked) {
        flash('warning', 'Unlock this feature first.');
        redirect(tab_url('billing'));
    }
    $mode = 'post';
    try {
        require __DIR__ . '/tabs/' . $tab . '.php';
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    }
    redirect(tab_url($tab));
}

if (input('paid')) flash('success', 'Payment received — your services are now active. Let\'s build a beautiful tribute.');
$pendingTributes = (int) val("SELECT COUNT(*) FROM tributes WHERE funeral_id = ? AND status = 'pending'", [$fid]);
$pendingOrder = row("SELECT * FROM orders WHERE funeral_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1", [$fid]);
$pageTitle = $tabs[$tab][0] . ' · ' . $f['deceased_name'];
$hideFooter = true;
$bodyClass = 'dash';
$deferFlash = true;
require __DIR__ . '/../includes/header.php';
?>
<div class="dash-head py-3">
  <div class="container-fluid px-lg-4 d-flex flex-wrap align-items-center gap-3">
    <?= portrait($f, 'rounded-circle', 52) ?>
    <div class="me-auto">
      <div class="small-muted"><a href="<?= e(url('account/')) ?>" class="text-decoration-none">My accounts</a> /</div>
      <h1 class="h4 mb-0"><?= e($f['deceased_name']) ?> <?= status_badge($f['status']) ?></h1>
    </div>
    <?php if ($f['status'] === 'pending_payment' && $pendingOrder): ?>
      <a href="<?= e(url('checkout.php?ref=' . $pendingOrder['reference'])) ?>" class="btn btn-mpesa rounded-pill"><i class="bi bi-phone"></i> Pay <?= e(money($pendingOrder['amount'])) ?> to activate</a>
    <?php endif; ?>
    <a href="<?= e(tribute_url($f)) ?>" target="_blank" class="btn btn-outline-ink rounded-pill btn-sm"><i class="bi bi-box-arrow-up-right"></i> View tribute page</a>
    <button class="btn btn-ink rounded-pill btn-sm d-lg-none" data-bs-toggle="collapse" data-bs-target="#dashNav"><i class="bi bi-list"></i> Menu</button>
  </div>
</div>
<div class="container-fluid">
  <div class="row">
    <aside class="col-lg-3 col-xl-2 dash-side p-3 collapse d-lg-block" id="dashNav">
      <nav class="nav flex-column">
        <?php foreach ($tabs as $k => [$label, $icon, $req]):
            $isLocked = $req !== null && !array_filter($req, fn($c) => has_service($f, $c)); ?>
          <a class="nav-link<?= $k === $tab ? ' active' : '' ?>" href="<?= e(tab_url($k)) ?>"><i class="bi <?= $icon ?>"></i> <?= e($label) ?>
            <?php if ($k === 'tributes' && $pendingTributes): ?><span class="badge rounded-pill text-bg-danger ms-auto"><?= $pendingTributes ?></span><?php endif; ?>
            <?php if ($isLocked): ?><i class="bi bi-lock-fill"></i><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="mt-4 p-3 rounded-4 bg-cream small">
        <div class="fw-semibold mb-1"><i class="bi bi-headset text-gold"></i> Need help?</div>
        Call or WhatsApp <a href="https://wa.me/<?= e(setting('company_whatsapp')) ?>" target="_blank"><?= e(setting('company_phone')) ?></a>
      </div>
    </aside>
    <main class="col-lg-9 col-xl-10 p-3 p-lg-4">
      <?= flashes() ?>
      <?php if ($locked):
          $svcs = rows('SELECT * FROM services WHERE code IN (' . implode(',', array_fill(0, count($need), '?')) . ') AND is_active = 1', $need); ?>
        <div class="locked-box">
          <i class="bi bi-lock"></i>
          <h2 class="h4 mt-3"><?= e($tabs[$tab][0]) ?> is not part of your package yet</h2>
          <p class="text-secondary">Unlock it with <?= e(implode(' or ', array_column($svcs, 'name'))) ?>.</p>
          <form method="post" action="<?= e(tab_url('billing')) ?>">
            <?= csrf_field() ?><input type="hidden" name="act" value="buy">
            <?php foreach (array_slice($svcs, 0, 1) as $s): ?><input type="hidden" name="services[]" value="<?= (int) $s['id'] ?>"><button class="btn btn-gold rounded-pill px-4">Unlock <?= e($s['name']) ?> · <?= e(money($s['price'])) ?></button><?php endforeach; ?>
          </form>
          <a href="<?= e(tab_url('billing')) ?>" class="small d-inline-block mt-3">See all services</a>
        </div>
      <?php else:
          $mode = 'view';
          require __DIR__ . '/tabs/' . $tab . '.php';
      endif; ?>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php';
