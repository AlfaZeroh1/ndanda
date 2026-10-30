<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();
if (is_post()) {
    $id = (int) input('id');
    if (input('act') === 'delete') q('DELETE FROM messages WHERE id = ?', [$id]);
    elseif (input('act') === 'toggle') q('UPDATE messages SET is_read = 1 - is_read WHERE id = ?', [$id]);
    elseif (input('act') === 'read_all') q('UPDATE messages SET is_read = 1');
    back(url('admin/messages.php'));
}
$type = input('type');
$list = rows('SELECT * FROM messages' . ($type ? ' WHERE type = ?' : '') . ' ORDER BY is_read, created_at DESC LIMIT 200', $type ? [$type] : []);
if (input('open')) q('UPDATE messages SET is_read = 1 WHERE id = ?', [(int) input('open')]);
admin_header('Inbox', 'messages');
?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach (['' => 'All', 'contact' => 'Contact', 'livestream' => 'Live stream bookings', 'photography' => 'Photography bookings'] as $k => $l): ?><a href="?type=<?= $k ?>" class="btn btn-sm <?= $type === $k ? 'btn-dark' : 'btn-outline-secondary' ?>"><?= $l ?></a><?php endforeach; ?>
  <form method="post" class="ms-auto"><?= csrf_field() ?><button name="act" value="read_all" class="btn btn-sm btn-outline-secondary">Mark all read</button></form>
</div>
<div class="accordion" id="inbox">
<?php foreach ($list as $m): ?>
  <div class="accordion-item">
    <h2 class="accordion-header"><button class="accordion-button collapsed <?= $m['is_read'] ? '' : 'fw-bold' ?>" data-bs-toggle="collapse" data-bs-target="#m<?= (int) $m['id'] ?>">
      <?= $m['is_read'] ? '' : '<span class="badge text-bg-danger me-2">new</span>' ?><span class="badge text-bg-light border me-2"><?= e($m['type']) ?></span><?= e($m['name']) ?> — <?= e($m['subject']) ?><span class="ms-auto me-3 small text-muted fw-normal"><?= e(time_ago($m['created_at'])) ?></span></button></h2>
    <div id="m<?= (int) $m['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#inbox"><div class="accordion-body small">
      <p class="mb-2"><?= nl2br(e($m['message'])) ?></p>
      <div class="text-muted mb-3"><?= e($m['email']) ?> · <?= e($m['phone']) ?><?= $m['event_date'] ? ' · Event date: ' . e(fmt_date($m['event_date'])) : '' ?></div>
      <div class="d-flex gap-2">
        <?php if ($m['phone']): ?><a href="tel:<?= e($m['phone']) ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-telephone"></i> Call</a><a href="https://wa.me/<?= e(normalize_phone($m['phone']) ?: '') ?>" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
        <?php if ($m['email']): ?><a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-reply"></i> Email</a><?php endif; ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button name="act" value="toggle" class="btn btn-sm btn-outline-secondary">Mark <?= $m['is_read'] ? 'unread' : 'read' ?></button></form>
        <form method="post" data-confirm="Delete message?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button name="act" value="delete" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
      </div>
    </div></div>
  </div>
<?php endforeach; ?>
<?php if (!$list): ?><div class="admin-card card empty"><i class="bi bi-inbox"></i>No messages.</div><?php endif; ?>
</div>
<?php admin_footer();
