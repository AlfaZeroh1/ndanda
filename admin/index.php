<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
$k = [
    'revenue'   => (float) val("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status = 'paid'"),
    'month'     => (float) val("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status = 'paid' AND paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
    'pending'   => (float) val("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status = 'pending'"),
    'funerals'  => (int) val("SELECT COUNT(*) FROM funerals WHERE status = 'active'"),
    'awaiting'  => (int) val("SELECT COUNT(*) FROM funerals WHERE status = 'pending_payment'"),
    'tributes'  => (int) val('SELECT COUNT(*) FROM tributes'),
    'users'     => (int) val("SELECT COUNT(*) FROM users WHERE role = 'planner'"),
    'unmatched' => (int) val("SELECT COUNT(*) FROM mpesa_transactions WHERE type = 'c2b' AND order_id IS NULL"),
];
$months = [];
for ($i = 11; $i >= 0; $i--) $months[date('Y-m', strtotime("first day of -$i month"))] = 0;
foreach (rows("SELECT DATE_FORMAT(paid_at, '%Y-%m') m, SUM(amount) t FROM orders WHERE status = 'paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY m") as $r) {
    if (isset($months[$r['m']])) $months[$r['m']] = (float) $r['t'];
}
$bySvc = rows("SELECT oi.name, COUNT(*) c, SUM(oi.price) t FROM order_items oi JOIN orders o ON o.id = oi.order_id AND o.status = 'paid' GROUP BY oi.name ORDER BY t DESC");
$recentOrders = rows('SELECT o.*, u.name AS customer FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 8');
$recentFunerals = rows('SELECT f.*, u.name AS owner FROM funerals f JOIN users u ON u.id = f.user_id ORDER BY f.created_at DESC LIMIT 6');
admin_header('Dashboard', 'index');
?>
<?php if (test_flag()): ?><div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2"><div><i class="bi bi-cone-striped"></i> <strong>Test mode is on.</strong> Everything created now is flagged as test data. When you are ready, clean it up and switch to live.</div><a href="go-live.php" class="btn btn-sm btn-dark">Go live</a></div><?php endif; ?>
<?php if (!Mpesa::isConfigured()): ?><div class="alert alert-info"><i class="bi bi-info-circle"></i> M-Pesa Daraja keys are not set yet. Payments can be simulated in test mode. <a href="settings.php?tab=mpesa">Configure M-Pesa →</a></div><?php endif; ?>
<?php if ($k['unmatched']): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $k['unmatched'] ?> Paybill payment(s) could not be matched to an order. <a href="transactions.php?unmatched=1">Reconcile →</a></div><?php endif; ?>
<div class="row g-3 mb-4">
  <?php foreach ([
      ['Total revenue', money($k['revenue']), 'bi-cash-stack', '#e9f6e8', '#2f8f2b'],
      ['This month', money($k['month']), 'bi-calendar-check', '#fff5e0', '#b8913a'],
      ['Pending payments', money($k['pending']), 'bi-hourglass-split', '#fdecec', '#c0392b'],
      ['Active funerals', number_format($k['funerals']) . ' <small class="text-muted fs-6">+' . $k['awaiting'] . ' awaiting</small>', 'bi-flower1', '#eef1fb', '#3b5bdb'],
      ['Tributes written', number_format($k['tributes']), 'bi-chat-heart', '#f6eefb', '#8e44ad'],
      ['Planners', number_format($k['users']), 'bi-people', '#eef7f7', '#16a085'],
  ] as [$label, $num, $icon, $bg, $fg]): ?>
  <div class="col-6 col-xl-2"><div class="kpi"><div class="kpi-icon mb-2" style="background:<?= $bg ?>;color:<?= $fg ?>"><i class="bi <?= $icon ?>"></i></div><div class="kpi-num"><?= $num ?></div><div class="kpi-label"><?= $label ?></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-4 mb-4">
  <div class="col-xl-8"><div class="admin-card card h-100"><div class="card-header">Revenue – last 12 months</div><div class="card-body"><canvas id="revChart" height="110"></canvas></div></div></div>
  <div class="col-xl-4"><div class="admin-card card h-100"><div class="card-header">Revenue by service</div><div class="card-body">
    <?php if (!$bySvc): ?><div class="text-muted small">No paid orders yet.</div><?php endif; ?>
    <?php $max = max(array_column($bySvc, 't') ?: [1]); foreach ($bySvc as $s): ?>
      <div class="mb-2"><div class="d-flex justify-content-between small"><span><?= e($s['name']) ?> <span class="text-muted">×<?= (int) $s['c'] ?></span></span><strong><?= e(money($s['t'])) ?></strong></div><div class="progress progress-thin"><div class="progress-bar" style="width:<?= round($s['t'] / $max * 100) ?>%;background:var(--gold)"></div></div></div>
    <?php endforeach; ?>
  </div></div></div>
</div>
<div class="row g-4">
  <div class="col-xl-7"><div class="admin-card card"><div class="card-header d-flex justify-content-between">Recent orders <a href="orders.php" class="small">All</a></div>
    <div class="table-responsive"><table class="table mb-0 small align-middle"><tbody>
    <?php foreach ($recentOrders as $o): ?><tr><td><strong><?= e($o['reference']) ?></strong><div class="text-muted"><?= e($o['customer']) ?></div></td><td><?= e(money($o['amount'])) ?></td><td><?= status_badge($o['status']) ?><?= $o['is_test'] ? ' <span class="badge text-bg-warning">test</span>' : '' ?></td><td class="text-muted"><?= e(time_ago($o['created_at'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
  <div class="col-xl-5"><div class="admin-card card"><div class="card-header d-flex justify-content-between">New funeral accounts <a href="funerals.php" class="small">All</a></div>
    <?php foreach ($recentFunerals as $f): ?><a href="funeral.php?id=<?= (int) $f['id'] ?>" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-reset"><?= portrait($f, 'rounded-circle', 40) ?><div class="flex-grow-1"><div class="fw-semibold small"><?= e($f['deceased_name']) ?></div><div class="text-muted small"><?= e($f['owner']) ?></div></div><?= status_badge($f['status']) ?></a><?php endforeach; ?>
  </div></div>
</div>
<?php
admin_footer('<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script><script>
new Chart(document.getElementById("revChart"), {type: "bar", data: {labels: ' . json_encode(array_map(fn($m) => date('M y', strtotime($m . '-01')), array_keys($months))) . ', datasets: [{label: "Revenue (KES)", data: ' . json_encode(array_values($months)) . ', backgroundColor: "#b8913a", borderRadius: 8}]}, options: {plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true, ticks: {callback: v => v.toLocaleString()}}}}});
</script>');
