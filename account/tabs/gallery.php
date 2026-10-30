<?php
if ($mode === 'post') {
    $act = input('act');
    if ($act === 'upload') {
        $count = (int) val('SELECT COUNT(*) FROM gallery WHERE funeral_id = ?', [$fid]);
        $added = 0;
        foreach (files_list('photos') as $file) {
            if ($count + $added >= 100) break;
            $path = upload_file($file, 'f' . $fid . '/gallery');
            if ($path) {
                insert('gallery', ['funeral_id' => $fid, 'file' => $path, 'sort' => $count + $added]);
                $added++;
            }
        }
        flash('success', $added . ' photo' . ($added === 1 ? '' : 's') . ' added.');
    } elseif ($act === 'save') {
        foreach ((array) ($_POST['caption'] ?? []) as $id => $cap) {
            q('UPDATE gallery SET caption = ?, sort = ? WHERE id = ? AND funeral_id = ?', [mb_substr(trim($cap), 0, 200), (int) ($_POST['sort'][$id] ?? 0), (int) $id, $fid]);
        }
        flash('success', 'Gallery updated.');
    } elseif ($act === 'delete') {
        $g = row('SELECT * FROM gallery WHERE id = ? AND funeral_id = ?', [(int) input('gid'), $fid]);
        if ($g) {
            delete_upload($g['file']);
            q('DELETE FROM gallery WHERE id = ?', [$g['id']]);
            flash('success', 'Photo removed.');
        }
    }
    return;
}
$photos = rows('SELECT * FROM gallery WHERE funeral_id = ? ORDER BY sort, id', [$fid]);
?>
<div class="card-soft p-4 mb-4">
  <h2 class="h5">Add photos</h2>
  <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
    <?= csrf_field() ?><input type="hidden" name="act" value="upload">
    <input type="file" name="photos[]" accept="image/*" multiple class="form-control" style="max-width:420px" required>
    <button class="btn btn-gold rounded-pill px-4"><i class="bi bi-cloud-upload"></i> Upload</button>
    <span class="small-muted">JPG/PNG/WEBP up to <?= MAX_UPLOAD_MB ?>MB each · <?= count($photos) ?>/100 used</span>
  </form>
</div>
<?php if (!$photos): ?><div class="card-soft empty"><i class="bi bi-images"></i>No photos yet. Upload childhood photos, family moments and celebrations.</div>
<?php else: ?>
<form method="post" id="galForm"><?= csrf_field() ?><input type="hidden" name="act" value="save"></form>
<div class="row g-3">
  <?php foreach ($photos as $g): ?>
  <div class="col-6 col-md-4 col-xl-3">
    <div class="card-soft overflow-hidden h-100">
      <img src="<?= e(img($g['file'])) ?>" class="w-100 object-cover" style="height:160px" alt="">
      <div class="p-2">
        <input form="galForm" name="caption[<?= (int) $g['id'] ?>]" class="form-control form-control-sm mb-1" placeholder="Caption" value="<?= e($g['caption']) ?>">
        <div class="d-flex gap-1">
          <input form="galForm" type="number" name="sort[<?= (int) $g['id'] ?>]" class="form-control form-control-sm" value="<?= (int) $g['sort'] ?>" title="Order" style="width:70px">
          <form method="post" data-confirm="Delete this photo?" class="ms-auto"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="gid" value="<?= (int) $g['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<button form="galForm" class="btn btn-ink rounded-pill mt-3"><i class="bi bi-check2"></i> Save captions & order</button>
<?php endif;
