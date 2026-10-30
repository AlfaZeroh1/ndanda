<?php
require __DIR__ . '/includes/bootstrap.php';
$active = 'pricing';
$pageTitle = 'Pricing';
$metaDesc = 'Simple, transparent pricing. Pick only the services you need.';
$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort');
$paid = array_filter($services, fn($s) => $s['price'] > 0);
$all = array_sum(array_column($paid, 'price'));
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="eyebrow">Transparent pricing</div><h1>Pay only for what you need</h1><p>Every service is a simple add-on. Tick what you want when creating your funeral account, pay once with M-Pesa, and add more anytime.</p></div></section>
<section class="section">
  <div class="container" style="max-width:1080px">
    <div class="row g-4">
      <?php foreach ($services as $s): ?>
      <div class="col-md-6">
        <div class="card-soft p-4 h-100 d-flex gap-3">
          <div class="feature-icon flex-shrink-0"><i class="bi <?= e($s['icon']) ?>"></i></div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <h2 class="h5 mb-1"><?= e($s['name']) ?></h2>
              <span class="fw-bold text-nowrap"><?= $s['price'] > 0 ? e(money($s['price'])) : '<span class="badge text-bg-success">Free</span>' ?></span>
            </div>
            <p class="small text-secondary mb-0"><?= e($s['description']) ?></p>
            <?php if ($s['is_standard']): ?><span class="badge rounded-pill text-bg-light border mt-2">Included with every account</span><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="card-soft p-4 p-md-5 mt-5 text-center" style="background:linear-gradient(135deg,#fffaf0,#fff)">
      <div class="eyebrow">Complete digital funeral</div>
      <h2 class="mb-1">Everything: <?= e(money($all)) ?></h2>
      <p class="text-secondary"><?= count($paid) ?> services + the free preparations workspace. One-time payment, no subscription.</p>
      <a href="<?= e(url('create.php?all=1')) ?>" class="btn btn-gold btn-lg rounded-pill px-5">Start with everything</a>
      <a href="<?= e(url('create.php')) ?>" class="btn btn-outline-ink btn-lg rounded-pill px-4 ms-sm-2 mt-2 mt-sm-0">Build my own package</a>
    </div>
    <div class="row g-4 mt-4 text-center small text-secondary">
      <div class="col-md-4"><i class="bi bi-phone fs-3 text-success"></i><div class="mt-2">Pay via M-Pesa STK push or Paybill</div></div>
      <div class="col-md-4"><i class="bi bi-plus-circle fs-3 text-gold"></i><div class="mt-2">Add more services anytime from your dashboard</div></div>
      <div class="col-md-4"><i class="bi bi-building fs-3 text-gold"></i><div class="mt-2">Funeral homes & planners: <a href="<?= e(url('contact.php')) ?>">ask about volume rates</a></div></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
