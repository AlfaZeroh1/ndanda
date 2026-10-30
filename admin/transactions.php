<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
if (is_post() && input('act') === 'reconcile') {
    $tx = row("SELECT * FROM mpesa_transactions WHERE id = ? AND status = 'success'", [(int) input('id')]);
    $o = row('SELECT * FROM orders WHERE reference = ?', [strtoupper(trim(input('reference')))]);
    if (!$tx || !$o) {
        flash('danger', 'Transaction or order not found.');
    } else {
        update('mpesa_transactions', ['order_id' => $o['id'], 'result_desc' => 'Reconciled manually to ' . $o['reference']], 'id = ?', [$tx['id']]);
        if ($o['status'] !== 'paid') mark_order_paid((int) $o['id'], $tx['mpesa_receipt'], $tx['phone']);
        flash('success', 'Payment ' . $tx['mpesa_receipt'] . ' linked to ' . $o['reference'] . '.');
    }
    back(url('admin/transactions.php'));
}
$view = input('view') === 'logs' ? 'logs' : 'tx';
admin_header('M-Pesa transactions', 'transactions');
?>
<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link <?= $view === 'tx' ? 'active bg-dark' : 'text-dark' ?>" href="transactions.php">Transactions</a></li>
  <li class="nav-item"><a class="nav-link <?= $view === 'logs' ? 'active bg-dark' : 'text-dark' ?>" href="transactions.php?view=logs">API logs (callbacks & requests)</a></li>
</ul>
<?php if ($view === 'logs'):
    $logs = rows('SELECT * FROM mpesa_logs ORDER BY id DESC LIMIT 100'); ?>
  <div class="admin-card card"><div class="table-responsive"><table class="table small mb-0 align-top">
    <thead><tr class="text-muted"><th>Time</th><th>Endpoint</th><th>IP</th><th>Payload</th></tr></thead><tbody>
    <?php foreach ($logs as $l): ?><tr><td class="text-nowrap"><?= e(fmt_date($l['created_at'], 'j M H:i:s')) ?></td><td><code><?= e($l['endpoint']) ?></code></td><td><?= e($l['ip']) ?></td><td style="max-width:600px"><details><summary class="text-muted"><?= e(excerpt($l['payload'], 90)) ?></summary><pre class="raw mt-2"><?= e(json_encode(json_decode((string) $l['payload']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: $l['payload']) ?></pre></details></td></tr><?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="4" class="empty">No API activity yet.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php else:
    $where = ['1=1'];
    $params = [];
    $qs = trim(input('q'));
    if ($qs !== '') { $where[] = '(t.mpesa_receipt LIKE ? OR t.phone LIKE ? OR t.account_ref LIKE ? OR t.checkout_request_id LIKE ?)'; $l = "%$qs%"; array_push($params, $l, $l, $l, $l); }
    if (input('type')) { $where[] = 't.type = ?'; $params[] = input('type'); }
    if (input('status')) { $where[] = 't.status = ?'; $params[] = input('status'); }
    if (input('unmatched')) $where[] = "t.order_id IS NULL AND t.status = 'success'";
    $w = implode(' AND ', $where);
    if (input('export')) {
        csv_download('mpesa-' . date('Ymd') . '.csv', ['Date', 'Type', 'Status', 'Receipt', 'Phone', 'Payer', 'Amount', 'Account', 'Order', 'Result'],
            rows("SELECT t.created_at, t.type, t.status, t.mpesa_receipt, t.phone, t.payer_name, t.amount, t.account_ref, o.reference, t.result_desc FROM mpesa_transactions t LEFT JOIN orders o ON o.id = t.order_id WHERE $w ORDER BY t.id DESC", $params));
    }
    $list = rows("SELECT t.*, o.reference FROM mpesa_transactions t LEFT JOIN orders o ON o.id = t.order_id WHERE $w ORDER BY t.id DESC LIMIT 200", $params);
    $sum = (float) val("SELECT COALESCE(SUM(amount),0) FROM mpesa_transactions t WHERE $w AND t.status = 'success'", $params); ?>
  <form class="d-flex flex-wrap gap-2 mb-3">
    <input name="q" value="<?= e($qs) ?>" class="form-control" style="max-width:260px" placeholder="Receipt, phone, account">
    <select name="type" class="form-select" style="max-width:160px"><option value="">All types</option><?php foreach (['stk' => 'STK push', 'c2b' => 'Paybill (C2B)', 'manual' => 'Manual', 'simulated' => 'Simulated'] as $k => $l): ?><option value="<?= $k ?>" <?= input('type') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <select name="status" class="form-select" style="max-width:160px"><option value="">All statuses</option><?php foreach (['success', 'pending', 'failed', 'cancelled', 'timeout'] as $s): ?><option <?= input('status') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
    <div class="form-check align-self-center"><input class="form-check-input" type="checkbox" name="unmatched" value="1" id="um" <?= input('unmatched') ? 'checked' : '' ?>><label for="um" class="form-check-label small">Unmatched only</label></div>
    <button class="btn btn-dark">Filter</button>
    <a href="?<?= e(http_build_query(array_filter(['q' => $qs, 'type' => input('type'), 'status' => input('status'), 'unmatched' => input('unmatched'), 'export' => 1]))) ?>" class="btn btn-outline-success"><i class="bi bi-filetype-csv"></i> Export</a>
    <span class="ms-auto badge text-bg-success align-self-center p-2">Successful: <?= e(money($sum)) ?></span>
  </form>
  <div class="admin-card card"><div class="table-responsive"><table class="table align-middle mb-0 small">
    <thead><tr class="text-muted"><th>Date</th><th>Type</th><th>Receipt</th><th>Phone / payer</th><th>Amount</th><th>Account</th><th>Order</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($list as $t): ?>
      <tr>
        <td class="text-nowrap"><?= e(fmt_date($t['created_at'], 'j M H:i')) ?></td>
        <td><span class="badge text-bg-light border"><?= e(strtoupper($t['type'])) ?></span><?= $t['is_test'] ? ' <span class="badge text-bg-warning">test</span>' : '' ?></td>
        <td class="fw-semibold"><?= e($t['mpesa_receipt']) ?></td>
        <td><?= e($t['phone']) ?><div class="text-muted"><?= e($t['payer_name']) ?></div></td>
        <td><?= e(money($t['amount'])) ?></td>
        <td><?= e($t['account_ref']) ?></td>
        <td><?php if ($t['reference']): ?><a href="orders.php?q=<?= e($t['reference']) ?>"><?= e($t['reference']) ?></a><?php elseif ($t['status'] === 'success'): ?>
          <form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="act" value="reconcile"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><input name="reference" class="form-control form-control-sm" placeholder="Order ref" style="width:100px" required><button class="btn btn-sm btn-warning">Link</button></form>
        <?php else: ?>—<?php endif; ?></td>
        <td><?= status_badge($t['status']) ?><div class="text-muted" style="max-width:200px"><?= e($t['result_desc']) ?></div></td>
        <td><?php if ($t['raw_callback'] || $t['raw_request']): ?><details><summary class="small">Raw</summary><pre class="raw" style="width:320px"><?= e(json_encode(json_decode((string) ($t['raw_callback'] ?: $t['raw_request'])), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre></details><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="9" class="empty">No transactions.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php endif;
admin_footer();
