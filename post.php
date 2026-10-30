<?php
require __DIR__ . '/includes/bootstrap.php';
$p = row("SELECT * FROM posts WHERE slug = ? AND (status = 'published' OR ? = 1)", [input('s'), is_admin() ? 1 : 0]);
if (!$p) { http_response_code(404); exit('Article not found.'); }
$active = 'blog';
$pageTitle = $p['title'];
$metaDesc = $p['excerpt'] ?: excerpt($p['content'], 160);
$ogImage = $p['cover'] ? img($p['cover']) : null;
$related = rows("SELECT * FROM posts WHERE status = 'published' AND id <> ? ORDER BY published_at DESC LIMIT 3", [$p['id']]);
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container" style="max-width:820px"><div class="eyebrow"><?= e(fmt_date($p['published_at'], 'j F Y')) ?></div><h1><?= e($p['title']) ?></h1><?php if ($p['excerpt']): ?><p><?= e($p['excerpt']) ?></p><?php endif; ?></div></section>
<article class="section">
  <div class="container" style="max-width:780px">
    <?php if ($p['cover']): ?><img src="<?= e(img($p['cover'])) ?>" class="w-100 rounded-xl mb-4" alt=""><?php endif; ?>
    <div class="prose"><?= $p['content'] ?></div>
    <div class="d-flex gap-2 mt-5 pt-4 border-top"><span class="small-muted me-2 align-self-center">Share:</span>
      <a class="btn btn-sm btn-success rounded-pill" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($p['title'] . ' ' . post_url($p)) ?>"><i class="bi bi-whatsapp"></i></a>
      <a class="btn btn-sm btn-primary rounded-pill" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode(post_url($p)) ?>"><i class="bi bi-facebook"></i></a>
      <button class="btn btn-sm btn-outline-secondary rounded-pill" data-copy="<?= e(post_url($p)) ?>"><i class="bi bi-link-45deg"></i></button>
    </div>
  </div>
</article>
<?php if ($related): ?>
<section class="section-sm bg-cream"><div class="container"><h2 class="h4 mb-4">Keep reading</h2><div class="row g-4">
<?php foreach ($related as $m): ?><div class="col-md-4"><a href="<?= e(post_url($m)) ?>" class="card-soft d-block p-4 h-100 text-decoration-none text-reset hover-lift"><div class="small-muted"><?= e(fmt_date($m['published_at'])) ?></div><h3 class="h6 mt-1"><?= e($m['title']) ?></h3></a></div><?php endforeach; ?>
</div></div></section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php';
