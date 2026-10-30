<?php
/** Private funeral preparations workspace: tasks, budget, documents and notes. Standard (free) feature. */
$sections = ['tasks' => ['Tasks', 'bi-check2-square'], 'budget' => ['Budget', 'bi-calculator'], 'documents' => ['Documents', 'bi-file-earmark-text'], 'notes' => ['Notes & plans', 'bi-journal']];
$sec = array_key_exists(input('sec'), $sections) ? input('sec') : 'tasks';
$budgetCats = ['Mortuary', 'Casket', 'Transport', 'Venue', 'Catering', 'Printing', 'Media', 'Church', 'Burial site', 'Flowers', 'Attire', 'Other'];
$docCats = ['Permits & certificates', 'Identification', 'Quotes & invoices', 'Receipts', 'Photos', 'Programme drafts', 'Other'];

if ($mode === 'post') {
    $act = input('act');
    $id = (int) input('rid');
    switch ($act) {
        case 'task_add':
            if (trim(input('title')) === '') throw new RuntimeException('Enter a task.');
            insert('prep_tasks', ['funeral_id' => $fid, 'title' => mb_substr(input('title'), 0, 200), 'assignee' => mb_substr(input('assignee'), 0, 120) ?: null, 'due_date' => input('due_date') ?: null]);
            break;
        case 'task_toggle':
            q('UPDATE prep_tasks SET is_done = 1 - is_done WHERE id = ? AND funeral_id = ?', [$id, $fid]);
            break;
        case 'task_delete':
            q('DELETE FROM prep_tasks WHERE id = ? AND funeral_id = ?', [$id, $fid]);
            break;
        case 'budget_save':
            if (trim(input('item')) === '') throw new RuntimeException('Enter the budget item.');
            $data = ['category' => in_array(input('category'), $budgetCats, true) ? input('category') : 'Other', 'item' => mb_substr(input('item'), 0, 160), 'estimated' => (float) input('estimated'), 'actual' => (float) input('actual'), 'is_paid' => input('is_paid') ? 1 : 0, 'notes' => mb_substr(input('notes'), 0, 255) ?: null];
            if ($id) update('prep_budget', $data, 'id = ? AND funeral_id = ?', [$id, $fid]);
            else insert('prep_budget', $data + ['funeral_id' => $fid]);
            break;
        case 'budget_delete':
            q('DELETE FROM prep_budget WHERE id = ? AND funeral_id = ?', [$id, $fid]);
            break;
        case 'doc_upload':
            $file = $_FILES['file'] ?? null;
            $path = upload_file($file, 'f' . $fid, 'doc');
            if (!$path) throw new RuntimeException('Choose a file to upload.');
            insert('prep_documents', ['funeral_id' => $fid, 'title' => mb_substr(trim(input('title')) ?: $file['name'], 0, 160), 'category' => in_array(input('category'), $docCats, true) ? input('category') : 'Other', 'file' => $path, 'original_name' => mb_substr($file['name'], 0, 200), 'size' => (int) $file['size']]);
            flash('success', 'Document uploaded securely.');
            break;
        case 'doc_delete':
            $d = row('SELECT * FROM prep_documents WHERE id = ? AND funeral_id = ?', [$id, $fid]);
            if ($d) { delete_upload($d['file']); q('DELETE FROM prep_documents WHERE id = ?', [$id]); }
            break;
        case 'note_save':
            $data = ['title' => mb_substr(trim(input('title')) ?: 'Untitled note', 0, 160), 'body' => input('body')];
            if ($id) update('prep_notes', $data, 'id = ? AND funeral_id = ?', [$id, $fid]);
            else insert('prep_notes', $data + ['funeral_id' => $fid]);
            flash('success', 'Note saved.');
            break;
        case 'note_delete':
            q('DELETE FROM prep_notes WHERE id = ? AND funeral_id = ?', [$id, $fid]);
            break;
    }
    redirect(tab_url('preparations', '&sec=' . $sec));
}
$secUrl = fn($s) => tab_url('preparations', '&sec=' . $s);
$hidden = csrf_field() . '<input type="hidden" name="sec" value="' . e($sec) . '">';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h2 class="h4 mb-0">Funeral preparations</h2><div class="small-muted"><i class="bi bi-lock"></i> Private to you — never shown publicly.</div></div>
  <ul class="nav nav-pills">
    <?php foreach ($sections as $k => [$l, $i]): ?><li class="nav-item"><a class="nav-link <?= $sec === $k ? 'active bg-dark' : 'text-dark' ?>" href="<?= e($secUrl($k)) ?>"><i class="bi <?= $i ?>"></i> <?= $l ?></a></li><?php endforeach; ?>
  </ul>
