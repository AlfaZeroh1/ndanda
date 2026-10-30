<?php
require __DIR__ . '/includes/bootstrap.php';
$active = 'home';
$featured = public_funerals('', [], 6);
$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort');
$posts = rows("SELECT * FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 3");
$stats = [
    'tributes' => (int) val("SELECT COUNT(*) FROM funerals WHERE status = 'active'"),
    'messages' => (int) val("SELECT COUNT(*) FROM tributes WHERE status = 'approved'"),
    'candles'  => (int) val('SELECT COALESCE(SUM(candles),0) FROM funerals'),
];
$heroSample = $featured[0] ?? null;
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <div class="eyebrow mb-3">Digital funerals · Made in Kenya</div>
        <h1 class="mb-4"><?= e(setting('hero_title')) ?></h1>
        <p class="lead mb-4"><?= e(setting('hero_subtitle')) ?></p>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <a href="<?= e(url('create.php')) ?>" class="btn btn-gold btn-lg rounded-pill px-4"><i class="bi bi-flower1 me-2"></i>Create a tribute</a>
          <a href="<?= e(url('obituaries.php')) ?>" class="btn btn-outline-light btn-lg rounded-pill px-4">View tributes</a>
        </div>
        <div class="hero-badges">
          <span><i class="bi bi-geo-alt"></i> GPS directions to every venue</span>
          <span><i class="bi bi-qr-code"></i> QR codes & plaques</span>
          <span><i class="bi bi-phone"></i> Pay with M-Pesa</span>
          <span><i class="bi bi-palette"></i> Your own branding</span>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block position-relative">
        <?php if ($heroSample): ?>
        <div class="hero-card">
          <div class="hc-cover" style="background:linear-gradient(135deg,<?= e($heroSample['brand_primary']) ?>,<?= e($heroSample['brand_accent']) ?>)"></div>
          <?= portrait($heroSample, 'hc-photo', 96) ?>
          <div class="p-4 text-center">
            <small class="text-uppercase text-muted" style="letter-spacing:.16em;font-size:.68rem"><?= e($heroSample['heading']) ?></small>
            <h3 class="h4 mt-1 mb-0"><?= e($heroSample['deceased_name']) ?></h3>
            <div class="text-muted small mb-3"><?= e(life_years($heroSample)) ?></div>
            <p class="fst-italic small text-secondary"><?= e(excerpt($heroSample['quote'], 90)) ?></p>
            <a href="<?= e(tribute_url($heroSample)) ?>" class="btn btn-ink btn-sm rounded-pill px-3">Visit tribute</a>
          </div>
        </div>
        <div class="hero-float" style="left:-10px;top:40px"><i class="bi bi-signpost-split"></i> Next: Funeral service · 10:00</div>
        <div class="hero-float" style="left:20px;bottom:30px"><i class="bi bi-chat-heart"></i> <?= number_format($stats['messages']) ?> tributes shared</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-4"><div class="display-6 display-font"><?= number_format($stats['tributes']) ?></div><div class="small-muted">Lives honoured</div></div>
      <div class="col-4"><div class="display-6 display-font"><?= number_format($stats['messages']) ?></div><div class="small-muted">Tributes written</div></div>
      <div class="col-4"><div class="display-6 display-font"><?= number_format($stats['candles']) ?></div><div class="small-muted">Candles lit</div></div>
    </div>
  </div>
</section>

