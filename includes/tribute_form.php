<?php /* Shared tribute form. Expects $f and $formAction. */ ?>
<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="tribute">
  <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
  <div class="mb-2"><input name="author_name" class="form-control" placeholder="Your name *" required maxlength="120"></div>
  <div class="row g-2 mb-2">
    <div class="col-6"><input name="relationship" class="form-control" placeholder="Relationship" maxlength="80" list="relList"></div>
    <div class="col-6"><input name="location" class="form-control" placeholder="Location" maxlength="120"></div>
  </div>
  <datalist id="relList"><option>Son</option><option>Daughter</option><option>Grandchild</option><option>Sibling</option><option>Spouse</option><option>Friend</option><option>Colleague</option><option>Neighbour</option><option>Church member</option></datalist>
  <div class="mb-2"><textarea name="message" class="form-control" rows="5" placeholder="Your tribute or condolence message *" required maxlength="5000"></textarea></div>
  <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email (optional, not shown)"></div>
  <div class="mb-3"><label class="small text-muted">Add a photo (optional)</label><input type="file" name="photo" accept="image/*" class="form-control form-control-sm"></div>
  <button class="btn btn-brand w-100 rounded-pill"><i class="bi bi-send"></i> Post tribute</button>
  <?php if ($f['moderate_tributes']): ?><div class="small text-muted mt-2 text-center">Tributes appear after the family approves them.</div><?php endif; ?>
</form>
