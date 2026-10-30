<?php
if ($mode === 'post') {
    $act = input('act');
    $tid = (int) input('tid');
    if (in_array($act, ['approved', 'hidden', 'pending'], true)) {
        q('UPDATE tributes SET status = ? WHERE id = ? AND funeral_id = ?', [$act, $tid, $fid]);
        flash('success', 'Tribute updated.');
    } elseif ($act === 'approve_all') {
        q("UPDATE tributes SET status = 'approved' WHERE funeral_id = ? AND status = 'pending'", [$fid]);
        flash('success', 'All pending tributes approved.');
    } elseif ($act === 'delete') {
        $t = row('SELECT * FROM tributes WHERE id = ? AND funeral_id = ?', [$tid, $fid]);
        if ($t) {
            delete_upload($t['photo']);
            q('DELETE FROM tributes WHERE id = ?', [$tid]);
            flash('success', 'Tribute deleted.');
        }
    } elseif ($act === 'add') {
        if (trim(input('author_name')) === '' || trim(input('message')) === '') throw new RuntimeException('Name and message are required.');
        insert('tributes', [
            'funeral_id' => $fid, 'author_name' => mb_substr(input('author_name'), 0, 120), 'relationship' => mb_substr(input('relationship'), 0, 80) ?: null,
            'location' => mb_substr(input('location'), 0, 120) ?: null, 'message' => input('message'),
            'photo' => upload_file($_FILES['photo'] ?? null, 'f' . $fid . '/tributes'), 'status' => 'approved', 'is_test' => test_flag(),
        ]);
        flash('success', 'Tribute added.');
    }
    return;
}
$filter = in_array(input('status'), ['pending', 'approved', 'hidden'], true) ? input('status') : '';
$list = rows('SELECT * FROM tributes WHERE funeral_id = ?' . ($filter ? ' AND status = ?' : '') . ' ORDER BY status = \'pending\' DESC, created_at DESC', $filter ? [$fid, $filter] : [$fid]);
$counts = [];
foreach (rows('SELECT status, COUNT(*) c FROM tributes WHERE funeral_id = ? GROUP BY status', [$fid]) as $r) $counts[$r['status']] = (int) $r['c'];
?>
<div class="row g-4">
  <div class="col-xl-8">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
      <?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'hidden' => 'Hidden'] as $k => $l): ?>
        <a href="<?= e(tab_url('tributes', $k ? '&status=' . $k : '')) ?>" class="btn btn-sm rounded-pill <?= $filter === $k ? 'btn-ink' : 'btn-outline-secondary' ?>"><?= $l ?> <?= $k ? '(' . ($counts[$k] ?? 0) . ')' : '' ?></a>
      <?php endforeach; ?>
      <?php if (!empty($counts['pending'])): ?>
        <form method="post" class="ms-auto"><?= csrf_field() ?><input type="hidden" name="act" value="approve_all"><button class="btn btn-sm btn-success rounded-pill"><i class="bi bi-check2-all"></i> Approve all pending</button></form>
      <?php endif; ?>
    </div>
    <?php if (!$list): ?><div class="card-soft empty"><i class="bi bi-chat-heart"></i>No tributes yet. Share the tribute form link with family and friends.</div><?php endif; ?>
    <?php foreach ($list as $t): ?>
      <div class="card-soft p-3 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-2">
          <div><strong><?= e($t['author_name']) ?></strong> <span class="small-muted"><?= e(implode(' · ', array_filter([$t['relationship'], $t['location'], $t['email']]))) ?></span></div>
          <div><?= status_badge($t['status']) ?> <span class="small-muted"><?= e(time_ago($t['created_at'])) ?></span></div>
        </div>
        <p class="mb-2 mt-2 small"><?= nl2br(e($t['message'])) ?></p>
        <?php if ($t['photo']): ?><img src="<?= e(img($t['photo'])) ?>" class="rounded-3 mb-2" style="max-height:120px" alt=""><?php endif; ?>
        <div class="d-flex gap-1 flex-wrap">
          <?php foreach (['approved' => ['Approve', 'success', 'check2'], 'hidden' => ['Hide', 'secondary', 'eye-slash']] as $st => [$l, $c, $i]): if ($t['status'] !== $st): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="<?= $st ?>"><input type="hidden" name="tid" value="<?= (int) $t['id'] ?>"><button class="btn btn-sm btn-outline-<?= $c ?>"><i class="bi bi-<?= $i ?>"></i> <?= $l ?></button></form>
          <?php endif; endforeach; ?>
          <form method="post" data-confirm="Delete this tribute permanently?"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="tid" value="<?= (int) $t['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="col-xl-4">
    <div class="card-soft p-4 mb-4">
      <h2 class="h6"><i class="bi bi-link-45deg text-gold"></i> Tribute form link</h2>
      <p class="small-muted">Share this with relatives and friends so they can submit tributes.</p>
      <div class="copy-field mb-2"><input class="form-control" id="formLink" value="<?= e(form_url($f)) ?>" readonly><button class="btn btn-outline-secondary" data-copy-target="#formLink"><i class="bi bi-clipboard"></i></button></div>
      <a class="btn btn-success btn-sm rounded-pill" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode('Please share your tribute for ' . $f['deceased_name'] . ': ' . form_url($f)) ?>"><i class="bi bi-whatsapp"></i> Send via WhatsApp</a>
    </div>
    <div class="card-soft p-4">
      <h2 class="h6">Add a tribute on someone's behalf</h2>
      <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="act" value="add">
        <input name="author_name" class="form-control form-control-sm mb-2" placeholder="Name" required>
        <div class="row g-2 mb-2"><div class="col"><input name="relationship" class="form-control form-control-sm" placeholder="Relationship"></div><div class="col"><input name="location" class="form-control form-control-sm" placeholder="Location"></div></div>
        <textarea name="message" class="form-control form-control-sm mb-2" rows="4" placeholder="Message" required></textarea>
        <input type="file" name="photo" accept="image/*" class="form-control form-control-sm mb-2">
        <button class="btn btn-ink btn-sm rounded-pill w-100">Add tribute</button>
      </form>
    </div>
  </div>
</div>