<section class="section bg-cream">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3">
      <div><div class="eyebrow">Recent tributes</div><h2 class="mb-0">Lives we are celebrating</h2></div>
      <a href="<?= e(url('obituaries.php')) ?>" class="btn btn-outline-ink rounded-pill">All obituaries <i class="bi bi-arrow-right"></i></a>
    </div>
    <?php if ($featured): ?>
    <div class="row g-4">
      <?php foreach ($featured as $f): ?><div class="col-sm-6 col-lg-4"><?= tribute_card($f) ?></div><?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty"><i class="bi bi-flower1"></i>Tributes created on the platform will appear here.</div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="text-center mb-5"><div class="eyebrow">Everything in one place</div><h2>A complete digital funeral</h2><p class="lead-muted mx-auto" style="max-width:640px">Pick only what you need. Every item is a simple add-on, and the preparations workspace is free.</p></div>
    <div class="row g-4">
      <?php foreach ($services as $s): ?>
      <div class="col-md-6 col-lg-3">
        <div class="card-soft p-4 h-100 hover-lift">
          <div class="feature-icon mb-3"><i class="bi <?= e($s['icon']) ?>"></i></div>
          <h3 class="h5"><?= e($s['name']) ?></h3>
          <p class="small text-secondary mb-3"><?= e(excerpt($s['description'], 130)) ?></p>
          <div class="fw-bold"><?= $s['price'] > 0 ? e(money($s['price'])) : '<span class="text-success">Free</span>' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section bg-ink">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <div class="eyebrow">How it works</div>
        <h2 class="text-white">From paper to a seamless digital farewell</h2>
        <p class="text-white-50">The funeral planner picks a package, pays with M-Pesa and gets a funeral account instantly. Share one link and everyone has the life story, the programme and directions in their pocket.</p>
        <a href="<?= e(url('how-it-works.php')) ?>" class="btn btn-gold rounded-pill px-4 mt-2">See the full journey</a>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <?php foreach ([
              ['bi-ui-checks', 'Choose your package', 'Tick the services you need and see the total instantly.'],
              ['bi-phone', 'Pay with M-Pesa', 'Approve the STK push on your phone. Done in seconds.'],
              ['bi-brush', 'Create & brand', 'Add the life story, photos, programme and venues in your own colours.'],
              ['bi-share', 'Share one link', 'Family adds tributes via a form link; mourners get live directions.'],
          ] as $i => [$icon, $t, $d]): ?>
          <div class="col-sm-6">
            <div class="p-4 rounded-4 h-100" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)">
              <div class="d-flex align-items-center gap-3 mb-2"><span class="step-num"><?= $i + 1 ?></span><i class="bi <?= $icon ?> fs-4 text-gold"></i></div>
              <h3 class="h6 text-white mb-1"><?= $t ?></h3>
              <p class="small text-white-50 mb-0"><?= $d ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6 order-lg-2">
        <div class="eyebrow">Interactive programme</div>
        <h2>Nobody gets lost on the way to say goodbye</h2>
        <p class="lead-muted">Pin the exact GPS location of the mortuary, church, home and burial site. Mourners tap once to open Google Maps, Apple Maps or Waze — and the programme shows what is happening now and what is next.</p>
        <ul class="list-unstyled mt-4">
          <li class="mb-2"><i class="bi bi-check-circle-fill text-gold me-2"></i>Live “happening now / up next” timeline</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-gold me-2"></i>One-tap navigation and call the coordinator</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-gold me-2"></i>Add each event to your phone calendar</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-gold me-2"></i>Order of service and a print-ready booklet</li>
        </ul>
      </div>
      <div class="col-lg-6">
        <div class="card-soft p-4" style="--brand:#1f3a2e;--brand-accent:#c9a227">
          <div class="timeline">
            <?php foreach ([['07:30', 'Cortege departs', 'Kiambu Mortuary', 'is-done'], ['10:00', 'Funeral service', 'PCEA Gatundu Parish', 'is-now'], ['14:00', 'Burial & reception', 'Family home, Gatundu', 'is-next']] as [$t, $title, $v, $cls]): ?>
            <div class="tl-item <?= $cls ?>"><span class="tl-dot"></span>
              <div class="tl-card py-3">
                <div class="d-flex justify-content-between"><span class="tl-time"><?= $t ?></span><span class="tl-status now">Happening now</span><span class="tl-status next">Up next</span><span class="tl-status done">Done</span></div>
                <div class="fw-semibold"><?= $title ?></div>
                <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= $v ?></div>
                <div class="nav-btns mt-2 d-flex gap-2"><span class="btn btn-sm btn-outline-secondary"><i class="bi bi-google"></i> Maps</span><span class="btn btn-sm btn-outline-secondary"><i class="bi bi-apple"></i> Apple</span><span class="btn btn-sm btn-outline-secondary">Waze</span></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section bg-cream">
  <div class="container">
    <div class="row g-4">
      <?php foreach ([
          ['bi-palette2', 'Your brand, your moment', 'Funeral planners, churches and funeral homes can brand every page with their own logo, colours and fonts.'],
          ['bi-folder2-open', 'Free preparations workspace', 'Keep documents, notes, committee tasks and the full budget in one private place.'],
          ['bi-cash-coin', 'Contributions & pledges', 'Share contribution details and let well-wishers pledge online. Track what has been received.'],
          ['bi-broadcast', 'Live stream & photography', 'Ndanda Events covers the service and brings it to loved ones anywhere in the world.'],
      ] as [$icon, $t, $d]): ?>
      <div class="col-md-6 col-lg-3"><div class="p-2"><i class="bi <?= $icon ?> fs-2 text-gold"></i><h3 class="h5 mt-3"><?= $t ?></h3><p class="small text-secondary mb-0"><?= $d ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($posts): ?>
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4"><div><div class="eyebrow">From the blog</div><h2 class="mb-0">Guidance for families</h2></div><a href="<?= e(url('blog.php')) ?>" class="btn btn-outline-ink rounded-pill d-none d-sm-inline-block">All articles</a></div>
    <div class="row g-4">
      <?php foreach ($posts as $p): ?>
      <div class="col-md-4">
        <a href="<?= e(post_url($p)) ?>" class="card-soft d-block h-100 text-decoration-none text-reset hover-lift overflow-hidden">
          <?php if ($p['cover']): ?><img src="<?= e(img($p['cover'])) ?>" class="w-100 object-cover" style="height:180px" alt=""><?php else: ?><div style="height:8px;background:linear-gradient(90deg,var(--ink),var(--gold))"></div><?php endif; ?>
          <div class="p-4"><div class="small-muted mb-1"><?= e(fmt_date($p['published_at'])) ?></div><h3 class="h5"><?= e($p['title']) ?></h3><p class="small text-secondary mb-0"><?= e($p['excerpt']) ?></p></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section-sm">
  <div class="container">
    <div class="rounded-5 p-5 text-center text-white" style="background:linear-gradient(135deg,#1b1f2a,#3a3326)">
      <h2 class="text-white">Honour your loved one today</h2>
      <p class="text-white-50 mx-auto" style="max-width:560px">Set up a funeral account in minutes. Our team is available on WhatsApp and phone to help you every step of the way.</p>
      <div class="d-flex justify-content-center flex-wrap gap-2">
        <a href="<?= e(url('create.php')) ?>" class="btn btn-gold btn-lg rounded-pill px-4">Create a funeral account</a>
        <a href="tel:<?= e(preg_replace('/\s+/', '', setting('company_phone'))) ?>" class="btn btn-outline-light btn-lg rounded-pill px-4"><i class="bi bi-telephone"></i> <?= e(setting('company_phone')) ?></a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
