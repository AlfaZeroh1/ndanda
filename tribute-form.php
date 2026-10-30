<?php
/** Shareable form link (/f/{token}) so family & friends can submit tributes. */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/tribute_submit.php';
$f = row("SELECT * FROM funerals WHERE form_token = ? AND status = 'active'", [input('t')]);
if (!$f || !has_service($f, 'tribute_page')) {
    http_response_code(404);
    exit('This tribute form link is not active.');
}
if (is_post()) handle_tribute_submission($f, form_url($f) . '?sent=1', form_url($f));

$pageTitle = 'Share a tribute for ' . $f['deceased_name'];
$hideNav = true;
$hideFooter = true;
$hideTestBanner = true;
$deferFlash = true;
$bodyClass = 'memorial';
$bodyAttr = 'style="' . e(brand_style($f)) . '"';
$extraHead = '<link href="' . e(brand_font_href($f)) . '" rel="stylesheet">';
require __DIR__ . '/includes/header.php';
?>
<header class="m-hero" style="min-height:220px;<?= $f['cover'] ? 'background-image:url(' . e(img($f['cover'])) . ')' : '' ?>">
  <div class="container m-brandbar"><?php if ($f['brand_logo']): ?><img src="<?= e(img($f['brand_logo'])) ?>" alt=""><?php endif; ?></div>
</header>
<section class="container text-center" style="margin-top:-90px;position:relative;z-index:2">
  <?= portrait($f, 'm-portrait', 150) ?>
  <div class="m-heading"><?= e($f['heading']) ?></div>
  <h1 class="m-name" style="font-size:2.2rem"><?= e($f['deceased_name']) ?></h1>
  <div class="m-years"><?= e(life_years($f)) ?></div>
</section>
<section class="m-section pt-4">
  <div class="container" style="max-width:620px">
    <div class="m-card">
      <?php if (input('sent')): ?>
        <div class="text-center py-3">
          <i class="bi bi-heart-fill" style="font-size:3rem;color:var(--brand-accent)"></i>
          <h2 class="h4 mt-2">Thank you for your tribute</h2>
          <p class="text-muted"><?= $f['moderate_tributes'] ? 'The family will review it shortly.' : 'It is now on the tribute page.' ?></p>
          <a href="<?= e(tribute_url($f)) ?>" class="btn btn-brand rounded-pill">View the tribute page</a>
          <a href="<?= e(form_url($f)) ?>" class="btn btn-link">Write another</a>
        </div>
      <?php else: ?>
        <h2 class="h4 m-font">Share your memories</h2>
        <p class="text-muted small">The family of <?= e($f['deceased_name']) ?> invites you to share a tribute, a memory or a condolence message. You may also add a photo.</p>
        <?= flashes() ?>
        <?php $formAction = form_url($f); require __DIR__ . '/includes/tribute_form.php'; ?>
      <?php endif; ?>
    </div>
    <div class="text-center mt-3"><a href="<?= e(tribute_url($f)) ?>" class="small" style="color:var(--brand)">Visit the full tribute page →</a></div>
  </div>
</section>
<footer class="m-footer"><div class="small opacity-75">Powered by <?= e(setting('site_name')) ?></div></footer>
<?php require __DIR__ . '/includes/footer.php'; ?>
