<?php
require __DIR__ . '/includes/bootstrap.php';
$active = 'contact';
$pageTitle = 'Contact us';
if (is_post()) {
    $name = trim(input('name'));
    $msg = trim(input('message'));
    if ($name === '' || $msg === '' || input('website') !== '') {
        flash('danger', 'Please enter your name and message.');
    } elseif (!throttle('contact', 30)) {
        flash('warning', 'Please wait a moment before sending another message.');
    } else {
        insert('messages', [
            'type' => 'contact', 'name' => mb_substr($name, 0, 120), 'email' => filter_var(input('email'), FILTER_VALIDATE_EMAIL) ?: null,
            'phone' => mb_substr(input('phone'), 0, 30), 'subject' => mb_substr(input('subject'), 0, 200), 'message' => mb_substr($msg, 0, 5000), 'is_test' => test_flag(),
        ]);
        send_mail(setting('admin_notify_email'), 'Contact form: ' . input('subject'), '<p><strong>' . e($name) . '</strong> (' . e(input('email')) . ', ' . e(input('phone')) . ')</p><p>' . nl2br(e($msg)) . '</p>');
        flash('success', 'Thank you for reaching out. We will get back to you shortly.');
    }
    redirect(url('contact.php'));
}
require __DIR__ . '/includes/header.php';
$tel = preg_replace('/\s+/', '', setting('company_phone'));
?>
<section class="page-hero"><div class="container"><div class="eyebrow">We're here for you</div><h1>Contact us</h1><p>Questions about a package, a live stream or setting up a tribute? Our team responds quickly — especially on WhatsApp.</p></div></section>
<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-5">
        <div class="d-grid gap-3">
          <a href="tel:<?= e($tel) ?>" class="card-soft p-4 d-flex gap-3 align-items-center text-decoration-none text-reset hover-lift"><div class="feature-icon"><i class="bi bi-telephone"></i></div><div><div class="small text-muted">Call us</div><div class="fw-semibold"><?= e(setting('company_phone')) ?></div></div></a>
          <a href="https://wa.me/<?= e(setting('company_whatsapp')) ?>" target="_blank" rel="noopener" class="card-soft p-4 d-flex gap-3 align-items-center text-decoration-none text-reset hover-lift"><div class="feature-icon" style="color:#25d366"><i class="bi bi-whatsapp"></i></div><div><div class="small text-muted">WhatsApp</div><div class="fw-semibold">Chat with us</div></div></a>
          <a href="mailto:<?= e(setting('company_email')) ?>" class="card-soft p-4 d-flex gap-3 align-items-center text-decoration-none text-reset hover-lift"><div class="feature-icon"><i class="bi bi-envelope"></i></div><div><div class="small text-muted">Email</div><div class="fw-semibold"><?= e(setting('company_email')) ?></div></div></a>
          <div class="card-soft p-4 d-flex gap-3 align-items-center"><div class="feature-icon"><i class="bi bi-geo-alt"></i></div><div><div class="small text-muted">Visit</div><div class="fw-semibold"><?= e(setting('company_address')) ?></div></div></div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="card-soft p-4 p-md-5">
          <h2 class="h4 mb-3">Send us a message</h2>
          <form method="post">
            <?= csrf_field() ?><input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Name *</label><input name="name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Subject</label><select name="subject" class="form-select"><option>General enquiry</option><option>Package / pricing</option><option>Live streaming</option><option>Photography</option><option>Funeral home / planner partnership</option><option>Technical support</option></select></div>
              <div class="col-12"><label class="form-label">Message *</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
              <div class="col-12"><button class="btn btn-gold btn-lg rounded-pill px-5">Send message</button></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
