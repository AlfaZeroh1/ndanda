<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
if (is_post()) {
    $id = (int) input('id');
    $act = input('act');
    if (in_array($act, ['active', 'suspended', 'archived', 'pending_payment'], true)) {
        q('UPDATE funerals SET status = ? WHERE id = ?', [$act, $id]);
        flash('success', 'Status updated.');
    } elseif ($act === 'feature') {
        q('UPDATE funerals SET is_featured = 1 - is_featured WHERE id = ?', [$id]);
        flash('success', 'Featured flag toggled.');
    } elseif ($act === 'delete') {
        rrmdir(UPLOAD_DIR . '/f' . $id);
        @rmdir(UPLOAD_DIR . '/f' . $id);
        rrmdir(UPLOAD_DIR . '/private/f' . $id);
        @rmdir(UPLOAD_DIR . '/private/f' . $id);
        q('DELETE FROM funerals WHERE id = ?', [$id]);
        flash('success', 'Funeral account deleted. Its payment records were kept.');
    }
    back(url('admin/funerals.php'));
}
$qs = trim(input('q'));
$st = input('status');
$where = ['1=1'];
$params = [];
if ($qs !== '') { $where[] = '(f.deceased_name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; $l = "%$qs%"; array_push($params, $l, $l, $l, $l); }
if ($st) { $where[] = 'f.status = ?'; $params[] = $st; }
$perPage = 25;
$pg = max(1, (int) input('page', 1));
$w = implode(' AND ', $where);
$total = (int) val("SELECT COUNT(*) FROM funerals f JOIN users u ON u.id = f.user_id WHERE $w", $params);
$list = rows("SELECT f.*, u.name AS owner, u.email, u.phone,
    (SELECT COUNT(*) FROM tributes t WHERE t.funeral_id = f.id) AS tcount,
    (SELECT COALESCE(SUM(amount),0) FROM orders o WHERE o.funeral_id = f.id AND o.status = 'paid') AS paid
    FROM funerals f JOIN users u ON u.id = f.user_id WHERE $w ORDER BY f.created_at DESC LIMIT $perPage OFFSET " . (($pg - 1) * $perPage), $params);
admin_header('Funeral accounts', 'funerals');
?>
<form class="d-flex flex-wrap gap-2 mb-3">
  <input name="q" value="<?= e($qs) ?>" class="form-control" style="max-width:320px" placeholder="Search name, owner, email, phone">
  <select name="status" class="form-select" style="max-width:200px"><option value="">All statuses</option><?php foreach (['active', 'pending_payment', 'draft', 'suspended', 'archived'] as $s): ?><option value="<?= $s ?>" <?= $st === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option><?php endforeach; ?></select>
  <button class="btn btn-dark">Filter</button>
  <span class="ms-auto align-self-center small text-muted"><?= $total ?> accounts</span>
</form>
<div class="admin-card card"><div class="table-responsive"><table class="table align-middle mb-0 small">
  <thead><tr class="text-muted"><th>Deceased</th><th>Planner</th><th>Status</th><th>Paid</th><th>Engagement</th><th>Created</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $f): ?>
    <tr>
      <td><div class="d-flex align-items-center gap-2"><?= portrait($f, 'rounded-circle', 38) ?><div><a href="funeral.php?id=<?= (int) $f['id'] ?>" class="fw-semibold text-reset"><?= e($f['deceased_name']) ?></a><?= $f['is_featured'] ? ' <i class="bi bi-star-fill text-warning" title="Featured"></i>' : '' ?><?= $f['is_test'] ? ' <span class="badge text-bg-warning">test</span>' : '' ?><div class="text-muted"><?= e(life_years($f)) ?> · <?= e($f['visibility']) ?></div></div></div></td>
      <td><?= e($f['owner']) ?><div class="text-muted"><?= e($f['email']) ?> · <?= e($f['phone']) ?></div></td>
      <td><?= status_badge($f['status']) ?></td>
      <td class="fw-semibold"><?= e(money($f['paid'])) ?></td>
      <td class="text-muted"><i class="bi bi-eye"></i> <?= (int) $f['views'] ?> · <i class="bi bi-chat"></i> <?= (int) $f['tcount'] ?> · <i class="bi bi-fire"></i> <?= (int) $f['candles'] ?></td>
      <td class="text-muted"><?= e(fmt_date($f['created_at'])) ?></td>
      <td class="text-end text-nowrap">
        <a href="funeral.php?id=<?= (int) $f['id'] ?>" class="btn btn-sm btn-outline-dark">Manage</a>
        <a href="<?= e(tribute_url($f)) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="7" class="empty">No funeral accounts found.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<div class="mt-3"><?= paginate($total, $perPage, $pg, 'funerals.php?' . http_build_query(['q' => $qs, 'status' => $st])) ?></div>
<?php admin_footer();
