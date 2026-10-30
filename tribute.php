<?php
require __DIR__ . '/includes/bootstrap.php';
$f = row('SELECT * FROM funerals WHERE slug = ?', [input('s')]);
$owner = $f && can_manage($f);
if (!$f || ($f['status'] !== 'active' && !$owner)) {
    http_response_code(404);
    $pageTitle = 'Tribute not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container section empty"><i class="bi bi-flower1"></i><h1 class="h3">This tribute is not available</h1><p>It may be awaiting activation. <a href="' . e(url('obituaries.php')) . '">Browse tributes</a></p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
if (empty($_SESSION['viewed'][$f['id']]) && !$owner) {
    $_SESSION['viewed'][$f['id']] = 1;
    q('UPDATE funerals SET views = views + 1 WHERE id = ?', [$f['id']]);
}

// Tribute form submission on the page itself
if (is_post() && input('action') === 'tribute' && has_service($f, 'tribute_page')) {
    require __DIR__ . '/includes/tribute_submit.php';
    handle_tribute_submission($f, tribute_url($f) . '#tributes');
}

$hasTP = has_service($f, 'tribute_page');
$hasProg = has_service($f, 'programme');
$hasLive = has_service($f, 'livestream') && $f['livestream_url'];
$tributes = $hasTP ? rows("SELECT * FROM tributes WHERE funeral_id = ? AND status = 'approved' ORDER BY created_at DESC", [$f['id']]) : [];
$gallery = $hasTP ? rows('SELECT * FROM gallery WHERE funeral_id = ? ORDER BY sort, id', [$f['id']]) : [];
$events = $hasProg ? rows('SELECT * FROM programme_events WHERE funeral_id = ? ORDER BY event_date, start_time, sort', [$f['id']]) : [];
$pledged = $f['accept_pledges'] ? (float) val('SELECT COALESCE(SUM(amount),0) FROM contributions WHERE funeral_id = ?', [$f['id']]) : 0;

function youtube_embed(string $u): ?string
{
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|live/|embed/))([A-Za-z0-9_-]{6,})~', $u, $m)) return 'https://www.youtube.com/embed/' . $m[1];
    if (preg_match('~facebook\.com/.+/videos/~', $u)) return 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($u);
    if (preg_match('~vimeo\.com/(\d+)~', $u, $m)) return 'https://player.vimeo.com/video/' . $m[1];
    return null;
}

$pageTitle = $f['heading'] . ' ' . $f['deceased_name'];
$metaDesc = excerpt($f['obituary'] ?: $f['bio'], 160);
$ogImage = $f['photo'] ? img($f['photo']) : null;
$hideNav = true;
$hideFooter = true;
$hideTestBanner = true;
$deferFlash = true;
$bodyClass = 'memorial';
$bodyAttr = 'style="' . e(brand_style($f)) . '"';
$extraHead = '<link href="' . e(brand_font_href($f)) . '" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css">';
require __DIR__ . '/includes/header.php';
$share = tribute_url($f);
?>
<?php if ($f['status'] !== 'active'): ?><div class="preview-bar"><i class="bi bi-eye"></i> Preview — this page is not public yet (status: <?= e(str_replace('_', ' ', $f['status'])) ?>). <a href="<?= e(url('account/funeral.php?id=' . $f['id'] . '&tab=billing')) ?>">Complete payment</a></div><?php endif; ?>
<?php if ($owner): ?><div class="preview-bar" style="background:#e8f0fe;color:#123"><a href="<?= e(url('account/funeral.php?id=' . $f['id'])) ?>"><i class="bi bi-pencil-square"></i> Edit this tribute</a></div><?php endif; ?>

<header class="m-hero" <?= $f['cover'] ? 'style="background-image:url(' . e(img($f['cover'])) . ')"' : '' ?>>
  <div class="container m-brandbar">
    <div class="d-flex align-items-center gap-2">
      <?php if ($f['brand_logo']): ?><img src="<?= e(img($f['brand_logo'])) ?>" alt="logo"><?php endif; ?>
      <?php if ($f['brand_name']): ?><div class="small"><span class="opacity-75">Presented by</span><br><strong><?= e($f['brand_name']) ?></strong></div><?php endif; ?>
    </div>
    <button class="btn btn-light btn-sm rounded-pill" data-share="<?= e($share) ?>" data-title="<?= e($pageTitle) ?>"><i class="bi bi-share"></i> Share</button>
  </div>
</header>

