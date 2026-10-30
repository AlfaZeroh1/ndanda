<?php
if ($mode === 'post') return;
$hasQR = has_service($f, 'qr_code');
$qrs = [['qrTribute', 'Tribute page', tribute_url($f), 'tribute']];
if (has_service($f, 'programme')) $qrs[] = ['qrProg', 'Programme & directions', programme_url($f), 'programme'];
if (has_service($f, 'tribute_page')) $qrs[] = ['qrForm', 'Tribute form', form_url($f), 'tribute-form'];
$prints = [
    ['memorial', 'Memorial card (A5)', 'Portrait, dates, verse and QR – for the service and keepsakes.', 'memorial_print', 'bi-postcard-heart'],
    ['poster', 'Memorial poster (A4)', 'Announcement poster for churches, offices and notice boards.', 'memorial_print', 'bi-file-earmark-image'],
    ['programme', 'Programme booklet (A4)', 'Full printable programme with venues, times and order of service.', 'programme', 'bi-journal-text'],
    ['plaque', 'QR memorial plaque', 'Engraving/print-ready plaque design that links to the life story.', 'qr_plaque', 'bi-award'],
];
$unlock = function (string $code) use ($f) {
    $s = service_by_code($code);
    return '<form method="post" action="' . e(tab_url('billing')) . '">' . csrf_field() . '<input type="hidden" name="act" value="buy"><input type="hidden" name="services[]" value="' . (int) $s['id'] . '"><button class="btn btn-sm btn-gold rounded-pill"><i class="bi bi-unlock"></i> Unlock · ' . e(money($s['price'])) . '</button></form>';
};
?>
<h2 class="h4 mb-3">QR codes</h2>
<?php if (!$hasQR): ?>
  <div class="locked-box mb-4 py-4"><i class="bi bi-qr-code"></i><p class="mt-2">Get high-resolution QR codes for banners, invitations and newspapers.</p><?= $unlock('qr_code') ?></div>
<?php else: ?>
<div class="row g-4 mb-5">
  <?php foreach ($qrs as [$id, $label, $link, $file]): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card-soft p-4 text-center h-100">
      <div class="fw-semibold mb-2"><?= e($label) ?></div>
      <div class="qr-box mb-3"><div id="<?= $id ?>" data-qr="<?= e($link) ?>" data-size="600" data-color="<?= e($f['brand_primary']) ?>" style="width:180px;height:180px" class="qr-scaled"></div></div>
      <div class="small text-break text-muted mb-3"><?= e($link) ?></div>
      <button class="btn btn-ink btn-sm rounded-pill" data-qr-download="#<?= $id ?>" data-filename="<?= e($f['slug'] . '-' . $file) ?>-qr.png"><i class="bi bi-download"></i> Download PNG</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<h2 class="h4 mb-3">Print-ready designs</h2>
<div class="row g-4">
  <?php foreach ($prints as [$type, $label, $desc, $code, $icon]): $ok = has_service($f, $code); ?>
  <div class="col-md-6">
    <div class="card-soft p-4 h-100 d-flex gap-3">
      <div class="feature-icon flex-shrink-0"><i class="bi <?= $icon ?>"></i></div>
      <div class="flex-grow-1">
        <h3 class="h6 mb-1"><?= e($label) ?></h3>
        <p class="small text-secondary"><?= e($desc) ?></p>
        <?php if ($ok): ?><a href="<?= e(url('print.php?id=' . $fid . '&type=' . $type)) ?>" target="_blank" class="btn btn-sm btn-ink rounded-pill"><i class="bi bi-printer"></i> Open & print / save PDF</a>
        <?php else: ?><?= $unlock($code) ?><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php
$extraFoot = '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>renderQRs();document.querySelectorAll(".qr-scaled img,.qr-scaled canvas").forEach(function(el){el.style.width="180px";el.style.height="180px";});</script><style>.qr-scaled img,.qr-scaled canvas{width:180px!important;height:180px!important}</style>';
