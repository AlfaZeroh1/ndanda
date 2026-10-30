<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
$f = row('SELECT f.*, u.name AS owner, u.email, u.phone, u.id AS uid FROM funerals f JOIN users u ON u.id = f.user_id WHERE f.id = ?', [(int) input('id')]);
if (!$f) { flash('danger', 'Not found'); redirect(url('admin/funerals.php')); }
$fid = (int) $f['id'];
if (is_post()) {
    $sid = (int) input('service_id');
    if (input('act') === 'grant') {
        q('INSERT IGNORE INTO funeral_services (funeral_id, service_id) VALUES (?, ?)', [$fid, $sid]);
        if ($f['status'] === 'pending_payment') q("UPDATE funerals SET status = 'active' WHERE id = ?", [$fid]);
        flash('success', 'Service granted (complimentary).');
    } elseif (input('act') === 'revoke') {
        q('DELETE FROM funeral_services WHERE funeral_id = ? AND service_id = ?', [$fid, $sid]);
        flash('success', 'Service removed.');
    }
    redirect(url('admin/funeral.php?id=' . $fid));
}
$services = rows('SELECT s.*, fs.order_id, fs.created_at AS owned_at FROM services s LEFT JOIN funeral_services fs ON fs.service_id = s.id AND fs.funeral_id = ? ORDER BY s.sort', [$fid]);
$orders = rows('SELECT * FROM orders WHERE funeral_id = ? ORDER BY created_at DESC', [$fid]);
$counts = [
    'Tributes' => val('SELECT COUNT(*) FROM tributes WHERE funeral_id = ?', [$fid]),
    'Photos' => val('SELECT COUNT(*) FROM gallery WHERE funeral_id = ?', [$fid]),
    'Programme events' => val('SELECT COUNT(*) FROM programme_events WHERE funeral_id = ?', [$fid]),
    'Documents' => val('SELECT COUNT(*) FROM prep_documents WHERE funeral_id = ?', [$fid]),
    'Contributions' => money(val('SELECT COALESCE(SUM(amount),0) FROM contributions WHERE funeral_id = ?', [$fid])),
];
admin_header($f['deceased_name'], 'funerals');
?>
<div class="row g-4">
  <div class="col-xl-4">
    <div class="admin-card card p-4 text-center mb-4">
      <div class="mx-auto"><?= portrait($f, 'rounded-circle', 96) ?></div>
      <h2 class="h5 mt-2 mb-0"><?= e($f['deceased_name']) ?></h2>
      <div class="text-muted small mb-2"><?= e(life_years($f)) ?></div>
      <div><?= status_badge($f['status']) ?> <?= $f['is_featured'] ? '<span class="badge text-bg-warning">Featured</span>' : '' ?> <?= $f['is_test'] ? '<span class="badge text-bg-warning">test</span>' : '' ?></div>
      <div class="d-grid gap-2 mt-3">
        <a href="<?= e(url('account/funeral.php?id=' . $fid)) ?>" class="btn btn-dark btn-sm"><i class="bi bi-pencil-square"></i> Open planner dashboard</a>
        <a href="<?= e(tribute_url($f)) ?>" target="_blank" class="btn btn-outline-dark btn-sm">View tribute page</a>
      </div>
      <hr>
      <form method="post" action="funerals.php" class="d-flex flex-wrap gap-1 justify-content-center"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $fid ?>">
        <?php if ($f['status'] !== 'active'): ?><button name="act" value="active" class="btn btn-sm btn-success">Activate</button><?php endif; ?>
        <?php if ($f['status'] !== 'suspended'): ?><button name="act" value="suspended" class="btn btn-sm btn-outline-danger">Suspend</button><?php endif; ?>
        <button name="act" value="feature" class="btn btn-sm btn-outline-warning"><?= $f['is_featured'] ? 'Unfeature' : 'Feature on home' ?></button>
      </form>
      <form method="post" action="funerals.php" class="mt-2" data-confirm="Delete this funeral account and all its content permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $fid ?>"><button name="act" value="delete" class="btn btn-sm btn-link text-danger">Delete account</button></form>
    </div>
    <div class="admin-card card p-4 small">
      <h3 class="h6">Planner</h3>
      <div><strong><?= e($f['owner']) ?></strong></div><div><?= e($f['email']) ?></div><div><?= e($f['phone']) ?></div>
      <a href="users.php?q=<?= urlencode($f['email']) ?>" class="small">View user</a>
      <hr>
      <?php foreach ($counts as $l => $c): ?><div class="d-flex justify-content-between"><span class="text-muted"><?= $l ?></span><strong><?= e($c) ?></strong></div><?php endforeach; ?>
    </div>
  </div>
  <div class="col-xl-8">
    <div class="admin-card card mb-4"><div class="card-header">Services</div>
      <table class="table mb-0 small align-middle"><tbody>
      <?php foreach ($services as $s): ?>
        <tr><td><i class="bi <?= e($s['icon']) ?> text-gold"></i> <?= e($s['name']) ?></td><td><?= e(money($s['price'])) ?></td>
          <td><?= $s['owned_at'] ? '<span class="badge text-bg-success">Active</span> ' . ($s['order_id'] ? '<span class="text-muted">paid</span>' : '<span class="text-muted">complimentary</span>') : ($s['is_standard'] ? '<span class="badge text-bg-light border">Standard</span>' : '<span class="text-muted">—</span>') ?></td>
          <td class="text-end"><form method="post"><?= csrf_field() ?><input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
            <?php if ($s['owned_at']): ?><button name="act" value="revoke" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this service?')">Revoke</button><?php else: ?><button name="act" value="grant" class="btn btn-sm btn-outline-success">Grant free</button><?php endif; ?>
          </form></td></tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <div class="admin-card card"><div class="card-header">Orders</div>
      <table class="table mb-0 small"><tbody>
      <?php foreach ($orders as $o): ?><tr><td><a href="orders.php?q=<?= e($o['reference']) ?>"><?= e($o['reference']) ?></a></td><td><?= e(money($o['amount'])) ?></td><td><?= status_badge($o['status']) ?></td><td><?= e($o['mpesa_receipt']) ?></td><td class="text-muted"><?= e(fmt_date($o['created_at'], 'j M Y H:i')) ?></td></tr><?php endforeach; ?>
      <?php if (!$orders): ?><tr><td class="empty">No orders.</td></tr><?php endif; ?>
      </tbody></table></div>
  </div>
</div>
<?php admin_footer();
