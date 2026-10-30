<?php
require __DIR__ . '/includes/bootstrap.php';
$active = 'blog';
$pageTitle = 'Blog';
$perPage = 9;
$pg = max(1, (int) input('page', 1));
$total = (int) val("SELECT COUNT(*) FROM posts WHERE status = 'published'");
$posts = rows("SELECT * FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT $perPage OFFSET " . (($pg - 1) * $perPage));
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="eyebrow">Guidance & stories</div><h1>Blog</h1><p>Practical advice for families planning a funeral, and stories from the lives we celebrate.</p></div></section>
<section class="section bg-cream">
  <div class="container">
    <?php if (!$posts): ?><div class="empty"><i class="bi bi-journal-text"></i>No articles yet.</div><?php endif; ?>
    <div class="row g-4">
      <?php foreach ($posts as $p): ?>
      <div class="col-md-6 col-lg-4">
        <a href="<?= e(post_url($p)) ?>" class="card-soft d-block h-100 text-decoration-none text-reset hover-lift overflow-hidden">
          <?php if ($p['cover']): ?><img src="<?= e(img($p['cover'])) ?>" class="w-100 object-cover" style="height:200px" alt=""><?php else: ?><div style="height:8px;background:linear-gradient(90deg,var(--ink),var(--gold))"></div><?php endif; ?>
          <div class="p-4"><div class="small-muted mb-1"><?= e(fmt_date($p['published_at'])) ?> · <?= e($p['author']) ?></div><h2 class="h5"><?= e($p['title']) ?></h2><p class="small text-secondary mb-0"><?= e($p['excerpt'] ?: excerpt($p['content'])) ?></p></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-5"><?= paginate($total, $perPage, $pg, url('blog.php')) ?></div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
