<?php
if ($mode === 'post') {
    if (input('act') === 'buy') {
        $order = create_order((int) $user['id'], $fid, (array) ($_POST['services'] ?? []));
        if (!$order) throw new RuntimeException('Select at least one service you do not already have.');
        if ($order['status'] === 'paid') { flash('success', 'Services added.'); return; }
        redirect(url('checkout.php?ref=' . $order['reference']));
    }
    if (input('act') === 'cancel') {
        q("UPDATE orders SET status = 'cancelled' WHERE id = ? AND funeral_id = ? AND status = 'pending'", [(int) input('oid'), $fid]);
        flash('success', 'Order cancelled.');
    }
    return;
}
$services = rows('SELECT s.*, fs.created_at AS owned_at FROM services s LEFT JOIN funeral_services fs ON fs.service_id = s.id AND fs.funeral_id = ? WHERE s.is_active = 1 OR fs.funeral_id IS NOT NULL ORDER BY s.sort', [$fid]);
$orders = rows('SELECT * FROM orders WHERE funeral_id = ? ORDER BY created_at DESC', [$fid]);
$available = array_filter($services, fn($s) => !$s['owned_at'] && !$s['is_standard']);
?>
<div class="row g-4">
  <div class="col-xl-7">
    <div class="card-soft p-4">
      <h2 class="h5">Services</h2>
      <?php foreach ($services as $s): if ($s['owned_at'] || $s['is_standard']): ?>
        <div class="d-flex align-items-center gap-3 py-2 border-bottom"><i class="bi <?= e($s['icon']) ?> text-gold fs-5"></i><div class="flex-grow-1"><?= e($s['name']) ?></div><span class="badge text-bg-success rounded-pill"><i class="bi bi-check2"></i> Active</span></div>
      <?php endif; endforeach; ?>
      <?php if ($available): ?>
      <form method="post" class="mt-4">
        <?= csrf_field() ?><input type="hidden" name="act" value="buy">
        <h3 class="h6 text-muted text-uppercase small">Add more</h3>
        <div class="row g-3">
          <?php foreach ($available as $s): ?>
          <div class="col-md-6"><label class="svc-option"><input type="checkbox" name="services[]" value="<?= (int) $s['id'] ?>" data-price="<?= (float) $s['price'] ?>" data-name="<?= e($s['name']) ?>">
            <div class="d-flex justify-content-between gap-2"><strong><?= e($s['name']) ?></strong><span class="svc-price"><?= e(money($s['price'])) ?></span></div>
            <div class="small text-secondary"><?= e(excerpt($s['description'], 90)) ?></div></label></div>
          <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3 p-3 rounded-4 bg-cream"><div><span class="text-muted">Total:</span> <strong data-total>KES 0</strong></div><button class="btn btn-mpesa rounded-pill px-4"><i class="bi bi-phone"></i> Pay with M-Pesa</button></div>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card-soft">
      <div class="p-3 border-bottom fw-semibold">Order history</div>
      <?php if (!$orders): ?><div class="empty">No orders yet.</div><?php endif; ?>
      <?php foreach ($orders as $o): ?>
        <div class="p-3 border-bottom small">
          <div class="d-flex justify-content-between"><strong><?= e($o['reference']) ?></strong><?= status_badge($o['status']) ?></div>
          <div class="text-muted"><?= e(fmt_date($o['created_at'], 'j M Y, H:i')) ?> · <?= e(money($o['amount'])) ?><?= $o['mpesa_receipt'] ? ' · ' . e($o['mpesa_receipt']) : '' ?></div>
          <div class="text-muted"><?= e(implode(', ', array_column(order_items((int) $o['id']), 'name'))) ?></div>
          <?php if ($o['status'] === 'pending'): ?>
            <div class="d-flex gap-2 mt-2"><a href="<?= e(url('checkout.php?ref=' . $o['reference'])) ?>" class="btn btn-sm btn-mpesa rounded-pill">Pay now</a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="cancel"><input type="hidden" name="oid" value="<?= (int) $o['id'] ?>"><button class="btn btn-sm btn-link text-muted">Cancel</button></form></div>
          <?php endif; ?>
          <?php if ($o['status'] === 'paid'): ?><a href="<?= e(url('receipt.php?ref=' . $o['reference'])) ?>" target="_blank" class="small"><i class="bi bi-receipt"></i> Receipt</a><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
