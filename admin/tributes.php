<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
if (is_post()) {
    $t = row('SELECT * FROM tributes WHERE id = ?', [(int) input('id')]);
    if ($t && in_array(input('act'), ['approved', 'hidden', 'pending'], true)) q('UPDATE tributes SET status = ? WHERE id = ?', [input('act'), $t['id']]);
    elseif ($t && input('act') === 'delete') { delete_upload($t['photo']); q('DELETE FROM tributes WHERE id = ?', [$t['id']]); }
    back(url('admin/tributes.php'));
}
$st = input('status');
$list = rows('SELECT t.*, f.deceased_name, f.slug FROM tributes t JOIN funerals f ON f.id = t.funeral_id' . ($st ? ' WHERE t.status = ?' : '') . ' ORDER BY t.created_at DESC LIMIT 200', $st ? [$st] : []);
admin_header('Tributes (moderation)', 'tributes');
?>
<p class="small text-muted">Families moderate their own tributes. Use this page to remove anything abusive across the platform.</p>
<div class="d-flex gap-2 mb-3"><?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'hidden' => 'Hidden'] as $k => $l): ?><a href="?status=<?= $k ?>" class="btn btn-sm <?= $st === $k ? 'btn-dark' : 'btn-outline-secondary' ?>"><?= $l ?></a><?php endforeach; ?></div>
<div class="admin-card card"><div class="table-responsive"><table class="table small align-middle mb-0"><tbody>
<?php foreach ($list as $t): ?>
  <tr><td style="width:40%"><strong><?= e($t['author_name']) ?></strong> <span class="text-muted"><?= e($t['relationship']) ?></span><div><?= e(excerpt($t['message'], 160)) ?></div></td>
    <td><a href="<?= e(tribute_url($t)) ?>" target="_blank"><?= e($t['deceased_name']) ?></a></td><td><?= status_badge($t['status']) ?></td><td class="text-muted"><?= e(time_ago($t['created_at'])) ?></td>
    <td class="text-end text-nowrap"><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
      <?php if ($t['status'] !== 'approved'): ?><button name="act" value="approved" class="btn btn-sm btn-outline-success">Approve</button><?php endif; ?>
      <?php if ($t['status'] !== 'hidden'): ?><button name="act" value="hidden" class="btn btn-sm btn-outline-secondary">Hide</button><?php endif; ?>
      <button name="act" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></button></form></td></tr>
<?php endforeach; ?>
<?php if (!$list): ?><tr><td class="empty">No tributes.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php admin_footer();
