<?php
if ($mode === 'post') {
    $data = [
        'deceased_name' => trim(input('deceased_name')) ?: $f['deceased_name'],
        'nickname'      => trim(input('nickname')) ?: null,
        'heading'       => trim(input('heading')) ?: 'Celebrating the life of',
        'birth_date'    => input('birth_date') ?: null,
        'death_date'    => input('death_date') ?: null,
        'birth_place'   => trim(input('birth_place')) ?: null,
        'resting_place' => trim(input('resting_place')) ?: null,
        'quote'         => mb_substr(trim(input('quote')), 0, 255) ?: null,
        'obituary'      => trim(input('obituary')) ?: null,
        'bio'           => trim(input('bio')) ?: null,
        'family'        => trim(input('family')) ?: null,
        'visibility'    => input('visibility') === 'unlisted' ? 'unlisted' : 'public',
        'moderate_tributes' => input('moderate_tributes') ? 1 : 0,
    ];
    $slug = slugify(input('slug') ?: $data['deceased_name']);
    if ($slug !== $f['slug']) $data['slug'] = unique_slug('funerals', $slug, $fid);
    foreach (['photo', 'cover'] as $field) {
        $path = upload_file($_FILES[$field] ?? null, 'f' . $fid);
        if ($path) {
            delete_upload($f[$field]);
            $data[$field] = $path;
        } elseif (input('remove_' . $field)) {
            delete_upload($f[$field]);
            $data[$field] = null;
        }
    }
    update('funerals', $data, 'id = ?', [$fid]);
    flash('success', 'Tribute details saved.');
    return;
}
?>
<form method="post" enctype="multipart/form-data" class="row g-4">
  <?= csrf_field() ?>
  <div class="col-xl-8">
    <div class="card-soft p-4 mb-4">
      <h2 class="h5 mb-3">About your loved one</h2>
      <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Full name</label><input name="deceased_name" class="form-control" value="<?= e($f['deceased_name']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Also known as</label><input name="nickname" class="form-control" value="<?= e($f['nickname']) ?>" placeholder="e.g. Mama Grace"></div>
        <div class="col-md-6"><label class="form-label">Heading</label><input name="heading" class="form-control" value="<?= e($f['heading']) ?>" list="headings"><datalist id="headings"><option>Celebrating the life of</option><option>In loving memory of</option><option>Forever in our hearts</option><option>Promoted to glory</option><option>Rest in peace</option></datalist></div>
        <div class="col-md-3"><label class="form-label">Born</label><input type="date" name="birth_date" class="form-control" value="<?= e($f['birth_date']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Passed on</label><input type="date" name="death_date" class="form-control" value="<?= e($f['death_date']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Place of birth</label><input name="birth_place" class="form-control" value="<?= e($f['birth_place']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Resting place</label><input name="resting_place" class="form-control" value="<?= e($f['resting_place']) ?>"></div>
        <div class="col-12"><label class="form-label">Favourite verse or quote</label><input name="quote" class="form-control" maxlength="255" value="<?= e($f['quote']) ?>"></div>
      </div>
    </div>
    <div class="card-soft p-4 mb-4">
      <h2 class="h5 mb-3">Obituary, life story & family</h2>
      <div class="mb-3"><label class="form-label">Obituary / death announcement</label><textarea name="obituary" rows="4" class="form-control" placeholder="It is with humble acceptance of God's will that we announce…"><?= e($f['obituary']) ?></textarea></div>
      <div class="mb-3"><label class="form-label">Life story / eulogy</label><textarea name="bio" rows="12" class="form-control" placeholder="Early life, education, career, family, faith, passions…"><?= e($f['bio']) ?></textarea><div class="form-text">Use blank lines to separate paragraphs.</div></div>
      <div><label class="form-label">Family (survived by)</label><textarea name="family" rows="4" class="form-control"><?= e($f['family']) ?></textarea></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft p-4 mb-4">
      <h2 class="h5 mb-3">Photos</h2>
      <label class="form-label">Portrait</label>
      <div class="text-center mb-2"><img id="pPrev" src="<?= e(img($f['photo'])) ?>" class="rounded-circle object-cover <?= $f['photo'] ? '' : 'd-none' ?>" width="120" height="120" alt=""></div>
      <input type="file" name="photo" accept="image/*" class="form-control mb-1" data-preview="#pPrev">
      <?php if ($f['photo']): ?><div class="form-check small"><input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="rp"><label class="form-check-label" for="rp">Remove portrait</label></div><?php endif; ?>
      <label class="form-label mt-3">Cover banner <span class="small-muted">(wide image)</span></label>
      <img id="cPrev" src="<?= e(img($f['cover'])) ?>" class="w-100 rounded-3 object-cover mb-2 <?= $f['cover'] ? '' : 'd-none' ?>" style="height:100px" alt="">
      <input type="file" name="cover" accept="image/*" class="form-control mb-1" data-preview="#cPrev">
      <?php if ($f['cover']): ?><div class="form-check small"><input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="rc"><label class="form-check-label" for="rc">Remove cover</label></div><?php endif; ?>
    </div>
    <div class="card-soft p-4 mb-4">
      <h2 class="h5 mb-3">Page settings</h2>
      <label class="form-label">Page link</label>
      <div class="input-group mb-3"><span class="input-group-text small"><?= e(pretty() ? '/t/' : '?s=') ?></span><input name="slug" class="form-control" value="<?= e($f['slug']) ?>"></div>
      <label class="form-label">Visibility</label>
      <select name="visibility" class="form-select mb-3">
        <option value="public" <?= $f['visibility'] === 'public' ? 'selected' : '' ?>>Public – listed in Obituaries</option>
        <option value="unlisted" <?= $f['visibility'] === 'unlisted' ? 'selected' : '' ?>>Unlisted – only people with the link</option>
      </select>
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="moderate_tributes" value="1" id="mod" <?= $f['moderate_tributes'] ? 'checked' : '' ?>><label class="form-check-label" for="mod">Approve tributes before they appear</label></div>
    </div>
    <button class="btn btn-gold btn-lg w-100 rounded-pill"><i class="bi bi-check2"></i> Save details</button>
  </div>
</form>