</div>

<?php if ($sec === 'tasks'):
    $tasks = rows('SELECT * FROM prep_tasks WHERE funeral_id = ? ORDER BY is_done, due_date IS NULL, due_date, id', [$fid]);
    $doneCount = count(array_filter($tasks, fn($t) => $t['is_done'])); ?>
  <div class="card-soft p-4">
    <div class="d-flex justify-content-between mb-2"><strong>Committee tasks</strong><span class="small-muted"><?= $doneCount ?>/<?= count($tasks) ?> done</span></div>
    <div class="progress progress-thin mb-3"><div class="progress-bar bg-success" style="width:<?= count($tasks) ? round($doneCount / count($tasks) * 100) : 0 ?>%"></div></div>
    <form method="post" class="row g-2 mb-3"><?= $hidden ?><input type="hidden" name="act" value="task_add">
      <div class="col-md-6"><input name="title" class="form-control" placeholder="e.g. Book the hearse" required></div>
      <div class="col-md-3"><input name="assignee" class="form-control" placeholder="Assigned to"></div>
      <div class="col-md-2"><input type="date" name="due_date" class="form-control"></div>
      <div class="col-md-1"><button class="btn btn-ink w-100"><i class="bi bi-plus"></i></button></div>
    </form>
    <?php if (!$tasks): ?><div class="empty"><i class="bi bi-check2-square"></i>Add tasks like burial permit, mortuary, catering, tents, transport.</div><?php endif; ?>
    <?php foreach ($tasks as $t): $overdue = !$t['is_done'] && $t['due_date'] && $t['due_date'] < date('Y-m-d'); ?>
      <div class="d-flex align-items-center gap-3 py-2 border-bottom">
        <form method="post"><?= $hidden ?><input type="hidden" name="act" value="task_toggle"><input type="hidden" name="rid" value="<?= (int) $t['id'] ?>"><button class="btn btn-link p-0 fs-5 <?= $t['is_done'] ? 'text-success' : 'text-muted' ?>"><i class="bi <?= $t['is_done'] ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i></button></form>
        <div class="flex-grow-1 <?= $t['is_done'] ? 'text-decoration-line-through text-muted' : '' ?>"><?= e($t['title']) ?><div class="small text-muted"><?= e($t['assignee']) ?><?= $t['due_date'] ? ' · <span class="' . ($overdue ? 'text-danger fw-semibold' : '') . '">Due ' . e(fmt_date($t['due_date'], 'D j M')) . '</span>' : '' ?></div></div>
        <form method="post"><?= $hidden ?><input type="hidden" name="act" value="task_delete"><input type="hidden" name="rid" value="<?= (int) $t['id'] ?>"><button class="btn btn-sm btn-link text-danger"><i class="bi bi-x-lg"></i></button></form>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($sec === 'budget'):
    $items = rows('SELECT * FROM prep_budget WHERE funeral_id = ? ORDER BY category, id', [$fid]);
    $est = array_sum(array_column($items, 'estimated'));
    $act = array_sum(array_column($items, 'actual'));
    $paidSum = array_sum(array_map(fn($b) => $b['is_paid'] ? (float) ($b['actual'] ?: $b['estimated']) : 0, $items));
    $received = (float) val("SELECT COALESCE(SUM(amount),0) FROM contributions WHERE funeral_id = ? AND status = 'received'", [$fid]);
    $byCat = [];
    foreach ($items as $b) $byCat[$b['category']] = ($byCat[$b['category']] ?? 0) + (float) ($b['actual'] ?: $b['estimated']); ?>
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat"><div class="stat-label">Estimated</div><div class="stat-num"><?= e(money($est)) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat"><div class="stat-label">Actual spent</div><div class="stat-num"><?= e(money($act)) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat"><div class="stat-label">Contributions received</div><div class="stat-num text-success"><?= e(money($received)) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat"><div class="stat-label"><?= $received - $est >= 0 ? 'Surplus' : 'Shortfall' ?></div><div class="stat-num <?= $received - $est >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(money(abs($received - $est))) ?></div></div></div>
  </div>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card-soft table-responsive">
        <table class="table align-middle mb-0 small">
          <thead><tr class="text-muted"><th>Item</th><th>Category</th><th class="text-end">Estimated</th><th class="text-end">Actual</th><th>Paid</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($items as $b): ?>
            <tr><td><strong><?= e($b['item']) ?></strong><div class="text-muted"><?= e($b['notes']) ?></div></td><td><?= e($b['category']) ?></td><td class="text-end"><?= number_format((float) $b['estimated']) ?></td><td class="text-end"><?= number_format((float) $b['actual']) ?></td><td><?= $b['is_paid'] ? '<span class="badge text-bg-success">Paid</span>' : '<span class="badge text-bg-light border">Unpaid</span>' ?></td>
              <td class="text-end text-nowrap"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#budgetModal" data-row="<?= e(json_encode($b)) ?>"><i class="bi bi-pencil"></i></button>
                <form method="post" class="d-inline" data-confirm="Remove this item?"><?= $hidden ?><input type="hidden" name="act" value="budget_delete"><input type="hidden" name="rid" value="<?= (int) $b['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
          <?php endforeach; ?>
          <?php if (!$items): ?><tr><td colspan="6" class="empty">No budget items yet.</td></tr><?php endif; ?>
          </tbody>
          <?php if ($items): ?><tfoot><tr class="fw-bold"><td colspan="2">Total</td><td class="text-end"><?= number_format($est) ?></td><td class="text-end"><?= number_format($act) ?></td><td colspan="2"></td></tr></tfoot><?php endif; ?>
        </table>
      </div>
      <button class="btn btn-gold rounded-pill mt-3" data-bs-toggle="modal" data-bs-target="#budgetModal" data-row="{}"><i class="bi bi-plus-lg"></i> Add budget item</button>
    </div>
    <div class="col-xl-4">
      <div class="card-soft p-4">
        <h3 class="h6">By category</h3>
        <?php $max = max($byCat ?: [1]); foreach ($byCat as $cat => $amt): ?>
          <div class="mb-2"><div class="d-flex justify-content-between small"><span><?= e($cat) ?></span><span><?= number_format($amt) ?></span></div><div class="progress progress-thin"><div class="progress-bar" style="width:<?= round($amt / $max * 100) ?>%;background:var(--gold)"></div></div></div>
        <?php endforeach; ?>
        <?php if (!$byCat): ?><div class="small-muted">Add items to see the breakdown.</div><?php endif; ?>
        <div class="small-muted mt-3">Paid so far: <strong><?= e(money($paidSum)) ?></strong></div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="budgetModal" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><?= $hidden ?><input type="hidden" name="act" value="budget_save"><input type="hidden" name="rid" id="b_id">
    <div class="modal-header"><h5 class="modal-title">Budget item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-2">
      <div class="col-12"><label class="form-label">Item</label><input name="item" id="b_item" class="form-control" required></div>
      <div class="col-12"><label class="form-label">Category</label><select name="category" id="b_cat" class="form-select"><?php foreach ($budgetCats as $c): ?><option><?= $c ?></option><?php endforeach; ?></select></div>
      <div class="col-6"><label class="form-label">Estimated (KES)</label><input type="number" step="any" name="estimated" id="b_est" class="form-control"></div>
      <div class="col-6"><label class="form-label">Actual (KES)</label><input type="number" step="any" name="actual" id="b_act" class="form-control"></div>
      <div class="col-12"><label class="form-label">Notes</label><input name="notes" id="b_notes" class="form-control" placeholder="Supplier, phone, M-Pesa code…"></div>
      <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="is_paid" id="b_paid" value="1"><label for="b_paid" class="form-check-label">Paid</label></div>
    </div>
    <div class="modal-footer"><button class="btn btn-gold rounded-pill px-4">Save</button></div></form></div></div>
  <?php $extraFoot = "<script>document.getElementById('budgetModal').addEventListener('show.bs.modal',function(e){const r=JSON.parse(e.relatedTarget.dataset.row||'{}');b_id.value=r.id||'';b_item.value=r.item||'';b_cat.value=r.category||'Other';b_est.value=r.estimated||'';b_act.value=r.actual||'';b_notes.value=r.notes||'';b_paid.checked=r.is_paid==1;});</script>"; ?>