<section class="m-head container">
  <?= portrait($f, 'm-portrait', 200) ?>
  <div class="m-heading"><?= e($f['heading']) ?></div>
  <h1 class="m-name"><?= e($f['deceased_name']) ?></h1>
  <?php if ($f['nickname']): ?><div class="text-muted mb-1">“<?= e($f['nickname']) ?>”</div><?php endif; ?>
  <div class="m-years"><?= e(life_years($f)) ?></div>
  <?php if ($f['quote']): ?><p class="m-quote">“<?= e($f['quote']) ?>”</p><?php endif; ?>
  <div class="m-actions d-flex flex-wrap justify-content-center gap-2 mt-4">
    <button class="btn btn-brand candle-btn" id="candleBtn" data-id="<?= (int) $f['id'] ?>"><span class="candle-flame"><i class="bi bi-fire"></i></span> Light a candle · <span id="candleCount"><?= (int) $f['candles'] ?></span></button>
    <?php if ($hasTP): ?><a href="#tributes" class="btn btn-outline-brand"><i class="bi bi-chat-heart"></i> Leave a tribute</a><?php endif; ?>
    <?php if ($hasProg && $events): ?><a href="<?= e(programme_url($f)) ?>" class="btn btn-outline-brand"><i class="bi bi-signpost-split"></i> Programme & directions</a><?php endif; ?>
    <?php if ($hasLive): ?><a href="#live" class="btn btn-danger"><i class="bi bi-broadcast"></i> Watch live</a><?php endif; ?>
  </div>
</section>

<nav class="m-tabs mt-5 text-center">
  <?php if ($f['obituary']): ?><a href="#obituary">Obituary</a><?php endif; ?>
  <?php if ($f['bio']): ?><a href="#life">Life story</a><?php endif; ?>
  <?php if ($gallery): ?><a href="#gallery">Gallery</a><?php endif; ?>
  <?php if ($events): ?><a href="#programme">Programme</a><?php endif; ?>
  <?php if ($hasLive): ?><a href="#live">Live stream</a><?php endif; ?>
  <?php if ($hasTP): ?><a href="#tributes">Tributes (<?= count($tributes) ?>)</a><?php endif; ?>
  <?php if ($f['contribution_details'] || $f['accept_pledges']): ?><a href="#contribute">Contribute</a><?php endif; ?>
</nav>

<div class="container"><?= flashes() ?></div>

<?php if ($f['obituary'] || $f['birth_date'] || $f['resting_place']): ?>
<section class="m-section" id="obituary">
  <div class="container" style="max-width:980px">
    <div class="row g-4">
      <div class="col-lg-8"><h2>Obituary</h2><div class="m-card prose"><?= nl2br(e($f['obituary'] ?: 'Details will be shared soon.')) ?></div></div>
      <div class="col-lg-4">
        <div class="m-card mt-lg-5">
          <dl class="m-facts mb-0">
            <?php if ($f['birth_date']): ?><dt>Born</dt><dd><?= e(fmt_date($f['birth_date'], 'j F Y')) ?><?= $f['birth_place'] ? '<br><span class="text-muted small">' . e($f['birth_place']) . '</span>' : '' ?></dd><?php endif; ?>
            <?php if ($f['death_date']): ?><dt>Passed on</dt><dd><?= e(fmt_date($f['death_date'], 'j F Y')) ?></dd><?php endif; ?>
            <?php if ($f['resting_place']): ?><dt>Resting place</dt><dd><?= e($f['resting_place']) ?></dd><?php endif; ?>
            <?php if ($f['family']): ?><dt>Family</dt><dd class="small"><?= nl2br(e($f['family'])) ?></dd><?php endif; ?>
          </dl>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($f['bio']): ?>
<section class="m-section bg-white" id="life">
  <div class="container" style="max-width:820px"><h2>Life story</h2><div class="prose"><?= nl2br(e($f['bio'])) ?></div></div>
</section>
<?php endif; ?>

<?php if ($gallery): ?>
<section class="m-section" id="gallery">
  <div class="container"><h2>Gallery</h2>
    <div class="gallery-grid"><?php foreach ($gallery as $g): ?><a href="<?= e(img($g['file'])) ?>" class="glightbox" data-gallery="g" data-description="<?= e($g['caption']) ?>"><img src="<?= e(img($g['file'])) ?>" alt="<?= e($g['caption']) ?>" loading="lazy"></a><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($events): ?>
<section class="m-section bg-white" id="programme">
  <div class="container" style="max-width:820px">
    <h2>Funeral programme</h2>
    <?php foreach ($events as $ev): ?>
      <div class="d-flex gap-3 py-3 border-bottom">
        <div class="text-center" style="min-width:70px"><div class="fw-bold" style="color:var(--brand)"><?= e(date('H:i', strtotime($ev['start_time']))) ?></div><div class="small text-muted"><?= e(fmt_date($ev['event_date'], 'D j M')) ?></div></div>
        <div class="flex-grow-1"><div class="fw-semibold"><?= e($ev['title']) ?></div><div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= e($ev['venue']) ?></div></div>
        <?php if ($ev['lat']): ?><a class="btn btn-sm btn-outline-brand rounded-pill align-self-center" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=<?= e($ev['lat'] . ',' . $ev['lng']) ?>"><i class="bi bi-cursor"></i></a><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <a href="<?= e(programme_url($f)) ?>" class="btn btn-brand rounded-pill mt-4"><i class="bi bi-map"></i> Open interactive programme & map</a>
  </div>
</section>
<?php endif; ?>

