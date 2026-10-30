<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$funerals = rows("SELECT f.*,
    (SELECT COUNT(*) FROM tributes t WHERE t.funeral_id = f.id) AS tribute_count,
    (SELECT COUNT(*) FROM tributes t WHERE t.funeral_id = f.id AND t.status = 'pending') AS pending_count,
    (SELECT reference FROM orders o WHERE o.funeral_id = f.id AND o.status = 'pending' ORDER BY o.id DESC LIMIT 1) AS pending_ref
    FROM funerals f WHERE f.user_id = ? ORDER BY f.created_at DESC", [$user['id']]);
$pageTitle = 'My funeral accounts';
require __DIR__ . '/../includes/header.php';
?>
<section class="bg-cream section-sm" style="min-height:70vh">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div><h1 class="h2 mb-0">My funeral accounts</h1><div class="small-muted">Welcome, <?= e($user['name']) ?></div></div>
      <a href="<?= e(url('create.php')) ?>" class="btn btn-gold rounded-pill px-4"><i class="bi bi-plus-lg"></i> New funeral account</a>
    </div>
    <?php if (!$funerals): ?>
      <div class="card-soft empty"><i class="bi bi-flower1"></i><p>You have not created a funeral account yet.</p><a href="<?= e(url('create.php')) ?>" class="btn btn-ink rounded-pill">Create one now</a></div>
    <?php endif; ?>
    <div class="row g-4">
      <?php foreach ($funerals as $f): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card-soft h-100 overflow-hidden">
          <div style="height:70px;background:linear-gradient(135deg,<?= e(valid_hex($f['brand_primary'], '#1f2937')) ?>,<?= e(valid_hex($f['brand_accent'], '#b8913a')) ?>)"></div>
          <div class="p-4 pt-0 text-center">
            <div style="margin-top:-40px"><?= portrait($f, 'rounded-circle border border-4 border-white', 80) ?></div>
            <h2 class="h5 mt-2 mb-0"><?= e($f['deceased_name']) ?></h2>
            <div class="small-muted mb-2"><?= e(life_years($f)) ?></div>
            <?= status_badge($f['status']) ?>
            <?php if ($f['pending_count']): ?><span class="badge rounded-pill text-bg-info"><?= (int) $f['pending_count'] ?> tributes to review</span><?php endif; ?>
            <div class="d-flex justify-content-center gap-3 small text-muted my-3"><span><i class="bi bi-eye"></i> <?= (int) $f['views'] ?></span><span><i class="bi bi-chat-heart"></i> <?= (int) $f['tribute_count'] ?></span><span><i class="bi bi-fire"></i> <?= (int) $f['candles'] ?></span></div>
            <div class="d-grid gap-2">
              <a href="<?= e(url('account/funeral.php?id=' . $f['id'])) ?>" class="btn btn-ink rounded-pill">Manage</a>
              <?php if ($f['status'] === 'pending_payment' && $f['pending_ref']): ?><a href="<?= e(url('checkout.php?ref=' . $f['pending_ref'])) ?>" class="btn btn-mpesa rounded-pill">Complete payment</a>
              <?php else: ?><a href="<?= e(tribute_url($f)) ?>" target="_blank" class="btn btn-outline-ink rounded-pill">View page</a><?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php';
