<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$order = row('SELECT * FROM orders WHERE reference = ?', [input('ref')]);
if (!$order || ((int) $order['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin')) {
    flash('danger', 'Order not found.');
    redirect(url('account/'));
}
$funeral = $order['funeral_id'] ? row('SELECT * FROM funerals WHERE id = ?', [$order['funeral_id']]) : null;
if ($order['status'] === 'paid') {
    flash('success', 'This order is already paid.');
    redirect($funeral ? url('account/funeral.php?id=' . $funeral['id']) : url('account/'));
}
if ($order['status'] !== 'pending') {
    // allow retrying failed/cancelled orders
    update('orders', ['status' => 'pending'], 'id = ?', [$order['id']]);
}
$items = order_items((int) $order['id']);
$canSimulate = test_flag() && setting('allow_simulation') === '1' && setting('mpesa_env') !== 'live';
$pageTitle = 'Checkout ' . $order['reference'];
$hideFooter = false;
require __DIR__ . '/includes/header.php';
$phone = $order['phone'] ?: $user['phone'];
$paybill = setting('mpesa_type') === 'till' ? '' : (setting('mpesa_c2b_shortcode') ?: setting('mpesa_shortcode'));
?>
<section class="page-hero py-5"><div class="container"><div class="eyebrow">Step 2 of 2</div><h1>Secure checkout</h1><p>Order <?= e($order['reference']) ?><?= $funeral ? ' · ' . e($funeral['deceased_name']) : '' ?></p></div></section>
<section class="section-sm bg-cream">
  <div class="container" style="max-width:980px">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card-soft p-4" id="payCard">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="feature-icon" style="background:#e9f6e8;color:#3aa935"><i class="bi bi-phone"></i></div>
            <div><h2 class="h4 mb-0">Pay with M-Pesa</h2><div class="small-muted">You will receive a prompt on your phone. Enter your M-Pesa PIN to complete.</div></div>
          </div>
          <div id="payForm">
            <label class="form-label">M-Pesa phone number</label>
            <div class="input-group input-group-lg mb-3">
              <span class="input-group-text">🇰🇪</span>
              <input id="payPhone" class="form-control" inputmode="tel" value="<?= e($phone ? '0' . substr($phone, 3) : '') ?>" placeholder="07XX XXX XXX">
            </div>
            <button id="payBtn" class="btn btn-mpesa btn-lg w-100 rounded-pill"><i class="bi bi-lightning-charge-fill me-1"></i> Pay <?= e(money($order['amount'])) ?> now</button>
            <div id="payError" class="alert alert-danger mt-3 d-none"></div>
            <?php if ($canSimulate): ?>
            <div class="border rounded-3 p-3 mt-3 small bg-light">
              <strong><i class="bi bi-cone-striped"></i> Test mode</strong> — Daraja keys not live yet? Simulate a successful payment to test the full flow.
              <button id="simBtn" class="btn btn-sm btn-outline-dark mt-2 d-block">Simulate successful payment</button>
            </div>
            <?php endif; ?>
          </div>
          <div id="payWait" class="pay-wait d-none">
            <i class="bi bi-phone-vibrate phone-ring"></i>
            <h3 class="h4 mt-3">Check your phone</h3>
            <p class="text-secondary mb-1" id="waitMsg">We have sent an M-Pesa prompt to <strong id="waitPhone"></strong>. Enter your PIN to pay <?= e(money($order['amount'])) ?>.</p>
            <div class="small-muted">Waiting for confirmation… <span id="waitSecs">0</span>s</div>
            <div class="progress progress-thin mt-3 mx-auto" style="max-width:280px"><div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width:100%"></div></div>
            <button class="btn btn-link btn-sm mt-3" id="cancelWait">Didn't get the prompt? Try again</button>
          </div>
          <div id="paySuccess" class="text-center py-4 d-none">
            <i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>
            <h3 class="h3 mt-2">Payment received</h3>
            <p class="text-secondary">Receipt <strong id="receiptNo"></strong>. Opening your funeral account…</p>
          </div>
        </div>

        <?php if ($paybill): ?>
        <div class="card-soft p-4 mt-4">
          <h3 class="h6 mb-2"><i class="bi bi-list-ol me-1"></i> Or pay manually via Paybill</h3>
          <ol class="small mpesa-steps mb-2">
            <li>Go to M-Pesa → <strong>Lipa na M-Pesa</strong> → <strong>Pay Bill</strong></li>
            <li>Business number: <strong><?= e($paybill) ?></strong></li>
            <li>Account number: <strong><?= e($order['reference']) ?></strong></li>
            <li>Amount: <strong><?= e(number_format((float) $order['amount'])) ?></strong></li>
          </ol>
          <div class="small-muted">Your account is activated automatically once the payment is confirmed. Keep this page open.</div>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-5">
        <div class="card-soft p-4">
          <h3 class="h5 mb-3">Order summary</h3>
          <?php foreach ($items as $it): ?>
          <div class="d-flex justify-content-between py-2 border-bottom small"><span><?= e($it['name']) ?></span><span class="fw-semibold"><?= e(money($it['price'])) ?></span></div>
          <?php endforeach; ?>
          <div class="d-flex justify-content-between align-items-end mt-3"><span class="text-muted">Total</span><span class="total-line"><?= e(money($order['amount'])) ?></span></div>
          <hr>
          <div class="small-muted"><i class="bi bi-shield-check text-success"></i> Payments are processed by Safaricom M-Pesa. We never see your PIN.</div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
$ref = json_encode($order['reference']);
$done = json_encode($funeral ? url('account/funeral.php?id=' . $funeral['id'] . '&paid=1') : url('account/'));
$extraFoot = <<<HTML
<script>
(function(){
  const ref = $ref, doneUrl = $done;
  const \$ = id => document.getElementById(id);
  let timer = null, secs = 0, polling = false;

  function show(which){ ['payForm','payWait','paySuccess'].forEach(id => \$(id).classList.toggle('d-none', id !== which)); }
  function err(msg){ const e = \$('payError'); e.textContent = msg; e.classList.remove('d-none'); show('payForm'); }
  function stop(){ polling = false; clearInterval(timer); }

  async function poll(){
    if (!polling) return;
    const res = await fetch(APP.base + '/api/payment-status.php?ref=' + encodeURIComponent(ref), {headers:{'Accept':'application/json'}}).then(r => r.json()).catch(() => ({}));
    if (res.status === 'paid') { stop(); \$('receiptNo').textContent = res.receipt || ''; show('paySuccess'); setTimeout(() => location.href = doneUrl, 2200); return; }
    if (res.status === 'failed') { stop(); err(res.message || 'Payment was not completed. Please try again.'); return; }
    if (secs > 120) { stop(); err('We did not get a confirmation yet. If you paid, it will reflect shortly — otherwise try again.'); return; }
    setTimeout(poll, 3000);
  }

  \$('payBtn').addEventListener('click', async function(){
    \$('payError').classList.add('d-none');
    const phone = \$('payPhone').value.trim();
    if (!phone) { err('Enter your M-Pesa phone number.'); return; }
    this.disabled = true; const label = this.innerHTML; this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending prompt…';
    const res = await postJSON(APP.base + '/api/stk-push.php', {ref: ref, phone: phone});
    this.disabled = false; this.innerHTML = label;
    if (!res.ok) { err(res.message || 'Could not start the payment.'); return; }
    \$('waitPhone').textContent = phone; show('payWait');
    secs = 0; clearInterval(timer); timer = setInterval(() => \$('waitSecs').textContent = ++secs, 1000);
    polling = true; setTimeout(poll, 4000);
  });

  \$('cancelWait').addEventListener('click', function(){ stop(); show('payForm'); });

  const sim = \$('simBtn');
  if (sim) sim.addEventListener('click', async function(){
    this.disabled = true;
    const res = await postJSON(APP.base + '/api/simulate-payment.php', {ref: ref});
    if (res.ok) { \$('receiptNo').textContent = res.receipt; show('paySuccess'); setTimeout(() => location.href = doneUrl, 1800); }
    else { this.disabled = false; err(res.message || 'Simulation failed'); }
  });

  // If a Paybill payment lands while the page is open, move on automatically
  polling = true; (function bg(){ fetch(APP.base + '/api/payment-status.php?ref=' + encodeURIComponent(ref) + '&passive=1').then(r => r.json()).then(r => { if (r.status === 'paid') { \$('receiptNo').textContent = r.receipt || ''; show('paySuccess'); setTimeout(() => location.href = doneUrl, 2000); } else setTimeout(bg, 10000); }).catch(() => setTimeout(bg, 15000)); })();
  polling = false;
})();
</script>
HTML;
require __DIR__ . '/includes/footer.php';