<?php if ($hasLive): $embed = youtube_embed($f['livestream_url']); ?>
<section class="m-section" id="live">
  <div class="container" style="max-width:900px">
    <h2>Live stream <?php if ($f['livestream_at'] && abs(time() - strtotime($f['livestream_at'])) < 4 * 3600): ?><span class="live-pill ms-2 align-middle">LIVE</span><?php endif; ?></h2>
    <?php if ($f['livestream_at']): ?><p class="text-muted"><i class="bi bi-clock"></i> <?= e(fmt_date($f['livestream_at'], 'l j F Y, g:i A')) ?> (EAT)</p><?php endif; ?>
    <?php if ($embed): ?><div class="ratio ratio-16x9"><iframe src="<?= e($embed) ?>" allow="accelerometer; autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>
    <?php else: ?><a class="btn btn-danger btn-lg rounded-pill" href="<?= e($f['livestream_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-broadcast"></i> Watch the live stream</a><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($hasTP): ?>
<section class="m-section bg-white" id="tributes">
  <div class="container" style="max-width:980px">
    <h2>Tributes & condolences</h2>
    <div class="row g-4">
      <div class="col-lg-7">
        <?php if (!$tributes): ?><div class="empty"><i class="bi bi-chat-heart"></i>Be the first to share a memory.</div><?php endif; ?>
        <?php foreach ($tributes as $t): ?>
        <div class="tribute-msg">
          <div class="d-flex justify-content-between flex-wrap gap-1"><span class="tm-author"><?= e($t['author_name']) ?></span><span class="tm-meta"><?= e(time_ago($t['created_at'])) ?></span></div>
          <div class="tm-meta mb-2"><?= e(implode(' · ', array_filter([$t['relationship'], $t['location']]))) ?></div>
          <div><?= nl2br(e($t['message'])) ?></div>
          <?php if ($t['photo']): ?><a href="<?= e(img($t['photo'])) ?>" class="glightbox"><img src="<?= e(img($t['photo'])) ?>" class="tm-photo" alt="" loading="lazy"></a><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="col-lg-5">
        <div class="m-card position-sticky" style="top:70px">
          <h3 class="h5 m-font">Share a memory</h3>
          <?php $formAction = tribute_url($f) . '#tributes'; require __DIR__ . '/includes/tribute_form.php'; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($f['contribution_details'] || $f['accept_pledges']): ?>
<section class="m-section" id="contribute">
  <div class="container" style="max-width:820px">
    <h2>Support the family</h2>
    <div class="row g-4">
      <?php if ($f['contribution_details']): ?><div class="col-md-6"><div class="m-card h-100"><h3 class="h6 text-uppercase small text-muted">Contribution details</h3><div class="fs-6"><?= nl2br(e($f['contribution_details'])) ?></div></div></div><?php endif; ?>
      <?php if ($f['accept_pledges']): ?>
      <div class="col-md-6"><div class="m-card h-100">
        <h3 class="h6 text-uppercase small text-muted">Make a pledge</h3>
        <form method="post" action="<?= e(url('api/pledge.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="funeral_id" value="<?= (int) $f['id'] ?>">
          <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
          <input name="name" class="form-control mb-2" placeholder="Your name" required>
          <input name="phone" class="form-control mb-2" placeholder="Phone (optional)">
          <input type="number" name="amount" min="1" class="form-control mb-2" placeholder="Amount (KES)" required>
          <button class="btn btn-brand w-100 rounded-pill">Pledge</button>
        </form>
      </div></div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="m-section bg-white text-center">
  <div class="container">
    <h2 class="d-inline-block">Share this tribute</h2>
    <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
      <a class="btn btn-success rounded-pill" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($pageTitle . ' ' . $share) ?>"><i class="bi bi-whatsapp"></i> WhatsApp</a>
      <a class="btn btn-primary rounded-pill" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($share) ?>"><i class="bi bi-facebook"></i> Facebook</a>
      <a class="btn btn-dark rounded-pill" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url=<?= rawurlencode($share) ?>&text=<?= rawurlencode($pageTitle) ?>"><i class="bi bi-twitter-x"></i> X</a>
      <button class="btn btn-outline-secondary rounded-pill" data-copy="<?= e($share) ?>"><i class="bi bi-link-45deg"></i> Copy link</button>
    </div>
  </div>
</section>

<footer class="m-footer">
  <?php if ($f['brand_name']): ?><div class="mb-1"><?= e($f['brand_name']) ?><?= $f['brand_tagline'] ? ' · ' . e($f['brand_tagline']) : '' ?></div><?php endif; ?>
  <div class="small opacity-75">Tribute powered by <a href="<?= e(url()) ?>"><?= e(setting('site_name')) ?></a> · <?= e(setting('company_name')) ?></div>
</footer>
<?php
$extraFoot = '<script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script>
<script>
GLightbox({selector: ".glightbox"});
document.getElementById("candleBtn").addEventListener("click", async function(){
  const r = await postJSON(APP.base + "/api/candle.php", {id: this.dataset.id});
  if (r.ok) { document.getElementById("candleCount").textContent = r.candles; toast(r.already ? "You already lit a candle. Thank you." : "Your candle has been lit 🕯️"); }
});
</script>';
require __DIR__ . '/includes/footer.php';
