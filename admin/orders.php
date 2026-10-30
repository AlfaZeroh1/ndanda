<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
if (is_post()) {
    $o = row('SELECT * FROM orders WHERE id = ?', [(int) input('id')]);
    if ($o && input('act') === 'mark_paid' && $o['status'] !== 'paid') {
        $receipt = strtoupper(trim(input('receipt'))) ?: 'MANUAL-' . date('ymdHi');
        insert('mpesa_transactions', ['order_id' => $o['id'], 'type' => 'manual', 'phone' => $o['phone'], 'amount' => $o['amount'], 'account_ref' => $o['reference'], 'result_code' => 0, 'result_desc' => 'Marked paid by admin: ' . current_user()['name'], 'mpesa_receipt' => $receipt, 'status' => 'success', 'is_test' => test_flag()]);
        mark_order_paid((int) $o['id'], $receipt);
        flash('success', 'Order ' . $o['reference'] . ' marked as paid and services activated.');
    } elseif ($o && input('act') === 'cancel' && $o['status'] === 'pending') {
        q("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$o['id']]);
        flash('success', 'Order cancelled.');
    }
    back(url('admin/orders.php'));
}
$qs = trim(input('q'));
$st = input('status');
$from = input('from');
$to = input('to');
$where = ['1=1'];
$params = [];
if ($qs !== '') { $where[] = '(o.reference LIKE ? OR o.mpesa_receipt LIKE ? OR o.phone LIKE ? OR u.name LIKE ? OR u.email LIKE ?)'; $l = "%$qs%"; array_push($params, $l, $l, $l, $l, $l); }
if ($st) { $where[] = 'o.status = ?'; $params[] = $st; }
if ($from) { $where[] = 'DATE(o.created_at) >= ?'; $params[] = $from; }
if ($to) { $where[] = 'DATE(o.created_at) <= ?'; $params[] = $to; }
$w = implode(' AND ', $where);
$sql = "FROM orders o LEFT JOIN users u ON u.id = o.user_id LEFT JOIN funerals f ON f.id = o.funeral_id WHERE $w";
if (input('export')) {
    $data = rows("SELECT o.reference, o.created_at, o.paid_at, o.status, o.amount, o.mpesa_receipt, o.phone, u.name, u.email, f.deceased_name, (SELECT GROUP_CONCAT(name SEPARATOR '; ') FROM order_items WHERE order_id = o.id) $sql ORDER BY o.created_at DESC", $params);
    csv_download('orders-' . date('Ymd') . '.csv', ['Reference', 'Created', 'Paid at', 'Status', 'Amount', 'M-Pesa receipt', 'Phone', 'Customer', 'Email', 'Funeral', 'Items'], $data);
}
$sum = (float) val("SELECT COALESCE(SUM(CASE WHEN o.status = 'paid' THEN o.amount END),0) $sql", $params);
$perPage = 30;
$pg = max(1, (int) input('page', 1));
$total = (int) val("SELECT COUNT(*) $sql", $params);
$list = rows("SELECT o.*, u.name AS customer, u.email, f.deceased_name, f.id AS fid $sql ORDER BY o.created_at DESC LIMIT $perPage OFFSET " . (($pg - 1) * $perPage), $params);
admin_header('Orders', 'orders');
$qsArr = ['q' => $qs, 'status' => $st, 'from' => $from, 'to' => $to];
?>
<form class="row g-2 mb-3 align-items-end">
  <div class="col-md-3"><input name="q" value="<?= e($qs) ?>" class="form-control" placeholder="Reference, receipt, phone, name"></div>
  <div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option><?php foreach (['paid', 'pending', 'failed', 'cancelled'] as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input type="date" name="from" value="<?= e($from) ?>" class="form-control" title="From"></div>
  <div class="col-md-2"><input type="date" name="to" value="<?= e($to) ?>" class="form-control" title="To"></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-dark">Filter</button><a href="?<?= e(http_build_query($qsArr + ['export' => 1])) ?>" class="btn btn-outline-success"><i class="bi bi-filetype-csv"></i> Export CSV</a></div>
</form>
<div class="d-flex gap-3 mb-3 small"><span class="badge text-bg-light border p-2"><?= $total ?> orders</span><span class="badge text-bg-success p-2">Collected: <?= e(money($sum)) ?></span></div>
<div class="admin-card card"><div class="table-responsive"><table class="table align-middle mb-0 small">
  <thead><tr class="text-muted"><th>Reference</th><th>Customer</th><th>Funeral</th><th>Amount</th><th>Status</th><th>M-Pesa</th><th>Date</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $o): ?>
    <tr>
      <td><strong><?= e($o['reference']) ?></strong><?= $o['is_test'] ? ' <span class="badge text-bg-warning">test</span>' : '' ?><div class="text-muted"><?= e(implode(', ', array_column(order_items((int) $o['id']), 'name'))) ?></div></td>
      <td><?= e($o['customer']) ?><div class="text-muted"><?= e($o['phone']) ?></div></td>
      <td><?php if ($o['fid']): ?><a href="funeral.php?id=<?= (int) $o['fid'] ?>"><?= e($o['deceased_name']) ?></a><?php else: ?>—<?php endif; ?></td>
      <td class="fw-semibold"><?= e(money($o['amount'])) ?></td>
      <td><?= status_badge($o['status']) ?><?= $o['notes'] ? '<div class="text-danger">' . e($o['notes']) . '</div>' : '' ?></td>
      <td><?= e($o['mpesa_receipt']) ?></td>
      <td class="text-muted"><?= e(fmt_date($o['created_at'], 'j M Y H:i')) ?><?= $o['paid_at'] ? '<div>Paid ' . e(fmt_date($o['paid_at'], 'j M H:i')) . '</div>' : '' ?></td>
      <td class="text-end text-nowrap">
        <?php if ($o['status'] !== 'paid'): ?>
          <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#paidModal" data-id="<?= (int) $o['id'] ?>" data-ref="<?= e($o['reference']) ?>">Mark paid</button>
          <?php if ($o['status'] === 'pending'): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button name="act" value="cancel" class="btn btn-sm btn-outline-secondary">Cancel</button></form><?php endif; ?>
        <?php else: ?><a href="<?= e(url('receipt.php?ref=' . $o['reference'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-receipt"></i></a><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="8" class="empty">No orders found.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<div class="mt-3"><?= paginate($total, $perPage, $pg, 'orders.php?' . http_build_query($qsArr)) ?></div>
<div class="modal fade" id="paidModal" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><?= csrf_field() ?><input type="hidden" name="act" value="mark_paid"><input type="hidden" name="id" id="pm_id">
  <div class="modal-header"><h5 class="modal-title">Mark <span id="pm_ref"></span> as paid</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="small text-muted">Use this for payments confirmed outside the automatic flow (e.g. verified on the M-Pesa portal). Services will be activated.</p><label class="form-label">M-Pesa receipt number</label><input name="receipt" class="form-control text-uppercase" placeholder="e.g. TJK1ABC2DE"></div>
  <div class="modal-footer"><button class="btn btn-success">Confirm payment</button></div></form></div></div>
<?php admin_footer('<script>document.getElementById("paidModal").addEventListener("show.bs.modal",e=>{pm_id.value=e.relatedTarget.dataset.id;pm_ref.textContent=e.relatedTarget.dataset.ref;});</script>');
