<?php
if ($mode === 'post') return;
$stats = [
    ['Page views', (int) $f['views'], 'bi-eye'],
    ['Tributes', (int) val('SELECT COUNT(*) FROM tributes WHERE funeral_id = ?', [$fid]), 'bi-chat-heart'],
    ['Candles lit', (int) $f['candles'], 'bi-fire'],
    ['Pledged / received', money(val('SELECT COALESCE(SUM(amount),0) FROM contributions WHERE funeral_id = ?', [$fid])) . ' / ' . number_format((float) val("SELECT COALESCE(SUM(amount),0) FROM contributions WHERE funeral_id = ? AND status = 'received'", [$fid])), 'bi-cash-coin'],
];
$checklist = [
    ['Add a portrait photo', (bool) $f['photo'], tab_url('details')],
    ['Write the obituary', (bool) $f['obituary'], tab_url('details')],
    ['Write the life story / eulogy', (bool) $f['bio'], tab_url('details')],
    ['Set your colours & logo', $f['brand_primary'] !== '#1f2937' || $f['brand_logo'], tab_url('branding')],
];
if (has_service($f, 'tribute_page')) $checklist[] = ['Upload photos to the gallery', (bool) val('SELECT COUNT(*) FROM gallery WHERE funeral_id = ?', [$fid]), tab_url('gallery')];
if (has_service($f, 'programme')) $checklist[] = ['Add programme events with GPS pins', (bool) val('SELECT COUNT(*) FROM programme_events WHERE funeral_id = ? AND lat IS NOT NULL', [$fid]), tab_url('programme')];
if (has_service($f, 'livestream')) $checklist[] = ['Add the live stream link', (bool) $f['livestream_url'], tab_url('livestream')];
$done = count(array_filter($checklist, fn($c) => $c[1]));
$pct = (int) round($done / max(1, count($checklist)) * 100);
$owned = rows('SELECT s.* FROM services s JOIN funeral_services fs ON fs.service_id = s.id WHERE fs.funeral_id = ? ORDER BY s.sort', [$fid]);
?>
<div class="row g-3 mb-4">
  <?php foreach ($stats as [$label, $num, $icon]): ?>
  <div class="col-6 col-xl-3"><div class="stat"><div class="d-flex justify-content-between"><div class="stat-label"><?= e($label) ?></div><i class="bi <?= $icon ?> text-gold"></i></div><div class="stat-num"><?= is_int($num) ? number_format($num) : e($num) ?></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-4">
  <div class="col-xl-7">
    <div class="card-soft p-4 mb-4">
      <h2 class="h5 mb-3"><i class="bi bi-share text-gold"></i> Share with family & friends</h2>
      <?php
      $links = [['Tribute page', tribute_url($f), 'Share on WhatsApp groups, social media and SMS.']];
      if (has_service($f, 'tribute_page')) $links[] = ['Tribute form link', form_url($f), 'Send to relatives & friends so they can add their own tributes and photos.'];
      if (has_service($f, 'programme')) $links[] = ['Programme & directions', programme_url($f), 'Venues, times and one-tap navigation for mourners.'];
      foreach ($links as $i => [$label, $link, $hint]): ?>
        <div class="mb-3">
          <label class="form-label mb-1"><?= e($label) ?></label>
          <div class="copy-field">
            <input class="form-control" id="lnk<?= $i ?>" value="<?= e($link) ?>" readonly>
            <button class="btn btn-outline-secondary" data-copy-target="#lnk<?= $i ?>" title="Copy"><i class="bi bi-clipboard"></i></button>
            <a class="btn btn-success" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($label . ' – ' . $f['deceased_name'] . ': ' . $link) ?>" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
          </div>
          <div class="form-text"><?= e($hint) ?></div>
        </div>
      <?php endforeach; ?>
      <?php if ($f['status'] !== 'active'): ?><div class="alert alert-warning small mb-0">Links go live once payment is complete. Until then only you can preview the page.</div><?php endif; ?>
    </div>
    <div class="card-soft p-4">
      <h2 class="h5 mb-3"><i class="bi bi-bag-check text-gold"></i> Your package</h2>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($owned as $s): ?><span class="badge rounded-pill text-bg-light border p-2"><i class="bi <?= e($s['icon']) ?> text-gold"></i> <?= e($s['name']) ?></span><?php endforeach; ?>
      </div>
      <a href="<?= e(tab_url('billing')) ?>" class="btn btn-sm btn-outline-ink rounded-pill mt-3"><i class="bi bi-plus"></i> Add services</a>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card-soft p-4">
      <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Setup checklist</h2><span class="fw-semibold"><?= $pct ?>%</span></div>
      <div class="progress progress-thin mb-3"><div class="progress-bar" style="width:<?= $pct ?>%;background:var(--gold)"></div></div>
      <ul class="list-unstyled mb-0">
        <?php foreach ($checklist as [$label, $ok, $link]): ?>
          <li class="d-flex align-items-center gap-2 py-2 border-bottom"><i class="bi <?= $ok ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i><a href="<?= e($link) ?>" class="text-decoration-none <?= $ok ? 'text-muted text-decoration-line-through' : 'text-reset' ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
