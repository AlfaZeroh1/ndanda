<?php
/**
 * One-time installer: creates tables, base content, optional sample data and the first admin.
 * Locked afterwards by database/install.lock (delete that file to reinstall).
 */
define('INSTALLING', true);
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';

$lock = __DIR__ . '/database/install.lock';
if (file_exists($lock)) {
    exit('Already installed. Delete database/install.lock to run the installer again.');
}

function run_sql_file(PDO $pdo, string $file, array $replace = []): void
{
    $sql = strtr(file_get_contents($file), $replace);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
        if (trim($stmt) !== '') $pdo->exec($stmt);
    }
}

$error = '';
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
        $error = 'Enter a name, a valid email and a password of at least 8 characters.';
    } else {
        try {
            $pdo = db();
            run_sql_file($pdo, __DIR__ . '/database/schema.sql');
            run_sql_file($pdo, __DIR__ . '/database/base.sql');
            set_setting('mpesa_callback_secret', bin2hex(random_bytes(12)));
            insert('users', ['name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'admin']);
            if (!empty($_POST['sample'])) {
                insert('users', ['name' => 'Test Planner', 'email' => 'planner@test.com', 'phone' => '254712000111', 'password_hash' => password_hash('Test@1234', PASSWORD_DEFAULT), 'role' => 'planner', 'is_test' => 1]);
                run_sql_file($pdo, __DIR__ . '/database/sample.sql');
            }
            file_put_contents($lock, date('c'));
            $done = true;
        } catch (Throwable $e) {
            $error = 'Installation failed: ' . $e->getMessage() . ' — check DB settings in config.php.';
        }
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install · Ndanda Tributes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<div class="container py-5" style="max-width:560px">
  <h1 class="h3 mb-1">Ndanda Tributes installer</h1>
  <p class="text-muted">Database: <code><?= e(DB_NAME) ?></code> on <code><?= e(DB_HOST) ?></code> · URL: <code><?= e(BASE_URL) ?></code></p>
  <?php if ($done): ?>
    <div class="alert alert-success">
      <strong>Installed!</strong> Log in as admin at <a href="<?= e(url('login.php')) ?>">login</a>.
      <?php if (!empty($_POST['sample'])): ?><br>Test planner account: <code>planner@test.com</code> / <code>Test@1234</code><?php endif; ?>
    </div>
  <?php else: ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="card card-body shadow-sm">
      <h2 class="h6">Create the first admin</h2>
      <div class="mb-3"><label class="form-label">Full name</label><input name="name" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Password (min 8)</label><input type="password" name="password" class="form-control" minlength="8" required></div>
      <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="sample" value="1" id="s" checked><label class="form-check-label" for="s">Load sample test data (removable via Admin → Go Live)</label></div>
      <button class="btn btn-dark w-100">Install</button>
    </form>
  <?php endif; ?>
</div></body></html>
