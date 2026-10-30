<?php
require __DIR__ . '/includes/bootstrap.php';
$active = 'create';
$pageTitle = 'Create a funeral account';
$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort');
$user = current_user();
$errors = [];
$old = $_POST;
$selected = array_map('intval', (array) ($_POST['services'] ?? $_GET['svc'] ?? []));
if (!empty($_GET['all'])) $selected = array_map('intval', array_column($services, 'id'));
if (!$selected && !is_post()) {
    $tp = service_by_code('tribute_page');
    if ($tp) $selected = [(int) $tp['id']];
}

if (is_post()) {
    $deceased = trim($_POST['deceased_name'] ?? '');
    if ($deceased === '') $errors[] = 'Enter the name of your loved one.';
    if (empty($_POST['agree'])) $errors[] = 'Please accept the terms of service.';

    if (!$user) {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = normalize_phone($_POST['phone'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($name === '') $errors[] = 'Enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (!$phone) $errors[] = 'Enter a valid Safaricom/Kenyan phone number, e.g. 0712 345 678.';
        if (strlen($pass) < 6) $errors[] = 'Choose a password of at least 6 characters.';
        if (!$errors) {
            $existing = row('SELECT * FROM users WHERE email = ?', [$email]);
            if ($existing) {
                if (password_verify($pass, $existing['password_hash']) && $existing['status'] === 'active') {
                    $user = $existing;
                } else {
                    $errors[] = 'An account with this email already exists. Enter its correct password or log in first.';
                }
            } else {
                $uid = insert('users', [
                    'name' => $name, 'email' => $email, 'phone' => $phone,
                    'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'planner', 'is_test' => test_flag(),
                ]);
                $user = row('SELECT * FROM users WHERE id = ?', [$uid]);
            }
            if ($user) login_user($user);
        }
    }

    if (!$errors) {
        $fid = insert('funerals', [
            'user_id'       => $user['id'],
            'slug'          => unique_slug('funerals', $deceased),
            'form_token'    => bin2hex(random_bytes(10)),
            'deceased_name' => $deceased,
            'heading'       => trim($_POST['heading'] ?? '') ?: 'Celebrating the life of',
            'birth_date'    => ($_POST['birth_date'] ?? '') ?: null,
            'death_date'    => ($_POST['death_date'] ?? '') ?: null,
            'brand_name'    => trim($_POST['brand_name'] ?? '') ?: null,
            'status'        => 'pending_payment',
            'is_test'       => test_flag(),
        ]);
        grant_standard_services($fid);
        $order = create_order((int) $user['id'], $fid, $selected);
        if (!$order) {
            // Nothing to pay for: only the free workspace was chosen
            q("UPDATE funerals SET status = 'active' WHERE id = ?", [$fid]);
            flash('success', 'Your funeral account is ready. You can add services anytime.');
            redirect(url('account/funeral.php?id=' . $fid));
        }
        if ($order['status'] === 'paid') redirect(url('account/funeral.php?id=' . $fid));
        redirect(url('checkout.php?ref=' . $order['reference']));
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero py-5"><div class="container"><div class="eyebrow">Step 1 of 2</div><h1>Create a funeral account</h1><p>Choose your package, tell us whose life we are celebrating, and pay with M-Pesa.</p></div></section>
<section class="section-sm bg-cream">
  <div class="container">
    <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" class="row g-4" novalidate>
      <?= csrf_field() ?>
      <div class="col-lg-8">
        <div class="card-soft p-4 mb-4">
          <h2 class="h4 mb-1">1. Choose your package</h2>
          <p class="small-muted mb-4">Tick what you need — each item is <?= e(money($services[0]['price'] ?? 5000)) ?>. You can add more later.</p>
          <div class="row g-3">
            <?php foreach ($services as $s): $isStd = (int) $s['is_standard'] === 1; ?>
            <div class="col-md-6">
              <label class="svc-option<?= $isStd ? ' locked checked' : '' ?>">
                <input type="checkbox" name="services[]" value="<?= (int) $s['id'] ?>" data-price="<?= (float) $s['price'] ?>" data-name="<?= e($s['name']) ?>" <?= $isStd || in_array((int) $s['id'], $selected, true) ? 'checked' : '' ?> <?= $isStd ? 'disabled' : '' ?>>
                <div class="d-flex justify-content-between gap-2 mb-1">
                  <strong><i class="bi <?= e($s['icon']) ?> svc-icon me-1"></i> <?= e($s['name']) ?></strong>
                  <span class="svc-price"><?= $s['price'] > 0 ? e(money($s['price'])) : 'Free' ?></span>
                </div>
                <div class="small text-secondary"><?= e(excerpt($s['description'], 120)) ?></div>
                <?php if ($isStd): ?><div class="small text-success mt-1"><i class="bi bi-check2-circle"></i> Included as standard</div><?php endif; ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card-soft p-4 mb-4">
          <h2 class="h4 mb-3">2. Whose life are we celebrating?</h2>
          <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Full name of the deceased *</label><input name="deceased_name" class="form-control form-control-lg" required value="<?= e($old['deceased_name'] ?? '') ?>" placeholder="e.g. Joseph Kamau Njoroge"></div>
            <div class="col-md-4"><label class="form-label">Heading</label>
              <select name="heading" class="form-select form-select-lg">
                <?php foreach (['Celebrating the life of', 'In loving memory of', 'Forever in our hearts', 'Rest in peace', 'Promoted to glory'] as $h): ?><option <?= ($old['heading'] ?? '') === $h ? 'selected' : '' ?>><?= $h ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-md-6"><label class="form-label">Date of birth</label><input type="date" name="birth_date" class="form-control" value="<?= e($old['birth_date'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Date of passing</label><input type="date" name="death_date" class="form-control" value="<?= e($old['death_date'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label">Planner / funeral home name <span class="small-muted">(optional – shown as “Presented by”)</span></label><input name="brand_name" class="form-control" value="<?= e($old['brand_name'] ?? '') ?>" placeholder="e.g. Kamau Family or Your Funeral Services Ltd"></div>
          </div>
        </div>

        <?php if (!$user): ?>
        <div class="card-soft p-4">
          <h2 class="h4 mb-1">3. Your details</h2>
          <p class="small-muted mb-3">This creates your login. Already have an account? <a href="<?= e(url('login.php?next=' . urlencode('/' . ltrim(parse_url(url('create.php'), PHP_URL_PATH), '/')))) ?>">Log in first</a>.</p>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full name *</label><input name="name" class="form-control" required value="<?= e($old['name'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">M-Pesa phone number *</label><input name="phone" class="form-control" required inputmode="tel" placeholder="07XX XXX XXX" value="<?= e($old['phone'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?= e($old['email'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" minlength="6" required></div>
          </div>
        </div>
        <?php else: ?>
        <div class="alert alert-light border"><i class="bi bi-person-check me-2"></i>Creating as <strong><?= e($user['name']) ?></strong> (<?= e($user['email']) ?>).</div>
        <?php endif; ?>
      </div>

      <div class="col-lg-4">
        <div class="card-soft p-4 order-summary">
          <h3 class="h5">Order summary</h3>
          <ul class="list-unstyled small mb-3 border-bottom pb-3" id="summaryList"></ul>
          <div class="d-flex justify-content-between align-items-end mb-3"><span class="text-muted">Total</span><span class="total-line" data-total>KES 0</span></div>
          <div class="form-check small mb-3"><input class="form-check-input" type="checkbox" name="agree" value="1" id="agree" <?= !empty($old['agree']) ? 'checked' : '' ?>><label class="form-check-label" for="agree">I accept the <a href="<?= e(url('page.php?s=terms')) ?>" target="_blank">terms</a> and <a href="<?= e(url('page.php?s=privacy')) ?>" target="_blank">privacy policy</a>.</label></div>
          <button class="btn btn-gold btn-lg w-100 rounded-pill"><i class="bi bi-arrow-right-circle me-1"></i> Create & continue</button>
          <div class="text-center small-muted mt-3"><i class="bi bi-shield-lock"></i> Next: secure M-Pesa payment</div>
        </div>
      </div>
    </form>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
