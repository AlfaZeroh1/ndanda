<?php
/** Printable payment receipt for a paid order. */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$o = row("SELECT o.*, u.name AS customer, u.email, f.deceased_name FROM orders o LEFT JOIN users u ON u.id = o.user_id LEFT JOIN funerals f ON f.id = o.funeral_id WHERE o.reference = ? AND o.status = 'paid'", [input('ref')]);
if (!$o || ((int) $o['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin')) { http_response_code(404); exit('Receipt not found.'); }
$items = order_items((int) $o['id']);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Receipt <?= e($o['reference']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f4f4f4}.receipt{max-width:640px;margin:32px auto;background:#fff;padding:40px;border-radius:12px}@media print{body{background:#fff}.receipt{margin:0;box-shadow:none}.no-print{display:none}}</style></head>
<body><div class="receipt shadow-sm">
<div class="d-flex justify-content-between align-items-start mb-4"><div><h1 class="h4 mb-0"><?= e(setting('company_name')) ?></h1><div class="small text-muted"><?= e(setting('company_address')) ?> · <?= e(setting('company_phone')) ?><br><?= e(setting('company_email')) ?></div></div><div class="text-end"><div class="h5 mb-0">RECEIPT</div><div class="small text-muted"><?= e($o['reference']) ?></div></div></div>
<div class="row small mb-4"><div class="col-6"><div class="text-muted">Billed to</div><strong><?= e($o['customer']) ?></strong><br><?= e($o['email']) ?><br><?= e($o['phone']) ?></div><div class="col-6 text-end"><div class="text-muted">Paid on</div><strong><?= e(fmt_date($o['paid_at'], 'j M Y, H:i')) ?></strong><br>M-Pesa: <strong><?= e($o['mpesa_receipt']) ?></strong><?php if ($o['deceased_name']): ?><br>For: <?= e($o['deceased_name']) ?><?php endif; ?></div></div>
<table class="table"><thead><tr><th>Service</th><th class="text-end">Amount</th></tr></thead><tbody>
<?php foreach ($items as $it): ?><tr><td><?= e($it['name']) ?></td><td class="text-end"><?= e(money($it['price'])) ?></td></tr><?php endforeach; ?>
</tbody><tfoot><tr><th>Total paid</th><th class="text-end"><?= e(money($o['amount'])) ?></th></tr></tfoot></table>
<p class="small text-muted text-center mt-4">Thank you for trusting <?= e(setting('site_name')) ?>.</p>
<div class="text-center no-print"><button onclick="print()" class="btn btn-dark">Print / Save PDF</button></div>
</div></body></html>
