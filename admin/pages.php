<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_editor.php';
require_admin();
$id = (int) input('id');
if (is_post()) {
    if (input('act') === 'delete') {
        q('DELETE FROM pages WHERE id = ? AND is_system = 0', [$id]);
        flash('success', 'Page deleted.');
        redirect(url('admin/pages.php'));
    }
    $data = [
        'title' => trim(input('title')) ?: 'Untitled', 'subtitle' => trim(input('subtitle')) ?: null,
        'content' => (string) ($_POST['content'] ?? ''), 'meta_description' => mb_substr(trim(input('meta_description')), 0, 255) ?: null,
        'is_published' => input('is_published') ? 1 : 0, 'show_in_footer' => input('show_in_footer') ? 1 : 0,
    ];
    if ($id) {
        $p = row('SELECT * FROM pages WHERE id = ?', [$id]);
        if ($p && !$p['is_system'] && input('slug')) $data['slug'] = unique_slug('pages', input('slug'), $id);
        update('pages', $data, 'id = ?', [$id]);
    } else {
        $id = insert('pages', $data + ['slug' => unique_slug('pages', input('slug') ?: $data['title'])]);
    }
    flash('success', 'Page saved.');
    redirect(url('admin/pages.php?id=' . $id));
}
$edit = $id ? row('SELECT * FROM pages WHERE id = ?', [$id]) : (input('new') ? ['id' => 0, 'slug' => '', 'title' => '', 'subtitle' => '', 'content' => '', 'meta_description' => '', 'is_system' => 0, 'is_published' => 1, 'show_in_footer' => 0] : null);
$GLOBALS['adminHead'] = editor_assets();
$publicUrl = ['about' => 'about.php', 'how-it-works' => 'how-it-works.php', 'livestream' => 'livestream.php', 'photography' => 'photography.php'];
admin_header($edit ? ($edit['id'] ? 'Edit page: ' . $edit['title'] : 'New page') : 'Website pages', 'pages');
if (!$edit):
    $pages = rows('SELECT * FROM pages ORDER BY is_system DESC, title'); ?>
  <div class="d-flex justify-content-between mb-3"><p class="text-muted small mb-0">Edit the content of public pages. Home page headline & contact details are under <a href="settings.php">Settings</a>.</p><a href="?new=1" class="btn btn-dark btn-sm"><i class="bi bi-plus"></i> New page</a></div>
  <div class="admin-card card"><table class="table mb-0 align-middle small"><tbody>
  <?php foreach ($pages as $p): ?>
    <tr><td><strong><?= e($p['title']) ?></strong><div class="text-muted">/<?= e($publicUrl[$p['slug']] ?? 'page.php?s=' . $p['slug']) ?></div></td>
      <td><?= $p['is_system'] ? '<span class="badge text-bg-light border">System</span>' : '' ?> <?= $p['is_published'] ? status_badge('published') : status_badge('draft') ?> <?= $p['show_in_footer'] ? '<span class="badge text-bg-info">Footer</span>' : '' ?></td>
      <td class="text-muted"><?= e($p['updated_at'] ? time_ago($p['updated_at']) : '') ?></td>
      <td class="text-end"><a href="?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-dark">Edit</a> <a href="<?= e(url($publicUrl[$p['slug']] ?? 'page.php?s=' . $p['slug'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php else: ?>
  <form method="post" class="row g-4"><?= csrf_field() ?>
    <div class="col-xl-8">
      <div class="admin-card card p-4">
        <input name="title" class="form-control form-control-lg fw-semibold mb-2" value="<?= e($edit['title']) ?>" placeholder="Page title" required>
        <input name="subtitle" class="form-control mb-3" value="<?= e($edit['subtitle']) ?>" placeholder="Subtitle (shown under the title)">
        <?= editor_field('content', $edit['content']) ?>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="admin-card card p-4">
        <label class="form-label">URL slug</label><input name="slug" class="form-control mb-1" value="<?= e($edit['slug']) ?>" <?= $edit['is_system'] ? 'readonly' : '' ?>>
        <?php if ($edit['is_system']): ?><div class="form-text mb-2">System page – slug is fixed.</div><?php endif; ?>
        <label class="form-label mt-2">Meta description (SEO)</label><textarea name="meta_description" class="form-control mb-3" rows="3"><?= e($edit['meta_description']) ?></textarea>
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" id="pub" <?= $edit['is_published'] ? 'checked' : '' ?>><label class="form-check-label" for="pub">Published</label></div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="show_in_footer" value="1" id="ftr" <?= $edit['show_in_footer'] ? 'checked' : '' ?>><label class="form-check-label" for="ftr">Show link in footer</label></div>
        <button class="btn btn-dark w-100">Save page</button>
        <a href="pages.php" class="btn btn-link w-100">Back</a>
      </div>
    </div>
  </form>
  <?php if ($edit['id'] && !$edit['is_system']): ?><form method="post" data-confirm="Delete this page?" class="mt-2"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><button class="btn btn-sm btn-link text-danger">Delete page</button></form><?php endif; ?>
<?php endif;
admin_footer($edit ? editor_script() : '');
