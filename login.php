<?php
require __DIR__ . '/includes/bootstrap.php';
$next = (string) input('next');
$wantsAdmin = input('admin') === '1' || strpos($next, '/admin') !== false;
if (current_user()) redirect(is_admin() ? safe_next($next, url('admin/')) : url('account/'));
$error = '';
if (is_post()) {
    $email = strtolower(trim(input('email')));
    if (!throttle('login_' . md5($email), 2)) {
        $error = 'Too many attempts. Please wait a moment.';
    } else {
        $u = row('SELECT * FROM users WHERE email = ?', [$email]);
        if ($u && password_verify((string) ($_POST['password'] ?? ''), $u['password_hash'])) {
            if ($u['status'] !== 'active') {
                $error = 'This account is suspended. Please contact us.';
            } else {
                login_user($u);
                if ($u['role'] !== 'admin' && $wantsAdmin) {
                    flash('warning', 'You are logged in as a client. Only administrator accounts can open the admin panel.');
                    redirect(url('account/'));
                }
                redirect(safe_next($next, $u['role'] === 'admin' ? url('admin/') : url('account/')));
            }
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-cream py-4">
  <div class="container">
    <div class="card-soft p-4 p-md-5 auth-card">
      <div class="text-center mb-4"><span class="brand-mark mb-2"><i class="bi bi-flower2"></i></span><h1 class="h3"><?= $wantsAdmin ? 'Admin login' : 'Welcome back' ?></h1><p class="small-muted"><?= $wantsAdmin ? 'Log in with an administrator account.' : 'Log in to manage your funeral accounts.' ?></p></div>
      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php if ($wantsAdmin): ?><input type="hidden" name="admin" value="1"><?php endif; ?>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-lg" required autofocus value="<?= e(input('email')) ?>"></div>
        <div class="mb-2"><label class="form-label">Password</label><input type="password" name="password" class="form-control form-control-lg" required></div>
        <div class="text-end mb-3"><a href="<?= e(url('forgot.php')) ?>" class="small">Forgot password?</a></div>
        <button class="btn btn-ink btn-lg w-100 rounded-pill">Log in</button>
      </form>
      <p class="text-center small mt-4 mb-0">New here? <a href="<?= e(url('create.php')) ?>">Create a funeral account</a></p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
