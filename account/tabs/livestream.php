<?php
if ($mode === 'post') {
    $u = trim(input('livestream_url'));
    if ($u !== '' && !filter_var($u, FILTER_VALIDATE_URL)) throw new RuntimeException('Enter a full link starting with https://');
    update('funerals', ['livestream_url' => $u ?: null, 'livestream_at' => input('livestream_at') ? str_replace('T', ' ', input('livestream_at')) . ':00' : null], 'id = ?', [$fid]);
    flash('success', 'Live stream details saved.');
    return;
}
?>
<div class="row g-4">
  <div class="col-xl-7">
    <div class="card-soft p-4">
      <h2 class="h5"><i class="bi bi-broadcast text-danger"></i> Live stream</h2>
      <p class="small-muted">The Ndanda Events crew will contact you to plan coverage. Once the stream link is ready (YouTube, Facebook or Vimeo), paste it here and it will be embedded on the tribute page.</p>
      <form method="post"><?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Stream link</label><input name="livestream_url" class="form-control" value="<?= e($f['livestream_url']) ?>" placeholder="https://www.youtube.com/live/…"></div>
        <div class="mb-3"><label class="form-label">Start time</label><input type="datetime-local" name="livestream_at" class="form-control" value="<?= e($f['livestream_at'] ? date('Y-m-d\TH:i', strtotime($f['livestream_at'])) : '') ?>"></div>
        <button class="btn btn-gold rounded-pill px-4">Save</button>
      </form>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card-soft p-4 bg-cream">
      <h3 class="h6">Coordinate with our crew</h3>
      <p class="small">Call or WhatsApp <strong><?= e(setting('company_phone')) ?></strong> with the venue, timings and expected guests.</p>
      <a class="btn btn-success btn-sm rounded-pill" target="_blank" rel="noopener" href="https://wa.me/<?= e(setting('company_whatsapp')) ?>?text=<?= rawurlencode('Hello, I need live streaming for the funeral of ' . $f['deceased_name'] . ' (' . tribute_url($f) . ').') ?>"><i class="bi bi-whatsapp"></i> WhatsApp the crew</a>
    </div>
  </div>
</div>
