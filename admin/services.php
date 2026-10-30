<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
if (is_post()) {
    if (input('act') === 'save') {
        foreach ((array) ($_POST['svc'] ?? []) as $id => $s) {
            update('services', [
                'name' => mb_substr(trim($s['name'] ?? ''), 0, 120) ?: 'Service',
                'description' => trim($s['description'] ?? ''),
                'icon' => preg_replace('/[^a-z0-9\-]/', '', $s['icon'] ?? '') ?: 'bi-check2-circle',
                'price' => max(0, (float) ($s['price'] ?? 0)),
                'is_standard' => !empty($s['is_standard']) ? 1 : 0,
                'is_active' => !empty($s['is_active']) ? 1 : 0,
                'sort' => (int) ($s['sort'] ?? 0),
            ], 'id = ?', [(int) $id]);
        }
        flash('success', 'Services & pricing saved.');
    } elseif (input('act') === 'add') {
        $code = slugify(input('code') ?: input('name'));
        $code = str_replace('-', '_', $code);
        if (val('SELECT id FROM services WHERE code = ?', [$code])) {
            flash('danger', 'A service with that code already exists.');
        } else {
            insert('services', ['code' => $code, 'name' => input('name'), 'description' => input('description'), 'price' => (float) input('price'), 'icon' => 'bi-stars', 'sort' => 99]);
            flash('success', 'Service added. Note: new services appear in packages; feature-gating in the dashboard applies to the built-in codes.');
        }
    }
    redirect(url('admin/services.php'));
}
$services = rows('SELECT s.*, (SELECT COUNT(*) FROM funeral_services fs WHERE fs.service_id = s.id) AS used FROM services s ORDER BY sort');
admin_header('Services & pricing', 'services');
?>
<div class="alert alert-light border small"><i class="bi bi-info-circle"></i> Prices are in <?= e(setting('currency')) ?>. <strong>Standard</strong> services are included free with every account (e.g. the preparations workspace) — set a price and untick “standard” later if you want to start charging for it.</div>
<form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="save">
<div class="admin-card card"><div class="table-responsive"><table class="table align-middle mb-0 small">
  <thead><tr class="text-muted"><th style="width:60px">Order</th><th>Service</th><th style="width:130px">Price</th><th>Icon</th><th>Standard</th><th>Active</th><th>Used by</th></tr></thead><tbody>
  <?php foreach ($services as $s): $n = 'svc[' . (int) $s['id'] . ']'; ?>
    <tr>
      <td><input type="number" name="<?= $n ?>[sort]" value="<?= (int) $s['sort'] ?>" class="form-control form-control-sm"></td>
      <td><input name="<?= $n ?>[name]" value="<?= e($s['name']) ?>" class="form-control form-control-sm fw-semibold mb-1"><textarea name="<?= $n ?>[description]" rows="2" class="form-control form-control-sm"><?= e($s['description']) ?></textarea><code class="small"><?= e($s['code']) ?></code></td>
      <td><input type="number" step="1" min="0" name="<?= $n ?>[price]" value="<?= (float) $s['price'] ?>" class="form-control form-control-sm"></td>
      <td><div class="input-group input-group-sm"><span class="input-group-text"><i class="bi <?= e($s['icon']) ?>"></i></span><input name="<?= $n ?>[icon]" value="<?= e($s['icon']) ?>" class="form-control"></div></td>
      <td><input type="checkbox" class="form-check-input" name="<?= $n ?>[is_standard]" value="1" <?= $s['is_standard'] ? 'checked' : '' ?>></td>
      <td><input type="checkbox" class="form-check-input" name="<?= $n ?>[is_active]" value="1" <?= $s['is_active'] ? 'checked' : '' ?>></td>
      <td><?= (int) $s['used'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div></div>
<button class="btn btn-dark mt-3"><i class="bi bi-check2"></i> Save all</button>
</form>
<div class="admin-card card p-4 mt-4" style="max-width:640px">
  <h2 class="h6">Add a new service</h2>
  <form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="act" value="add">
    <div class="col-md-6"><input name="name" class="form-control" placeholder="Name" required></div>
    <div class="col-md-3"><input name="code" class="form-control" placeholder="code (optional)"></div>
    <div class="col-md-3"><input type="number" name="price" class="form-control" placeholder="Price" value="5000"></div>
    <div class="col-12"><textarea name="description" class="form-control" rows="2" placeholder="Description"></textarea></div>
    <div class="col-12"><button class="btn btn-outline-dark">Add service</button></div>
  </form>
</div>
<?php admin_footer();
