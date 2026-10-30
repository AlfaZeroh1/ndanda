<?php
require __DIR__ . '/includes/bootstrap.php';
$token = (string) input('token');
$u = $token !== '' ? row('SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()', [hash('sha256', $token)]) : null;
$error = '';
if ($u && is_post()) {
    $p = (string) ($_POST['password'] ?? '');
    if (strlen($p) < 6) {
        $error = 'Use at least 6 characters.';
    } elseif ($p !== ($_POST['password2'] ?? '')) {
        $error = 'Passwords do not match.';
    } else {
        update('users', ['password_hash' => password_hash($p, PASSWORD_DEFAULT), 'reset_token' => null, 'reset_expires' => null], 'id = ?', [$u['id']]);
        flash('success', 'Password updated. You can now log in.');
        redirect(url('login.php'));
    }
}
$pageTitle = 'Choose a new password';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-cream py-4"><div class="container"><div class="card-soft p-4 p-md-5 auth-card">
  <h1 class="h3 text-center mb-3">Choose a new password</h1>
  <?php if (!$u): ?><div class="alert alert-warning">This reset link is invalid or has expired. <a href="<?= e(url('forgot.php')) ?>">Request a new one</a>.</div>
  <?php else: ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
      <input type="password" name="password" class="form-control form-control-lg mb-2" placeholder="New password" minlength="6" required>
      <input type="password" name="password2" class="form-control form-control-lg mb-3" placeholder="Confirm password" required>
      <button class="btn btn-ink w-100 rounded-pill btn-lg">Save password</button>
    </form>
  <?php endif; ?>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php';
