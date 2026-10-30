<?php
/**
 * Shared template for Live stream / Photography pages: CMS content + booking request form.
 * Expects $slug ('livestream'|'photography'), $active, $icon.
 */
$p = page($slug);
$svc = service_by_code($slug);
if (is_post()) {
    $name = trim(input('name'));
    $phone = trim(input('phone'));
    if ($name === '' || $phone === '' || input('website') !== '') {
        flash('danger', 'Please enter your name and phone number.');
    } elseif (!throttle('msg_' . $slug, 30)) {
        flash('warning', 'Please wait a moment before sending another request.');
    } else {
        insert('messages', [
            'type' => $slug, 'name' => mb_substr($name, 0, 120), 'email' => filter_var(input('email'), FILTER_VALIDATE_EMAIL) ?: null,
            'phone' => mb_substr($phone, 0, 30), 'subject' => ucfirst($slug) . ' booking request',
            'message' => mb_substr(input('message') ?: '-', 0, 4000), 'event_date' => input('event_date') ?: null, 'is_test' => test_flag(),
        ]);
        send_mail(setting('admin_notify_email'), 'New ' . $slug . ' request from ' . $name, '<p>' . e($name) . ' (' . e($phone) . ') requested ' . e($slug) . ' for ' . e(input('event_date')) . '.</p><p>' . nl2br(e(input('message'))) . '</p>');
        flash('success', 'Thank you! Our team will call you shortly to confirm the details.');
    }
    redirect(url($slug . '.php#book'));
}
$pageTitle = $p['title'] ?? ucfirst($slug);
$metaDesc = $p['meta_description'] ?? '';
$upcoming = $slug === 'livestream' ? rows("SELECT f.* FROM funerals f JOIN funeral_services fs ON fs.funeral_id = f.id JOIN services s ON s.id = fs.service_id AND s.code = 'livestream' WHERE f.status = 'active' AND f.visibility = 'public' AND f.livestream_url IS NOT NULL AND f.livestream_url <> '' AND f.livestream_at >= NOW() - INTERVAL 6 HOUR ORDER BY f.livestream_at LIMIT 6") : [];
require __DIR__ . '/header.php';
?>
<section class="page-hero"><div class="container"><div class="eyebrow"><i class="bi <?= e($icon) ?>"></i> Ndanda Events media</div><h1><?= e($pageTitle) ?></h1><p><?= e($p['subtitle'] ?? '') ?></p></div></section>
<?php if ($upcoming): ?>
<section class="section-sm bg-cream">
  <div class="container">
    <h2 class="h4 mb-3"><span class="live-pill me-2">LIVE</span> Upcoming & live streams</h2>
    <div class="row g-3">
      <?php foreach ($upcoming as $f): ?>
      <div class="col-md-6 col-lg-4"><a href="<?= e(tribute_url($f)) ?>#live" class="card-soft d-flex gap-3 p-3 align-items-center text-decoration-none text-reset hover-lift">
        <?= portrait($f, 'rounded-circle', 64) ?>
        <div><div class="fw-semibold"><?= e($f['deceased_name']) ?></div><div class="small text-muted"><i class="bi bi-clock"></i> <?= e(fmt_date($f['livestream_at'], 'D j M, g:i A')) ?></div></div>
      </a></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-7">
        <div class="prose"><?= $p['content'] ?? '' ?></div>
        <?php if ($svc): ?><div class="card-soft p-4 mt-4 d-flex justify-content-between align-items-center flex-wrap gap-3"><div><div class="small text-muted">Add to any funeral account</div><div class="h4 mb-0"><?= e(money($svc['price'])) ?></div></div><a href="<?= e(url('create.php?svc[]=' . $svc['id'])) ?>" class="btn btn-gold rounded-pill px-4">Add to my package</a></div><?php endif; ?>
      </div>
      <div class="col-lg-5" id="book">
        <div class="card-soft p-4">
          <h2 class="h4">Request a booking</h2>
          <p class="small-muted">Tell us about the funeral and we'll get back to you within the hour.</p>
          <form method="post">
            <?= csrf_field() ?><input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
            <div class="mb-2"><input name="name" class="form-control" placeholder="Your name *" required></div>
            <div class="row g-2 mb-2"><div class="col-6"><input name="phone" class="form-control" placeholder="Phone *" required></div><div class="col-6"><input type="email" name="email" class="form-control" placeholder="Email"></div></div>
            <div class="mb-2"><label class="small text-muted">Funeral date</label><input type="date" name="event_date" class="form-control"></div>
            <div class="mb-3"><textarea name="message" class="form-control" rows="4" placeholder="Location, expected guests, special requests…"></textarea></div>
            <button class="btn btn-ink w-100 rounded-pill">Send request</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/footer.php';
