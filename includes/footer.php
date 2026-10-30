</main>
<?php if (empty($hideFooter)):
    $footerPages = rows('SELECT slug, title FROM pages WHERE show_in_footer = 1 AND is_published = 1 ORDER BY title');
?>
<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3"><span class="brand-mark"><i class="bi bi-flower2"></i></span><strong class="fs-5 text-white"><?= e(setting('site_name')) ?></strong></div>
        <p class="small"><?= e(setting('site_tagline')) ?>. A digital funeral platform by <?= e(setting('company_name')) ?> — moments made unforgettable.</p>
        <div class="d-flex gap-2 social">
          <?php foreach (['social_instagram' => 'instagram', 'social_youtube' => 'youtube', 'social_facebook' => 'facebook', 'social_tiktok' => 'tiktok'] as $k => $i): if (setting($k)): ?>
            <a href="<?= e(setting($k)) ?>" target="_blank" rel="noopener" aria-label="<?= $i ?>"><i class="bi bi-<?= $i ?>"></i></a>
          <?php endif; endforeach; ?>
          <?php if (setting('company_whatsapp')): ?><a href="https://wa.me/<?= e(setting('company_whatsapp')) ?>" target="_blank" rel="noopener" aria-label="whatsapp"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Platform</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= e(url('how-it-works.php')) ?>">How it works</a></li>
          <li><a href="<?= e(url('pricing.php')) ?>">Pricing</a></li>
          <li><a href="<?= e(url('create.php')) ?>">Create a funeral account</a></li>
          <li><a href="<?= e(url('obituaries.php')) ?>">Obituaries</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Services</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= e(url('livestream.php')) ?>">Live streaming</a></li>
          <li><a href="<?= e(url('photography.php')) ?>">Photography</a></li>
          <li><a href="<?= e(url('blog.php')) ?>">Blog</a></li>
          <li><a href="<?= e(url('about.php')) ?>">About us</a></li>
          <?php foreach ($footerPages as $fp): ?><li><a href="<?= e(url('page.php?s=' . $fp['slug'])) ?>"><?= e($fp['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6>Talk to us</h6>
        <ul class="list-unstyled small contact-list">
          <li><i class="bi bi-telephone"></i><a href="tel:<?= e(preg_replace('/\s+/', '', setting('company_phone'))) ?>"><?= e(setting('company_phone')) ?></a></li>
          <li><i class="bi bi-envelope"></i><a href="mailto:<?= e(setting('company_email')) ?>"><?= e(setting('company_email')) ?></a></li>
          <li><i class="bi bi-geo-alt"></i><?= e(setting('company_address')) ?></li>
          <li><i class="bi bi-globe2"></i><a href="<?= e(setting('company_website')) ?>" target="_blank" rel="noopener"><?= e(preg_replace('#^https?://(www\.)?#', '', setting('company_website'))) ?></a></li>
        </ul>
        <div class="mpesa-chip"><i class="bi bi-phone"></i> Pay securely with <strong>M-Pesa</strong></div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e(setting('company_name')) ?>. All rights reserved.</span>
      <span>Made with care in Kenya 🇰🇪</span>
    </div>
  </div>
</footer>
<?php if (setting('company_whatsapp')): ?>
<a class="wa-float" href="https://wa.me/<?= e(setting('company_whatsapp')) ?>?text=<?= rawurlencode('Hello ' . setting('company_name') . ', I need help with a tribute/funeral account.') ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><i class="bi bi-whatsapp"></i></a>
<?php endif; ?>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?= $extraFoot ?? '' ?>
</body>
</html>
