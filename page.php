<?php
/** Generic CMS page (FAQ, terms, privacy and any page created in admin). */
require __DIR__ . '/includes/bootstrap.php';
$p = page(input('s'));
if (!$p) { http_response_code(404); $p = ['title' => 'Page not found', 'subtitle' => '', 'content' => '<p>The page you are looking for does not exist.</p>', 'meta_description' => '']; }
$active = $p['slug'] ?? '';
$pageTitle = $p['title'];
$metaDesc = $p['meta_description'];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><h1><?= e($p['title']) ?></h1><?php if ($p['subtitle']): ?><p><?= e($p['subtitle']) ?></p><?php endif; ?></div></section>
<section class="section"><div class="container" style="max-width:820px"><div class="prose"><?= $p['content'] ?></div></div></section>
<?php require __DIR__ . '/includes/footer.php';
