<?php
/** Print-ready designs: memorial card, poster, programme booklet and QR plaque. Print or "Save as PDF" from the browser. */
require __DIR__ . '/includes/bootstrap.php';
$f = managed_funeral((int) input('id'));
$type = input('type');
$need = ['memorial' => 'memorial_print', 'poster' => 'memorial_print', 'programme' => 'programme', 'plaque' => 'qr_plaque'];
if (!isset($need[$type]) || !has_service($f, $need[$type])) {
    flash('warning', 'This print design is not part of your package yet.');
    redirect(url('account/funeral.php?id=' . $f['id'] . '&tab=qr'));
}
$events = rows('SELECT * FROM programme_events WHERE funeral_id = ? ORDER BY event_date, start_time, sort', [$f['id']]);
$oos = rows('SELECT * FROM order_of_service WHERE funeral_id = ? ORDER BY sort, id', [$f['id']]);
$style = input('style') === 'brass' ? 'brass' : 'granite';
$photo = $f['photo'] ? img($f['photo']) : '';
$qrLink = $type === 'programme' ? programme_url($f) : tribute_url($f);
$page = ['memorial' => 'A5', 'poster' => 'A4', 'programme' => 'A4', 'plaque' => 'A4 landscape'][$type];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(ucfirst($type) . ' – ' . $f['deceased_name']) ?></title>
<link href="<?= e(brand_font_href($f)) ?>" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{<?= brand_style($f) ?>}
*{box-sizing:border-box}
body{margin:0;background:#e9e7e3;font-family:Inter,Arial,sans-serif;color:#222;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.toolbar{position:sticky;top:0;background:#1b1f2a;color:#fff;padding:10px 16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;z-index:5;font-size:14px}
.toolbar button,.toolbar a{background:#b8913a;color:#fff;border:0;padding:8px 16px;border-radius:999px;cursor:pointer;text-decoration:none;font-weight:600}
.toolbar a.alt{background:transparent;border:1px solid #666}
.sheet{background:#fff;margin:24px auto;box-shadow:0 8px 30px rgba(0,0,0,.15);position:relative;overflow:hidden}
.a4{width:210mm;min-height:297mm}.a5{width:148mm;min-height:210mm}.land{width:297mm;height:210mm}
.serif{font-family:var(--brand-font)}
.portrait{border-radius:50%;object-fit:cover;border:5px solid #fff;box-shadow:0 6px 20px rgba(0,0,0,.2)}
.mono{border-radius:50%;display:grid;place-items:center;color:#fff;font-family:var(--brand-font);background:linear-gradient(135deg,var(--brand),var(--brand-accent))}
.heading{letter-spacing:.3em;text-transform:uppercase;color:var(--brand-accent);font-weight:600;font-size:11px}
.qr{background:#fff;padding:8px;display:inline-block;border-radius:8px}
.qr img,.qr canvas{display:block}
.rule{width:60px;height:3px;background:var(--brand-accent);margin:14px auto;border-radius:3px}
.cover{background:linear-gradient(170deg,var(--brand),color-mix(in srgb,var(--brand) 65%,#000));color:#fff;text-align:center;padding:28mm 18mm}
.ev{display:flex;gap:14px;padding:10px 0;border-bottom:1px dashed #ddd}
.ev .t{width:70px;font-weight:700;color:var(--brand)}
.plaque{display:flex;align-items:center;gap:16mm;padding:18mm;height:100%}
.plaque.granite{background:radial-gradient(circle at 30% 20%,#3a3a3a,#111 70%);color:#e9e2cf}
.plaque.brass{background:linear-gradient(135deg,#c9a24a,#f1d68f 40%,#b8913a 60%,#8a6a22);color:#2b1f05}
.plaque .frame{position:absolute;inset:8mm;border:2px solid currentColor;opacity:.4;border-radius:6mm}
@media print{
  body{background:#fff}.toolbar{display:none}.sheet{margin:0;box-shadow:none;page-break-after:always}
  @page{size:<?= $page ?>;margin:0}
}
</style></head><body>
<div class="toolbar">
  <strong><?= e(ucfirst($type)) ?> · <?= e($page) ?></strong>
  <button onclick="window.print()">Print / Save as PDF</button>
  <?php if ($type === 'plaque'): ?><a class="alt" href="?id=<?= (int) $f['id'] ?>&type=plaque&style=granite">Granite</a><a class="alt" href="?id=<?= (int) $f['id'] ?>&type=plaque&style=brass">Brass</a><?php endif; ?>
  <a class="alt" href="<?= e(url('account/funeral.php?id=' . $f['id'] . '&tab=qr')) ?>">Back</a>
  <span style="opacity:.7">Tip: enable “Background graphics” in the print dialog.</span>
</div>

<?php if ($type === 'memorial'): ?>
  <div class="sheet a5" style="text-align:center">
    <div class="cover" style="padding:18mm 12mm 26mm">
      <?php if ($f['brand_logo']): ?><img src="<?= e(img($f['brand_logo'])) ?>" style="max-height:28px;margin-bottom:10px" alt=""><?php endif; ?>
      <div class="heading"><?= e($f['heading']) ?></div>
    </div>
    <div style="margin-top:-22mm">
      <?php if ($photo): ?><img src="<?= e($photo) ?>" class="portrait" style="width:50mm;height:50mm" alt=""><?php else: ?><div class="mono" style="width:50mm;height:50mm;margin:auto;font-size:40px"><?= e(initials($f['deceased_name'])) ?></div><?php endif; ?>
    </div>
    <div style="padding:6mm 12mm">
      <h1 class="serif" style="color:var(--brand);margin:6px 0;font-size:26px"><?= e($f['deceased_name']) ?></h1>
      <div style="letter-spacing:.2em;color:#666"><?= e(fmt_date($f['birth_date'], 'j M Y')) ?> — <?= e(fmt_date($f['death_date'], 'j M Y')) ?></div>
      <div class="rule"></div>
      <?php if ($f['quote']): ?><p class="serif" style="font-style:italic;font-size:15px;color:#444">“<?= e($f['quote']) ?>”</p><?php endif; ?>
      <div class="qr" data-qr="<?= e($qrLink) ?>" data-size="110"></div>
      <div style="font-size:10px;color:#777;margin-top:4px">Scan to read the life story & leave a tribute</div>
    </div>
  </div>

<?php elseif ($type === 'poster'): ?>
  <div class="sheet a4" style="text-align:center">
    <div class="cover" style="padding:20mm 18mm 40mm">
      <?php if ($f['brand_logo']): ?><img src="<?= e(img($f['brand_logo'])) ?>" style="max-height:36px;margin-bottom:12px" alt=""><?php endif; ?>
      <div class="heading" style="font-size:14px"><?= e($f['heading']) ?></div>
    </div>
    <div style="margin-top:-32mm">
      <?php if ($photo): ?><img src="<?= e($photo) ?>" class="portrait" style="width:75mm;height:75mm" alt=""><?php else: ?><div class="mono" style="width:75mm;height:75mm;margin:auto;font-size:64px"><?= e(initials($f['deceased_name'])) ?></div><?php endif; ?>
    </div>
    <div style="padding:8mm 22mm">
      <h1 class="serif" style="color:var(--brand);font-size:40px;margin:8px 0"><?= e($f['deceased_name']) ?></h1>
      <div style="letter-spacing:.25em;color:#555;font-size:16px"><?= e(life_years($f)) ?></div>
      <div class="rule"></div>
      <p style="font-size:14px;line-height:1.6;color:#333"><?= nl2br(e(excerpt($f['obituary'], 520))) ?></p>
      <?php if ($events): $first = $events[0]; ?>
        <p style="font-size:14px;margin-top:10px"><strong><?= e($first['title']) ?></strong> · <?= e(fmt_date($first['event_date'], 'l j F Y')) ?>, <?= e(date('g:i A', strtotime($first['start_time']))) ?><br><?= e($first['venue']) ?></p>
      <?php endif; ?>
      <div style="display:flex;justify-content:center;gap:20mm;margin-top:8mm;align-items:center">
        <div><div class="qr" data-qr="<?= e(tribute_url($f)) ?>" data-size="130"></div><div style="font-size:11px;color:#666;margin-top:4px">Tribute page</div></div>
        <?php if (has_service($f, 'programme')): ?><div><div class="qr" data-qr="<?= e(programme_url($f)) ?>" data-size="130"></div><div style="font-size:11px;color:#666;margin-top:4px">Programme & directions</div></div><?php endif; ?>
      </div>
      <?php if ($f['brand_name']): ?><div style="margin-top:8mm;font-size:12px;color:#777">Presented by <?= e($f['brand_name']) ?></div><?php endif; ?>
    </div>
  </div>

<?php elseif ($type === 'programme'): ?>
  <div class="sheet a4">
    <div class="cover" style="min-height:297mm;display:flex;flex-direction:column;justify-content:center;align-items:center">
      <?php if ($f['brand_logo']): ?><img src="<?= e(img($f['brand_logo'])) ?>" style="max-height:40px;margin-bottom:20px" alt=""><?php endif; ?>
      <div class="heading" style="font-size:14px"><?= e($f['heading']) ?></div>
      <div style="margin:10mm 0">
        <?php if ($photo): ?><img src="<?= e($photo) ?>" class="portrait" style="width:90mm;height:90mm" alt=""><?php else: ?><div class="mono" style="width:90mm;height:90mm;font-size:72px"><?= e(initials($f['deceased_name'])) ?></div><?php endif; ?>
      </div>
      <h1 class="serif" style="font-size:44px;margin:0"><?= e($f['deceased_name']) ?></h1>
      <div style="letter-spacing:.3em;opacity:.85;font-size:16px;margin-top:6px"><?= e(fmt_date($f['birth_date'], 'j M Y')) ?> — <?= e(fmt_date($f['death_date'], 'j M Y')) ?></div>
      <?php if ($f['quote']): ?><p class="serif" style="font-style:italic;max-width:140mm;margin-top:10mm;font-size:17px;opacity:.9">“<?= e($f['quote']) ?>”</p><?php endif; ?>
    </div>
  </div>
  <div class="sheet a4" style="padding:18mm">
    <h2 class="serif" style="color:var(--brand);margin-top:0">Programme</h2>
    <?php foreach ($events as $ev): ?>
      <div class="ev"><div class="t"><?= e(date('g:i A', strtotime($ev['start_time']))) ?><div style="font-weight:400;font-size:11px;color:#777"><?= e(fmt_date($ev['event_date'], 'D j M')) ?></div></div>
        <div><strong><?= e($ev['title']) ?></strong><div style="font-size:13px;color:#555"><?= e($ev['venue']) ?><?= $ev['address'] ? ', ' . e($ev['address']) : '' ?></div><?php if ($ev['contact_phone']): ?><div style="font-size:12px;color:#777">Contact: <?= e($ev['contact_name']) ?> <?= e($ev['contact_phone']) ?></div><?php endif; ?></div></div>
    <?php endforeach; ?>
    <?php if ($oos): ?>
      <h2 class="serif" style="color:var(--brand);margin-top:12mm">Order of service</h2>
      <?php foreach ($oos as $o): ?><div class="ev"><div class="t"><?= e($o['time_label']) ?></div><div><strong><?= e($o['item']) ?></strong><?php if ($o['person']): ?><div style="font-size:13px;color:#555"><?= e($o['person']) ?></div><?php endif; ?></div></div><?php endforeach; ?>
    <?php endif; ?>
    <div style="display:flex;gap:10mm;align-items:center;margin-top:12mm;padding:8mm;background:#f7f5f0;border-radius:6mm">
      <div class="qr" data-qr="<?= e(programme_url($f)) ?>" data-size="120"></div>
      <div><strong>Scan for live directions</strong><div style="font-size:13px;color:#555">Open every venue in Google Maps, Apple Maps or Waze, see what's happening now, and read the full tribute.</div></div>
    </div>
  </div>
  <?php if ($f['bio']): ?>
  <div class="sheet a4" style="padding:18mm">
    <h2 class="serif" style="color:var(--brand);margin-top:0">Life story</h2>
    <div style="font-size:14px;line-height:1.75;columns:2;column-gap:10mm"><?= nl2br(e($f['bio'])) ?></div>
    <?php if ($f['family']): ?><h3 class="serif" style="color:var(--brand)">Family</h3><p style="font-size:13px"><?= nl2br(e($f['family'])) ?></p><?php endif; ?>
  </div>
  <?php endif; ?>

<?php else: ?>
  <div class="sheet land">
    <div class="plaque <?= $style ?>">
      <div class="frame"></div>
      <div style="flex:1;text-align:center;position:relative">
        <div style="letter-spacing:.35em;text-transform:uppercase;font-size:13px;opacity:.8"><?= e($f['heading']) ?></div>
        <h1 class="serif" style="font-size:46px;margin:10px 0"><?= e($f['deceased_name']) ?></h1>
        <div style="letter-spacing:.3em;font-size:18px"><?= e(fmt_date($f['birth_date'], 'j M Y')) ?> — <?= e(fmt_date($f['death_date'], 'j M Y')) ?></div>
        <div style="width:80px;height:2px;background:currentColor;opacity:.5;margin:16px auto"></div>
        <?php if ($f['quote']): ?><p class="serif" style="font-style:italic;font-size:18px;max-width:150mm;margin:auto">“<?= e($f['quote']) ?>”</p><?php endif; ?>
      </div>
      <div style="text-align:center;position:relative">
        <div class="qr" data-qr="<?= e(tribute_url($f)) ?>" data-size="200" style="padding:12px"></div>
        <div style="font-size:13px;margin-top:8px;letter-spacing:.1em">SCAN TO READ<br>THE LIFE STORY</div>
      </div>
    </div>
  </div>
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.querySelectorAll('[data-qr]').forEach(function (el) {
  var s = parseInt(el.dataset.size, 10);
  new QRCode(el, {text: el.dataset.qr, width: s, height: s, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H});
});
</script>
</body></html>
