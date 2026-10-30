<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_editor.php';
require_admin();
$id = (int) input('id');
if (is_post()) {
    $p = $id ? row('SELECT * FROM posts WHERE id = ?', [$id]) : null;
    if (input('act') === 'delete' && $p) {
        delete_upload($p['cover']);
        q('DELETE FROM posts WHERE id = ?', [$id]);
        flash('success', 'Post deleted.');
        redirect(url('admin/posts.php'));
    }
    $status = input('status') === 'published' ? 'published' : 'draft';
    $data = [
        'title' => trim(input('title')) ?: 'Untitled', 'excerpt' => mb_substr(trim(input('excerpt')), 0, 300) ?: null,
        'content' => (string) ($_POST['content'] ?? ''), 'author' => trim(input('author')) ?: current_user()['name'], 'status' => $status,
        'published_at' => $status === 'published' ? (input('published_at') ? str_replace('T', ' ', input('published_at')) . ':00' : ($p['published_at'] ?? date('Y-m-d H:i:s'))) : ($p['published_at'] ?? null),
    ];
    $data['slug'] = unique_slug('posts', input('slug') ?: $data['title'], $id);
    try {
        $cover = upload_file($_FILES['cover'] ?? null, 'blog');
        if ($cover) { delete_upload($p['cover'] ?? null); $data['cover'] = $cover; }
        elseif (input('remove_cover')) { delete_upload($p['cover'] ?? null); $data['cover'] = null; }
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    }
    if ($p) update('posts', $data, 'id = ?', [$id]);
    else $id = insert('posts', $data + ['is_test' => test_flag()]);
    flash('success', 'Post saved.');
    redirect(url('admin/posts.php?id=' . $id));
}
$edit = $id ? row('SELECT * FROM posts WHERE id = ?', [$id]) : (input('new') ? ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'cover' => null, 'author' => setting('company_name'), 'status' => 'draft', 'published_at' => null] : null);
$GLOBALS['adminHead'] = editor_assets();
admin_header($edit ? ($edit['id'] ? 'Edit post' : 'New post') : 'Blog', 'posts');
if (!$edit):
    $posts = rows('SELECT * FROM posts ORDER BY COALESCE(published_at, created_at) DESC'); ?>
  <div class="text-end mb-3"><a href="?new=1" class="btn btn-dark btn-sm"><i class="bi bi-plus"></i> New post</a></div>
  <div class="admin-card card"><table class="table mb-0 align-middle small"><tbody>
  <?php foreach ($posts as $p): ?>
    <tr><td><strong><?= e($p['title']) ?></strong><?= $p['is_test'] ? ' <span class="badge text-bg-warning">test</span>' : '' ?><div class="text-muted"><?= e(excerpt($p['excerpt'], 90)) ?></div></td><td><?= status_badge($p['status']) ?></td><td class="text-muted"><?= e(fmt_date($p['published_at'])) ?></td>
      <td class="text-end"><a href="?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-dark">Edit</a> <a href="<?= e(post_url($p)) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a></td></tr>
  <?php endforeach; ?>
  <?php if (!$posts): ?><tr><td class="empty">No posts yet.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php else: ?>
  <form method="post" enctype="multipart/form-data" class="row g-4"><?= csrf_field() ?>
    <div class="col-xl-8"><div class="admin-card card p-4">
      <input name="title" class="form-control form-control-lg fw-semibold mb-2" value="<?= e($edit['title']) ?>" placeholder="Post title" required>
      <textarea name="excerpt" class="form-control mb-3" rows="2" placeholder="Short summary"><?= e($edit['excerpt']) ?></textarea>
      <?= editor_field('content', $edit['content']) ?>
    </div></div>
    <div class="col-xl-4"><div class="admin-card card p-4">
      <label class="form-label">Status</label><select name="status" class="form-select mb-2"><option value="draft">Draft</option><option value="published" <?= $edit['status'] === 'published' ? 'selected' : '' ?>>Published</option></select>
      <label class="form-label">Publish date</label><input type="datetime-local" name="published_at" class="form-control mb-2" value="<?= e($edit['published_at'] ? date('Y-m-d\TH:i', strtotime($edit['published_at'])) : '') ?>">
      <label class="form-label">Slug</label><input name="slug" class="form-control mb-2" value="<?= e($edit['slug']) ?>">
      <label class="form-label">Author</label><input name="author" class="form-control mb-2" value="<?= e($edit['author']) ?>">
      <label class="form-label">Cover image</label>
      <?php if ($edit['cover']): ?><img src="<?= e(img($edit['cover'])) ?>" class="w-100 rounded mb-1" alt=""><div class="form-check small"><input type="checkbox" class="form-check-input" name="remove_cover" value="1" id="rc"><label for="rc" class="form-check-label">Remove</label></div><?php endif; ?>
      <input type="file" name="cover" accept="image/*" class="form-control mb-3">
      <button class="btn btn-dark w-100">Save post</button><a href="posts.php" class="btn btn-link w-100">Back</a>
    </div></div>
  </form>
  <?php if ($edit['id']): ?><form method="post" data-confirm="Delete this post?"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><button class="btn btn-sm btn-link text-danger">Delete post</button></form><?php endif; ?>
<?php endif;
admin_footer($edit ? editor_script() : '');
