<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
$tab = in_array(input('tab'), ['general', 'mpesa', 'system'], true) ? input('tab') : 'general';
$fields = [
    'general' => ['site_name', 'site_tagline', 'hero_title', 'hero_subtitle', 'company_name', 'company_phone', 'company_email', 'company_address', 'company_whatsapp', 'company_website', 'social_instagram', 'social_youtube', 'social_facebook', 'social_tiktok', 'admin_notify_email', 'mail_from', 'currency'],
    'mpesa'   => ['mpesa_env', 'mpesa_type', 'mpesa_shortcode', 'mpesa_till_number', 'mpesa_c2b_shortcode', 'mpesa_passkey', 'mpesa_consumer_key', 'mpesa_consumer_secret', 'mpesa_callback_base', 'mpesa_callback_secret', 'mpesa_c2b_strict'],
    'system'  => ['allow_simulation', 'pretty_urls'],
];
$checkboxes = ['mpesa_c2b_strict', 'allow_simulation', 'pretty_urls'];
$secrets = ['mpesa_consumer_secret', 'mpesa_passkey'];

if (is_post()) {
    $act = input('act');
    if ($act === 'save') {
        foreach ($fields[$tab] as $k) {
            if (in_array($k, $checkboxes, true)) { set_setting($k, input($k) ? '1' : '0'); continue; }
            $v = trim((string) ($_POST[$k] ?? ''));
            if (in_array($k, $secrets, true) && $v === '') continue; // blank = keep existing secret
            if ($k === 'mpesa_env' && !in_array($v, ['sandbox', 'live'], true)) $v = 'sandbox';
            if ($k === 'mpesa_type' && !in_array($v, ['paybill', 'till'], true)) $v = 'paybill';
            if ($k === 'company_whatsapp') $v = preg_replace('/\D/', '', $v);
            set_setting($k, $v);
        }
        if ($tab === 'mpesa') set_setting('mpesa_token_cache', ''); // force a fresh token with new credentials
        flash('success', 'Settings saved.');
    } elseif ($act === 'test_token') {
        try {
            Mpesa::token(true);
            flash('success', 'Connected to Safaricom Daraja (' . setting('mpesa_env') . ') — access token received.');
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
        }
    } elseif ($act === 'register_c2b') {
        try {
            $r = Mpesa::registerC2B();
            $msg = $r['body']['ResponseDescription'] ?? $r['body']['errorMessage'] ?? $r['raw'];
            flash(($r['status'] === 200 && empty($r['body']['errorCode'])) ? 'success' : 'danger', 'C2B URL registration: ' . $msg);
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
        }
    } elseif ($act === 'test_stk') {
        try {
            $phone = normalize_phone(input('phone'));
            if (!$phone) throw new RuntimeException('Enter a valid phone number.');
            $r = Mpesa::stkPush($phone, 1, 'TEST', 'Test');
            insert('mpesa_transactions', ['type' => 'stk', 'merchant_request_id' => $r['MerchantRequestID'] ?? null, 'checkout_request_id' => $r['CheckoutRequestID'] ?? null, 'phone' => $phone, 'amount' => 1, 'account_ref' => 'TEST', 'status' => 'pending', 'is_test' => 1]);
            flash('success', 'STK push sent for KES 1. ' . ($r['CustomerMessage'] ?? '') . ' Check Transactions for the callback.');
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
        }
    } elseif ($act === 'regen_secret') {
        set_setting('mpesa_callback_secret', bin2hex(random_bytes(12)));
        flash('warning', 'Callback secret regenerated. Re-register C2B URLs so Safaricom uses the new links.');
    }
    redirect(url('admin/settings.php?tab=' . $tab));
}
$s = fn($k) => setting($k);
$https = strpos(setting('mpesa_callback_base') ?: BASE_URL, 'https://') === 0;
admin_header('Settings', 'settings');
?>
<ul class="nav nav-pills mb-4">
  <?php foreach (['general' => 'General & contacts', 'mpesa' => 'M-Pesa (Daraja)', 'system' => 'System'] as $k => $l): ?>
    <li class="nav-item"><a class="nav-link <?= $tab === $k ? 'active bg-dark' : 'text-dark' ?>" href="?tab=<?= $k ?>"><?= $l ?></a></li>
  <?php endforeach; ?>
</ul>