<?php elseif ($sec === 'documents'):
    $docs = rows('SELECT * FROM prep_documents WHERE funeral_id = ? ORDER BY created_at DESC', [$fid]); ?>
  <div class="card-soft p-4 mb-4">
    <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end"><?= $hidden ?><input type="hidden" name="act" value="doc_upload">
      <div class="col-md-4"><label class="form-label small">Title</label><input name="title" class="form-control" placeholder="e.g. Burial permit"></div>
      <div class="col-md-3"><label class="form-label small">Category</label><select name="category" class="form-select"><?php foreach ($docCats as $c): ?><option><?= $c ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label small">File (PDF, Word, Excel, image)</label><input type="file" name="file" class="form-control" required></div>
      <div class="col-md-2"><button class="btn btn-ink w-100"><i class="bi bi-cloud-upload"></i> Upload</button></div>
    </form>
  </div>
  <div class="card-soft">
    <?php if (!$docs): ?><div class="empty"><i class="bi bi-file-earmark-lock"></i>Keep the burial permit, death certificate, IDs, quotes and receipts safe here.</div><?php endif; ?>
    <?php foreach ($docs as $d): $ext = strtolower(pathinfo($d['file'], PATHINFO_EXTENSION)); ?>
      <div class="d-flex align-items-center gap-3 p-3 border-bottom">
        <i class="bi bi-filetype-<?= e(in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'txt', 'csv', 'ppt', 'pptx'], true) ? $ext : 'raw') ?> fs-3 text-gold"></i>
        <div class="flex-grow-1"><div class="fw-semibold"><?= e($d['title']) ?></div><div class="small text-muted"><?= e($d['category']) ?> · <?= number_format($d['size'] / 1024) ?> KB · <?= e(fmt_date($d['created_at'])) ?></div></div>
        <a href="<?= e(url('account/download.php?id=' . $d['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
        <form method="post" data-confirm="Delete this document?"><?= $hidden ?><input type="hidden" name="act" value="doc_delete"><input type="hidden" name="rid" value="<?= (int) $d['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
      </div>
    <?php endforeach; ?>
  </div>

