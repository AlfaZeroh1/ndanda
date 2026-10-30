<?php
require __DIR__ . '/includes/bootstrap.php';
$sent = false;
if (is_post()) {
    $u = row("SELECT * FROM users WHERE email = ? AND status = 'active'", [strtolower(trim(input('email')))]);
    if ($u && throttle('forgot', 30)) {
        $token = bin2hex(random_bytes(24));
        update('users', ['reset_token' => hash('sha256', $token), 'reset_expires' => date('Y-m-d H:i:s', time() + 3600)], 'id = ?', [$u['id']]);
        send_mail($u['email'], 'Reset your password', '<p>Hello ' . e($u['name']) . ',</p><p><a href="' . e(url('reset.php?token=' . $token)) . '">Click here to choose a new password</a>. The link expires in 1 hour.</p><p>If you did not request this, ignore this email.</p>');
    }
    $sent = true; // same message either way, to avoid revealing which emails exist
}
$pageTitle = 'Forgot password';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-cream py-4"><div class="container"><div class="card-soft p-4 p-md-5 auth-card">
  <h1 class="h3 text-center">Reset your password</h1>
  <?php if ($sent): ?>
    <div class="alert alert-success mt-3">If that email has an account, a reset link is on its way. Check your inbox (and spam).</div>
  <?php else: ?>
    <p class="small-muted text-center">Enter your email and we'll send you a reset link.</p>
    <form method="post"><?= csrf_field() ?><input type="email" name="email" class="form-control form-control-lg mb-3" required placeholder="you@example.com"><button class="btn btn-ink w-100 rounded-pill btn-lg">Send reset link</button></form>
  <?php endif; ?>
  <p class="text-center small mt-4 mb-0"><a href="<?= e(url('login.php')) ?>">Back to log in</a> · Need help? Call <?= e(setting('company_phone')) ?></p>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php';