<?php if ($tab === 'general'): ?>
<form method="post" class="admin-card card p-4" style="max-width:980px"><?= csrf_field() ?><input type="hidden" name="act" value="save">
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Platform name</label><input name="site_name" class="form-control" value="<?= e($s('site_name')) ?>"></div>
    <div class="col-md-6"><label class="form-label">Tagline</label><input name="site_tagline" class="form-control" value="<?= e($s('site_tagline')) ?>"></div>
    <div class="col-12"><label class="form-label">Home page headline</label><input name="hero_title" class="form-control" value="<?= e($s('hero_title')) ?>"></div>
    <div class="col-12"><label class="form-label">Home page intro</label><textarea name="hero_subtitle" class="form-control" rows="2"><?= e($s('hero_subtitle')) ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Company name</label><input name="company_name" class="form-control" value="<?= e($s('company_name')) ?>"></div>
    <div class="col-md-6"><label class="form-label">Website</label><input name="company_website" class="form-control" value="<?= e($s('company_website')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Phone</label><input name="company_phone" class="form-control" value="<?= e($s('company_phone')) ?>"></div>
    <div class="col-md-4"><label class="form-label">WhatsApp (2547…)</label><input name="company_whatsapp" class="form-control" value="<?= e($s('company_whatsapp')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Email</label><input name="company_email" class="form-control" value="<?= e($s('company_email')) ?>"></div>
    <div class="col-12"><label class="form-label">Address</label><input name="company_address" class="form-control" value="<?= e($s('company_address')) ?>"></div>
    <div class="col-md-6"><label class="form-label">Instagram URL</label><input name="social_instagram" class="form-control" value="<?= e($s('social_instagram')) ?>"></div>
    <div class="col-md-6"><label class="form-label">YouTube URL</label><input name="social_youtube" class="form-control" value="<?= e($s('social_youtube')) ?>"></div>
    <div class="col-md-6"><label class="form-label">Facebook URL</label><input name="social_facebook" class="form-control" value="<?= e($s('social_facebook')) ?>"></div>
    <div class="col-md-6"><label class="form-label">TikTok URL</label><input name="social_tiktok" class="form-control" value="<?= e($s('social_tiktok')) ?>"></div>
    <div class="col-md-5"><label class="form-label">Admin notification email</label><input name="admin_notify_email" class="form-control" value="<?= e($s('admin_notify_email')) ?>"></div>
    <div class="col-md-5"><label class="form-label">Send emails from</label><input name="mail_from" class="form-control" value="<?= e($s('mail_from')) ?>"></div>
    <div class="col-md-2"><label class="form-label">Currency</label><input name="currency" class="form-control" value="<?= e($s('currency')) ?>"></div>
  </div>
  <button class="btn btn-dark mt-4">Save settings</button>
</form>

