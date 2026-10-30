<?php
require __DIR__ . '/includes/bootstrap.php';
$active = 'obituaries';
$pageTitle = 'Obituaries & tributes';
$metaDesc = 'Recent obituaries and online tributes in Kenya.';
$qs = trim(input('q'));
$where = '';
$params = [];
if ($qs !== '') {
    $where = 'AND (f.deceased_name LIKE ? OR f.nickname LIKE ? OR f.birth_place LIKE ? OR f.resting_place LIKE ?)';
    $like = '%' . $qs . '%';
    $params = [$like, $like, $like, $like];
}
$perPage = 12;
$pg = max(1, (int) input('page', 1));
$total = (int) val("SELECT COUNT(*) FROM funerals f WHERE f.status = 'active' AND f.visibility = 'public' $where", $params);
$list = public_funerals($where, $params, $perPage, ($pg - 1) * $perPage);
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="eyebrow">Remembering</div><h1>Obituaries & tributes</h1><p>Celebrating the lives of loved ones across Kenya and beyond. Light a candle or leave a message of comfort.</p>
  <form class="mx-auto mt-4" style="max-width:520px"><div class="input-group input-group-lg"><input name="q" value="<?= e($qs) ?>" class="form-control rounded-start-pill" placeholder="Search by name or home town"><button class="btn btn-gold rounded-end-pill px-4"><i class="bi bi-search"></i></button></div></form>
</div></section>
<section class="section bg-cream">
  <div class="container">
    <?php if ($qs !== ''): ?><p class="text-muted"><?= $total ?> result<?= $total === 1 ? '' : 's' ?> for “<?= e($qs) ?>” · <a href="<?= e(url('obituaries.php')) ?>">clear</a></p><?php endif; ?>
    <?php if ($list): ?>
      <div class="row g-4"><?php foreach ($list as $f): ?><div class="col-sm-6 col-lg-4 col-xl-3"><?= tribute_card($f) ?></div><?php endforeach; ?></div>
      <div class="mt-5"><?= paginate($total, $perPage, $pg, url('obituaries.php' . ($qs !== '' ? '?q=' . urlencode($qs) : ''))) ?></div>
    <?php else: ?>
      <div class="empty"><i class="bi bi-search"></i>No tributes found.</div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
