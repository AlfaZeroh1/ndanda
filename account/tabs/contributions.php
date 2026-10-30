<?php
if ($mode === 'post') {
    $act = input('act');
    if ($act === 'settings') {
        update('funerals', ['contribution_details' => trim(input('contribution_details')) ?: null, 'accept_pledges' => input('accept_pledges') ? 1 : 0], 'id = ?', [$fid]);
        flash('success', 'Contribution settings saved.');
    } elseif ($act === 'add') {
        if (trim(input('name')) === '' || (float) input('amount') <= 0) throw new RuntimeException('Enter a name and amount.');
        insert('contributions', ['funeral_id' => $fid, 'name' => mb_substr(input('name'), 0, 120), 'phone' => mb_substr(input('phone'), 0, 30) ?: null, 'amount' => (float) input('amount'), 'status' => input('status') === 'received' ? 'received' : 'pledged', 'source' => 'planner', 'note' => mb_substr(input('note'), 0, 250) ?: null]);
        flash('success', 'Contribution recorded.');
    } elseif ($act === 'toggle') {
        q("UPDATE contributions SET status = IF(status = 'received', 'pledged', 'received') WHERE id = ? AND funeral_id = ?", [(int) input('cid'), $fid]);
    } elseif ($act === 'delete') {
        q('DELETE FROM contributions WHERE id = ? AND funeral_id = ?', [(int) input('cid'), $fid]);
        flash('success', 'Entry removed.');
    } elseif ($act === 'export') {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="contributions-' . $f['slug'] . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name', 'Phone', 'Amount', 'Status', 'Source', 'Note', 'Date']);
        foreach (rows('SELECT * FROM contributions WHERE funeral_id = ? ORDER BY created_at', [$fid]) as $c) fputcsv($out, [$c['name'], $c['phone'], $c['amount'], $c['status'], $c['source'], $c['note'], $c['created_at']]);
        exit;
    }
    return;
}
$list = rows('SELECT * FROM contributions WHERE funeral_id = ? ORDER BY created_at DESC', [$fid]);
$pledged = array_sum(array_map(fn($c) => (float) $c['amount'], $list));
$received = array_sum(array_map(fn($c) => $c['status'] === 'received' ? (float) $c['amount'] : 0, $list));
$budget = (float) val('SELECT COALESCE(SUM(estimated),0) FROM prep_budget WHERE funeral_id = ?', [$fid]);
?>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat"><div class="stat-label">Total pledged</div><div class="stat-num"><?= e(money($pledged)) ?></div></div></div>
  <div class="col-md-4"><div class="stat"><div class="stat-label">Received</div><div class="stat-num text-success"><?= e(money($received)) ?></div></div></div>
  <div class="col-md-4"><div class="stat"><div class="stat-label">Budget coverage</div><div class="stat-num"><?= $budget > 0 ? round($received / $budget * 100) . '%' : '—' ?></div><div class="small-muted">of <?= e(money($budget)) ?> estimated budget</div></div></div>
</div>
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card-soft">
      <div class="d-flex justify-content-between align-items-center p-3 border-bottom"><strong>Contributions & pledges</strong>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="export"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv"></i> Export</button></form></div>
      <div class="table-responsive"><table class="table mb-0 align-middle small">
        <thead><tr class="text-muted"><th>Name</th><th>Amount</th><th>Status</th><th>Source</th><th></th></tr></thead>
        <tbody>
        <?php if (!$list): ?><tr><td colspan="5" class="empty">No contributions yet.</td></tr><?php endif; ?>
        <?php foreach ($list as $c): ?>
          <tr><td><strong><?= e($c['name']) ?></strong><div class="text-muted"><?= e($c['phone']) ?> <?= e($c['note']) ?></div></td>
            <td class="fw-semibold"><?= e(money($c['amount'])) ?></td>
            <td><form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="toggle"><input type="hidden" name="cid" value="<?= (int) $c['id'] ?>"><button class="btn btn-link p-0" title="Toggle received"><?= status_badge($c['status']) ?></button></form></td>
            <td><?= e(ucfirst($c['source'])) ?></td>
            <td class="text-end"><form method="post" data-confirm="Remove this entry?"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="cid" value="<?= (int) $c['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft p-4 mb-4">
      <h3 class="h6">Record a contribution</h3>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="add">
        <input name="name" class="form-control form-control-sm mb-2" placeholder="Name" required>
        <div class="row g-2 mb-2"><div class="col"><input name="phone" class="form-control form-control-sm" placeholder="Phone"></div><div class="col"><input type="number" name="amount" min="1" step="any" class="form-control form-control-sm" placeholder="Amount" required></div></div>
        <select name="status" class="form-select form-select-sm mb-2"><option value="received">Received</option><option value="pledged">Pledged</option></select>
        <input name="note" class="form-control form-control-sm mb-2" placeholder="Note (e.g. M-Pesa code)">
        <button class="btn btn-ink btn-sm w-100 rounded-pill">Save</button>
      </form>
    </div>
    <div class="card-soft p-4">
      <h3 class="h6">Show on tribute page</h3>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="settings">
        <label class="form-label small">Contribution details</label>
        <textarea name="contribution_details" rows="4" class="form-control form-control-sm mb-2" placeholder="e.g. M-Pesa Paybill 123456, Account: KAMAU. Treasurer: 0712…"><?= e($f['contribution_details']) ?></textarea>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="accept_pledges" value="1" id="ap" <?= $f['accept_pledges'] ? 'checked' : '' ?>><label class="form-check-label small" for="ap">Allow visitors to pledge online</label></div>
        <button class="btn btn-outline-ink btn-sm rounded-pill">Save settings</button>
      </form>
    </div>
  </div>
</div>
