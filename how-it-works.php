<?php
require __DIR__ . '/includes/bootstrap.php';
$active = 'how';
$p = page('how-it-works');
$pageTitle = $p['title'] ?? 'How it works';
$metaDesc = $p['meta_description'] ?? '';
require __DIR__ . '/includes/header.php';
$steps = [
    ['bi-ui-checks-grid', 'Choose your package', 'Go to “Create a funeral account”, tick the services you need — tribute page, programme, QR codes, printable memorial, QR plaque, live stream or photography. Each is a simple add-on and the preparations workspace is always free.'],
    ['bi-phone-vibrate', 'Pay with M-Pesa', 'Enter your Safaricom number and approve the STK push on your phone. Prefer the Paybill? Pay with your order number as the account number and we match it automatically.'],
    ['bi-person-heart', 'Build the tribute', 'Your funeral account opens instantly. Add the photo, life story, obituary, family details and gallery. Choose your colours, font and upload your logo so the page reflects your family or business.'],
    ['bi-geo-alt', 'Plan the programme', 'Add every event — viewing, cortege, service, burial, reception — with times and exact GPS pins. Add the order of service. Mourners get one-tap directions in Google Maps, Apple Maps or Waze.'],
    ['bi-link-45deg', 'Share one link & a form', 'Share the tribute page and programme on WhatsApp, SMS and social media. Send the tribute form link so relatives and friends add their own messages and photos — you approve them before they appear.'],
    ['bi-qr-code-scan', 'Print & go paperless', 'Download QR codes for banners and invitations, print memorial cards, the programme booklet and a QR plaque that links to the life story forever.'],
];
?>
<section class="page-hero"><div class="container"><div class="eyebrow">Simple & guided</div><h1><?= e($pageTitle) ?></h1><p><?= e($p['subtitle'] ?? '') ?></p></div></section>
<section class="section">
  <div class="container" style="max-width:960px">
    <?php if (!empty($p['content'])): ?><div class="prose text-center mb-5"><?= $p['content'] ?></div><?php endif; ?>
    <?php foreach ($steps as $i => [$icon, $t, $d]): ?>
    <div class="d-flex gap-4 mb-4 card-soft p-4">
      <div class="text-center"><span class="step-num"><?= $i + 1 ?></span></div>
      <div><h2 class="h4"><i class="bi <?= $icon ?> text-gold me-2"></i><?= $t ?></h2><p class="mb-0 text-secondary"><?= $d ?></p></div>
    </div>
    <?php endforeach; ?>
    <div class="card-soft p-4 mt-5 bg-cream">
      <div class="row align-items-center g-3">
        <div class="col-md-8"><h3 class="h4 mb-1"><i class="bi bi-folder2-open text-gold me-2"></i>Planning ahead? Use the free preparations workspace.</h3><p class="mb-0 text-secondary">Upload documents (burial permit, IDs, quotes), keep committee notes, assign tasks and track the funeral budget against contributions — privately.</p></div>
        <div class="col-md-4 text-md-end"><a href="<?= e(url('create.php')) ?>" class="btn btn-gold rounded-pill px-4">Get started</a></div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
