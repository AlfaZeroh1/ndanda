<?php
/**
 * Command-line tool to list admins and reset (or create) an admin login.
 * Run from the server terminal only:
 *   php database/reset-admin.php                       -> lists all users
 *   php database/reset-admin.php you@example.com 'NewPassword123'  -> sets password, makes admin, re-activates
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('INSTALLING', true); // skip the web-only install redirect & CSRF
define('NO_SESSION', true);
require __DIR__ . '/../includes/bootstrap.php';

[$script, $email, $pass] = $argv + [null, null, null];

if (!$email) {
    echo str_pad('ID', 5) . str_pad('ROLE', 10) . str_pad('STATUS', 11) . str_pad('TEST', 6) . "EMAIL\n";
    foreach (rows('SELECT id, email, role, status, is_test FROM users ORDER BY role, id') as $u) {
        echo str_pad($u['id'], 5) . str_pad($u['role'], 10) . str_pad($u['status'], 11) . str_pad($u['is_test'] ? 'yes' : '', 6) . $u['email'] . "\n";
    }
    echo "\nTo reset: php database/reset-admin.php EMAIL 'NEW_PASSWORD'\n";
    exit(0);
}
if (strlen((string) $pass) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}
$email = strtolower(trim($email));
$hash = password_hash($pass, PASSWORD_DEFAULT);
$u = row('SELECT * FROM users WHERE LOWER(email) = ?', [$email]);
if ($u) {
    update('users', ['email' => $email, 'password_hash' => $hash, 'role' => 'admin', 'status' => 'active', 'is_test' => 0], 'id = ?', [$u['id']]);
    echo "Updated {$email}: password reset, role=admin, status=active.\n";
} else {
    insert('users', ['name' => 'Administrator', 'email' => $email, 'password_hash' => $hash, 'role' => 'admin']);
    echo "Created new admin {$email}.\n";
}
