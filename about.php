<?php
require __DIR__ . '/includes/bootstrap.php';
$active = 'about';
$p = page('about');
$pageTitle = $p['title'] ?? 'About us';
$metaDesc = $p['meta_description'] ?? '';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="eyebrow"><?= e(setting('company_name')) ?></div><h1><?= e($pageTitle) ?></h1><p><?= e($p['subtitle'] ?? '') ?></p></div></section>
<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-7"><div class="prose"><?= $p['content'] ?? '' ?></div></div>
      <div class="col-lg-5">
        <div class="card-soft p-4 mb-4">
          <h3 class="h5">What Ndanda Events does</h3>
          <ul class="list-unstyled mb-0">
            <?php foreach (['bi-heart' => 'Weddings & private celebrations', 'bi-building' => 'Corporate events & galas', 'bi-rocket-takeoff' => 'Launches & brand activations', 'bi-speaker' => 'Production & staging – lighting, sound, AV, set design', 'bi-flower1' => 'Celebrations of life – memorials & tributes', 'bi-camera-reels' => 'Media coverage, photography & live streaming'] as $i => $t): ?>
              <li class="d-flex gap-3 py-2 border-bottom"><i class="bi <?= $i ?> text-gold fs-5"></i><span><?= $t ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="card-soft p-4 bg-ink text-white">
          <h3 class="h5 text-white">Talk to our team</h3>
          <p class="small text-white-50">We'll help you set up the tribute, programme and coverage.</p>
          <a href="tel:<?= e(preg_replace('/\s+/', '', setting('company_phone'))) ?>" class="btn btn-gold rounded-pill me-2"><i class="bi bi-telephone"></i> Call</a>
          <a href="https://wa.me/<?= e(setting('company_whatsapp')) ?>" class="btn btn-outline-light rounded-pill" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