<?php elseif ($tab === 'mpesa'): ?>
<div class="row g-4">
  <div class="col-xl-7">
    <form method="post" class="admin-card card p-4"><?= csrf_field() ?><input type="hidden" name="act" value="save">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Environment</label><select name="mpesa_env" class="form-select"><option value="sandbox">Sandbox (testing)</option><option value="live" <?= $s('mpesa_env') === 'live' ? 'selected' : '' ?>>Live (production)</option></select></div>
        <div class="col-md-6"><label class="form-label">Account type</label><select name="mpesa_type" class="form-select"><option value="paybill">Paybill</option><option value="till" <?= $s('mpesa_type') === 'till' ? 'selected' : '' ?>>Buy Goods (Till)</option></select></div>
        <div class="col-md-6"><label class="form-label">STK shortcode <span class="text-muted small">(Paybill / store no.)</span></label><input name="mpesa_shortcode" class="form-control" value="<?= e($s('mpesa_shortcode')) ?>"></div>
        <div class="col-md-6"><label class="form-label">Till number <span class="text-muted small">(till only)</span></label><input name="mpesa_till_number" class="form-control" value="<?= e($s('mpesa_till_number')) ?>"></div>
        <div class="col-md-6"><label class="form-label">C2B shortcode <span class="text-muted small">(for direct Paybill payments)</span></label><input name="mpesa_c2b_shortcode" class="form-control" value="<?= e($s('mpesa_c2b_shortcode')) ?>"></div>
        <div class="col-md-6"><label class="form-label">Lipa na M-Pesa passkey</label><input name="mpesa_passkey" type="password" class="form-control" placeholder="<?= $s('mpesa_passkey') ? '•••••••• saved (leave blank to keep)' : 'Paste passkey' ?>" autocomplete="new-password"></div>
        <div class="col-md-6"><label class="form-label">Consumer key</label><input name="mpesa_consumer_key" class="form-control" value="<?= e($s('mpesa_consumer_key')) ?>"></div>
        <div class="col-md-6"><label class="form-label">Consumer secret</label><input name="mpesa_consumer_secret" type="password" class="form-control" placeholder="<?= $s('mpesa_consumer_secret') ? '•••••••• saved (leave blank to keep)' : 'Paste consumer secret' ?>" autocomplete="new-password"></div>
        <div class="col-12"><label class="form-label">Public callback base URL</label><input name="mpesa_callback_base" class="form-control" value="<?= e($s('mpesa_callback_base')) ?>" placeholder="<?= e(BASE_URL) ?>"><div class="form-text">Leave blank to use <?= e(BASE_URL) ?>. Must be <strong>HTTPS</strong> and publicly reachable (use ngrok when testing locally).</div></div>
        <div class="col-md-8"><label class="form-label">Callback secret</label><input name="mpesa_callback_secret" class="form-control" value="<?= e($s('mpesa_callback_secret')) ?>"><div class="form-text">Appended as <code>?key=</code> so only Safaricom (who knows the URL) can post results.</div></div>
        <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="mpesa_c2b_strict" value="1" id="strict" <?= $s('mpesa_c2b_strict') === '1' ? 'checked' : '' ?>><label for="strict" class="form-check-label small">Reject Paybill payments with unknown account numbers</label></div></div>
      </div>
      <button class="btn btn-dark mt-4">Save M-Pesa settings</button>
    </form>
  </div>
  <div class="col-xl-5">
    <div class="admin-card card p-4 mb-4">
      <h3 class="h6">Status</h3>
      <ul class="list-unstyled small mb-3">
        <li><i class="bi <?= Mpesa::isConfigured() ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?>"></i> Credentials <?= Mpesa::isConfigured() ? 'entered' : 'missing' ?></li>
        <li><i class="bi <?= $https ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning' ?>"></i> Callback URL <?= $https ? 'is HTTPS' : 'is not HTTPS — Safaricom will not reach it' ?></li>
        <li><i class="bi bi-circle-fill text-<?= $s('mpesa_env') === 'live' ? 'success' : 'secondary' ?>"></i> Environment: <strong><?= e($s('mpesa_env')) ?></strong> (<?= e(Mpesa::baseUrl()) ?>)</li>
      </ul>
      <div class="small mb-2 text-muted">Callback URLs Safaricom will call:</div>
      <?php foreach (['STK result' => 'mpesa-callback.php', 'C2B confirmation' => 'c2b-confirmation.php', 'C2B validation' => 'c2b-validation.php'] as $l => $ep): ?>
        <div class="small"><?= $l ?></div><code class="small d-block text-break mb-2"><?= e(Mpesa::callbackUrl($ep)) ?></code>
      <?php endforeach; ?>
      <div class="d-flex flex-wrap gap-2 mt-2">
        <form method="post"><?= csrf_field() ?><button name="act" value="test_token" class="btn btn-sm btn-outline-dark"><i class="bi bi-plug"></i> Test connection</button></form>
        <form method="post"><?= csrf_field() ?><button name="act" value="register_c2b" class="btn btn-sm btn-outline-dark"><i class="bi bi-link"></i> Register C2B URLs</button></form>
        <form method="post" data-confirm="Regenerate the callback secret?"><?= csrf_field() ?><button name="act" value="regen_secret" class="btn btn-sm btn-outline-secondary">New secret</button></form>
      </div>
      <form method="post" class="d-flex gap-2 mt-3"><?= csrf_field() ?><input type="hidden" name="act" value="test_stk"><input name="phone" class="form-control form-control-sm" placeholder="07XX… send KES 1 STK test"><button class="btn btn-sm btn-success text-nowrap">Send test</button></form>
    </div>
    <div class="admin-card card p-4 small bg-light">
      <h3 class="h6">Go-live checklist (Daraja)</h3>
      <ol class="mb-0 ps-3">
        <li>Create an app on <a href="https://developer.safaricom.co.ke" target="_blank" rel="noopener">developer.safaricom.co.ke</a> and test here in <em>sandbox</em> (shortcode 174379, sandbox passkey pre-filled).</li>
        <li>Apply to go live with the procured Paybill; Safaricom emails the <strong>live passkey</strong>.</li>
        <li>Paste the live consumer key/secret, shortcode and passkey; switch environment to <strong>Live</strong>; save.</li>
        <li>Click <strong>Test connection</strong>, then <strong>Register C2B URLs</strong>.</li>
        <li>Send a KES 1 test STK push and confirm it appears in Transactions.</li>
        <li>Finally run <a href="go-live.php">Go live</a> to clear test data.</li>
      </ol>
    </div>
  </div>
</div>

<?php else: ?>
<form method="post" class="admin-card card p-4" style="max-width:720px"><?= csrf_field() ?><input type="hidden" name="act" value="save">
  <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="allow_simulation" value="1" id="sim" <?= $s('allow_simulation') === '1' ? 'checked' : '' ?>><label for="sim" class="form-check-label">Allow simulated payments at checkout <span class="text-muted small">(only works in test mode with sandbox)</span></label></div>
  <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="pretty_urls" value="1" id="pu" <?= $s('pretty_urls') === '1' ? 'checked' : '' ?>><label for="pu" class="form-check-label">Short share links (/t/name, /p/name) <span class="text-muted small">— needs Apache mod_rewrite (.htaccess). Turn off if links 404.</span></label></div>
  <div class="small text-muted mb-3">Test mode: <strong><?= test_flag() ? 'ON' : 'OFF' ?></strong> — managed from <a href="go-live.php">Go live</a>.</div>
  <button class="btn btn-dark">Save</button>
</form>
<?php endif;
admin_footer();