<?php else:
    $notes = rows('SELECT * FROM prep_notes WHERE funeral_id = ? ORDER BY COALESCE(updated_at, created_at) DESC', [$fid]);
    $edit = input('note') ? row('SELECT * FROM prep_notes WHERE id = ? AND funeral_id = ?', [(int) input('note'), $fid]) : null; ?>
  <div class="row g-4">
    <div class="col-lg-5">
      <a href="<?= e($secUrl('notes')) ?>" class="btn btn-gold rounded-pill mb-3"><i class="bi bi-plus-lg"></i> New note</a>
      <?php foreach ($notes as $n): ?>
        <a href="<?= e($secUrl('notes') . '&note=' . $n['id']) ?>" class="card-soft d-block p-3 mb-2 text-decoration-none text-reset <?= $edit && $edit['id'] == $n['id'] ? 'border-warning' : '' ?>">
          <div class="fw-semibold"><?= e($n['title']) ?></div><div class="small text-muted"><?= e(excerpt($n['body'], 90)) ?></div>
        </a>
      <?php endforeach; ?>
      <?php if (!$notes): ?><div class="small-muted">No notes yet — capture committee decisions, wishes of the deceased, contacts and plans.</div><?php endif; ?>
    </div>
    <div class="col-lg-7">
      <form method="post" class="card-soft p-4"><?= $hidden ?><input type="hidden" name="act" value="note_save"><input type="hidden" name="rid" value="<?= (int) ($edit['id'] ?? 0) ?>">
        <input name="title" class="form-control form-control-lg mb-2 border-0 fw-semibold" placeholder="Note title" value="<?= e($edit['title'] ?? '') ?>">
        <textarea name="body" class="form-control mb-3" rows="14" placeholder="Write your notes and plans…"><?= e($edit['body'] ?? '') ?></textarea>
        <div class="d-flex gap-2"><button class="btn btn-ink rounded-pill px-4">Save note</button>
          <?php if ($edit): ?><button class="btn btn-outline-danger rounded-pill ms-auto" name="act" value="note_delete" onclick="return confirm('Delete this note?')">Delete</button><?php endif; ?></div>
      </form>
    </div>
  </div>
<?php endif;
