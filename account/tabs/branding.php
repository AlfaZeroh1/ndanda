<?php
if ($mode === 'post') {
    $data = [
        'brand_primary' => valid_hex(input('brand_primary'), '#1f2937'),
        'brand_accent'  => valid_hex(input('brand_accent'), '#b8913a'),
        'brand_font'    => array_key_exists(input('brand_font'), brand_fonts()) ? input('brand_font') : 'Playfair Display',
        'brand_name'    => mb_substr(trim(input('brand_name')), 0, 120) ?: null,
        'brand_tagline' => mb_substr(trim(input('brand_tagline')), 0, 160) ?: null,
    ];
    $logo = upload_file($_FILES['brand_logo'] ?? null, 'f' . $fid);
    if ($logo) { delete_upload($f['brand_logo']); $data['brand_logo'] = $logo; }
    elseif (input('remove_logo')) { delete_upload($f['brand_logo']); $data['brand_logo'] = null; }
    update('funerals', $data, 'id = ?', [$fid]);
    flash('success', 'Branding saved. Your pages, programme and prints now use these colours.');
    return;
}
$presets = [['#1f2937', '#b8913a', 'Classic'], ['#1f3a2e', '#c9a227', 'Forest'], ['#0f2c4d', '#7fb3d5', 'Ocean'], ['#4a2c4f', '#d4a5a5', 'Plum'], ['#3d2a5c', '#e2b8ff', 'Lavender'], ['#7a2e1d', '#f0b429', 'Sunset'], ['#2b2b2b', '#ffffff', 'Monochrome'], ['#6b1d3a', '#e8c39e', 'Burgundy']];
$fonts = '';
foreach (array_keys(brand_fonts()) as $fn) $fonts .= '&family=' . str_replace(' ', '+', $fn);
?>
<link href="https://fonts.googleapis.com/css2?<?= ltrim($fonts, '&') ?>&display=swap" rel="stylesheet">
<form method="post" enctype="multipart/form-data" class="row g-4">
  <?= csrf_field() ?>
  <div class="col-xl-7">
    <div class="card-soft p-4 mb-4">
      <h2 class="h5">Colours</h2>
      <p class="small-muted">Pick a preset or your own colours. They apply to the tribute page, programme, form and prints.</p>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($presets as [$p, $a, $n]): ?><button type="button" class="btn btn-sm btn-light border rounded-pill preset" data-p="<?= $p ?>" data-a="<?= $a ?>"><span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:<?= $p ?>"></span><span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:<?= $a ?>"></span><?= $n ?></button><?php endforeach; ?>
      </div>
      <div class="row g-3">
        <div class="col-6 d-flex gap-2 align-items-center"><input type="color" name="brand_primary" id="cPrimary" class="form-control-color color-swatch" value="<?= e($f['brand_primary']) ?>"><div><div class="fw-semibold">Main colour</div><div class="small-muted">Header, buttons</div></div></div>
        <div class="col-6 d-flex gap-2 align-items-center"><input type="color" name="brand_accent" id="cAccent" class="form-control-color color-swatch" value="<?= e($f['brand_accent']) ?>"><div><div class="fw-semibold">Accent colour</div><div class="small-muted">Highlights, lines</div></div></div>
      </div>
    </div>
    <div class="card-soft p-4 mb-4">
      <h2 class="h5">Typography</h2>
      <select name="brand_font" id="cFont" class="form-select form-select-lg">
        <?php foreach (array_keys(brand_fonts()) as $fn): ?><option value="<?= e($fn) ?>" style="font-family:'<?= e($fn) ?>'" <?= $f['brand_font'] === $fn ? 'selected' : '' ?>><?= e($fn) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="card-soft p-4">
      <h2 class="h5">Your logo & name</h2>
      <p class="small-muted">Funeral homes, planners, churches or families can show their own identity.</p>
      <?php if ($f['brand_logo']): ?><div class="p-2 mb-2 rounded-3" style="background:<?= e($f['brand_primary']) ?>;display:inline-block"><img src="<?= e(img($f['brand_logo'])) ?>" style="max-height:48px" alt=""></div><div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rl"><label for="rl" class="form-check-label">Remove logo</label></div><?php endif; ?>
      <input type="file" name="brand_logo" accept="image/*" class="form-control mb-3">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Presented by</label><input name="brand_name" class="form-control" value="<?= e($f['brand_name']) ?>" placeholder="e.g. The Kamau Family"></div>
        <div class="col-md-6"><label class="form-label">Tagline</label><input name="brand_tagline" class="form-control" value="<?= e($f['brand_tagline']) ?>" placeholder="e.g. Forever loved"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="position-sticky" style="top:90px">
      <div class="small-muted mb-2">Live preview</div>
      <div class="mini-preview" id="preview" style="<?= e(brand_style($f)) ?>">
        <div class="mp-hero"><?php if ($f['brand_logo']): ?><img src="<?= e(img($f['brand_logo'])) ?>" alt=""><?php endif; ?></div>
        <div class="mp-photo"></div>
        <div class="text-center p-3">
          <div style="letter-spacing:.2em;font-size:.65rem;color:var(--brand-accent);font-weight:600;text-transform:uppercase"><?= e($f['heading']) ?></div>
          <div style="font-family:var(--brand-font);font-size:1.6rem;color:var(--brand)"><?= e($f['deceased_name']) ?></div>
          <div class="small text-muted mb-3"><?= e(life_years($f)) ?></div>
          <span class="btn btn-sm btn-brand rounded-pill">Light a candle</span>
          <span class="btn btn-sm btn-outline-brand rounded-pill">Programme</span>
        </div>
      </div>
      <button class="btn btn-gold btn-lg w-100 rounded-pill mt-3"><i class="bi bi-check2"></i> Save branding</button>
    </div>
  </div>
</form>
<?php
$extraFoot = <<<'HTML'
<script>
(function(){
  const pv = document.getElementById('preview'), p = document.getElementById('cPrimary'), a = document.getElementById('cAccent'), f = document.getElementById('cFont');
  function apply(){ pv.style.setProperty('--brand', p.value); pv.style.setProperty('--brand-accent', a.value); pv.style.setProperty('--brand-font', "'" + f.value + "', serif"); }
  [p, a, f].forEach(el => el.addEventListener('input', apply));
  document.querySelectorAll('.preset').forEach(b => b.addEventListener('click', () => { p.value = b.dataset.p; a.value = b.dataset.a; apply(); }));
})();
</script>
HTML;
