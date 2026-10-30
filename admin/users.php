<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
$me = require_admin();
if (is_post()) {
    $id = (int) input('id');
    $act = input('act');
    if ($id === (int) $me['id'] && in_array($act, ['suspend', 'role', 'delete'], true)) {
        flash('danger', 'You cannot change your own account here.');
    } elseif ($act === 'suspend') {
        q("UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE id = ?", [$id]);
        flash('success', 'User status changed.');
    } elseif ($act === 'role') {
        q("UPDATE users SET role = IF(role = 'admin', 'planner', 'admin') WHERE id = ?", [$id]);
        flash('success', 'Role changed.');
    } elseif ($act === 'password') {
        if (strlen((string) input('password')) < 6) flash('danger', 'Password must be at least 6 characters.');
        else { q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash(input('password'), PASSWORD_DEFAULT), $id]); flash('success', 'Password updated.'); }
    } elseif ($act === 'delete') {
        q('DELETE FROM users WHERE id = ?', [$id]);
        flash('success', 'User and their funeral accounts deleted. Payment records were kept.');
    } elseif ($act === 'create') {
        $email = strtolower(trim(input('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) input('password')) < 6 || trim(input('name')) === '') flash('danger', 'Name, valid email and a 6+ character password are required.');
        elseif (val('SELECT id FROM users WHERE email = ?', [$email])) flash('danger', 'That email is already registered.');
        else { insert('users', ['name' => input('name'), 'email' => $email, 'phone' => normalize_phone(input('phone')), 'password_hash' => password_hash(input('password'), PASSWORD_DEFAULT), 'role' => input('role') === 'admin' ? 'admin' : 'planner', 'is_test' => 0]); flash('success', 'User created.'); }
    }
    back(url('admin/users.php'));
}
$qs = trim(input('q'));
$params = [];
$where = '1=1';
if ($qs !== '') { $where = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)'; $l = "%$qs%"; $params = [$l, $l, $l]; }
if (input('role')) { $where .= ' AND role = ?'; $params[] = input('role'); }
$users = rows("SELECT u.*, (SELECT COUNT(*) FROM funerals f WHERE f.user_id = u.id) AS fcount, (SELECT COALESCE(SUM(amount),0) FROM orders o WHERE o.user_id = u.id AND o.status = 'paid') AS spent FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT 200", $params);
admin_header('Users', 'users');
?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <form class="d-flex gap-2"><input name="q" value="<?= e($qs) ?>" class="form-control" placeholder="Search users"><select name="role" class="form-select"><option value="">All roles</option><option value="planner" <?= input('role') === 'planner' ? 'selected' : '' ?>>Planners</option><option value="admin" <?= input('role') === 'admin' ? 'selected' : '' ?>>Admins</option></select><button class="btn btn-dark">Search</button></form>
  <button class="btn btn-outline-dark ms-auto" data-bs-toggle="collapse" data-bs-target="#newUser"><i class="bi bi-person-plus"></i> Add user</button>
</div>
<div class="collapse mb-3" id="newUser"><div class="admin-card card p-3"><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="act" value="create">
  <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div><div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
  <div class="col-md-2"><input name="phone" class="form-control" placeholder="Phone"></div><div class="col-md-2"><input name="password" class="form-control" placeholder="Password" required></div>
  <div class="col-md-1"><select name="role" class="form-select"><option value="planner">Planner</option><option value="admin">Admin</option></select></div><div class="col-md-1"><button class="btn btn-dark w-100">Add</button></div>
</form></div></div>
<div class="admin-card card"><div class="table-responsive"><table class="table align-middle mb-0 small">
  <thead><tr class="text-muted"><th>User</th><th>Role</th><th>Funerals</th><th>Spent</th><th>Last login</th><th></th></tr></thead><tbody>
  <?php foreach ($users as $u): ?>
    <tr><td><strong><?= e($u['name']) ?></strong> <?= $u['status'] !== 'active' ? status_badge('suspended') : '' ?><?= $u['is_test'] ? ' <span class="badge text-bg-warning">test</span>' : '' ?><div class="text-muted"><?= e($u['email']) ?> · <?= e($u['phone']) ?></div></td>
      <td><span class="badge <?= $u['role'] === 'admin' ? 'text-bg-dark' : 'text-bg-light border' ?>"><?= e($u['role']) ?></span></td>
      <td><a href="funerals.php?q=<?= urlencode($u['email']) ?>"><?= (int) $u['fcount'] ?></a></td><td><?= e(money($u['spent'])) ?></td>
      <td class="text-muted"><?= $u['last_login'] ? e(time_ago($u['last_login'])) : 'never' ?></td>
      <td class="text-end text-nowrap">
        <div class="dropdown"><button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Actions</button>
          <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:240px">
            <form method="post" class="mb-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button name="act" value="suspend" class="dropdown-item"><?= $u['status'] === 'active' ? 'Suspend' : 'Re-activate' ?></button><button name="act" value="role" class="dropdown-item">Make <?= $u['role'] === 'admin' ? 'planner' : 'admin' ?></button></form>
            <form method="post" class="d-flex gap-1 mb-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="act" value="password"><input name="password" class="form-control form-control-sm" placeholder="New password"><button class="btn btn-sm btn-dark">Set</button></form>
            <form method="post" data-confirm="Delete this user and all their funeral accounts?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button name="act" value="delete" class="dropdown-item text-danger">Delete user</button></form>
          </div></div>
      </td></tr>
  <?php endforeach; ?>
  </tbody></table></div></div>
<?php admin_footer();
