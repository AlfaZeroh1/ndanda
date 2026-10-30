<?php
/** Public site header. Set $pageTitle, $metaDesc, $active, $extraHead, $bodyClass, $ogImage before including. */
$pageTitle = $pageTitle ?? setting('site_name');
$metaDesc = $metaDesc ?? setting('site_tagline');
$active = $active ?? '';
$user = current_user();
$nav = [
    'home' => ['Home', url()],
    'how' => ['How it works', url('how-it-works.php')],
    'pricing' => ['Pricing', url('pricing.php')],
    'obituaries' => ['Obituaries', url('obituaries.php')],
    'livestream' => ['Live stream', url('livestream.php')],
    'photography' => ['Photography', url('photography.php')],
];
$more = [
    'about' => ['About us', url('about.php')],
    'blog' => ['Blog', url('blog.php')],
    'contact' => ['Contact us', url('contact.php')],
    'faq' => ['FAQs', url('page.php?s=faq')],
];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle === setting('site_name') ? $pageTitle . ' · ' . setting('site_tagline') : $pageTitle . ' · ' . setting('site_name')) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<meta name="theme-color" content="#1b1f2a">
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<?= $extraHead ?? '' ?>
<script>window.APP = {base: <?= json_encode(rtrim(BASE_URL, '/')) ?>, csrf: <?= json_encode(csrf_token()) ?>};</script>
</head>
<body class="<?= e($bodyClass ?? '') ?>" <?= $bodyAttr ?? '' ?>>
<?php if (test_flag() && empty($hideTestBanner)): ?>
<div class="test-banner"><i class="bi bi-cone-striped"></i> Test mode — sample data shown, payments may be simulated.</div>
<?php endif; ?>
<?php if (empty($hideNav)): ?>
<nav class="navbar navbar-expand-lg site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url()) ?>">
      <span class="brand-mark"><i class="bi bi-flower2"></i></span>
      <span class="brand-text"><?= e(setting('site_name')) ?><small>by <?= e(setting('company_name')) ?></small></span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainNav" aria-label="Menu"><i class="bi bi-list fs-2"></i></button>
    <div class="offcanvas offcanvas-end" id="mainNav" tabindex="-1">
      <div class="offcanvas-header"><h5 class="offcanvas-title"><?= e(setting('site_name')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
      <div class="offcanvas-body">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
          <?php foreach ($nav as $k => [$label, $href]): ?>
            <li class="nav-item"><a class="nav-link<?= $active === $k ? ' active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a></li>
          <?php endforeach; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle<?= isset($more[$active]) ? ' active' : '' ?>" href="#" data-bs-toggle="dropdown">More</a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php foreach ($more as $k => [$label, $href]): ?>
                <li><a class="dropdown-item" href="<?= e($href) ?>"><?= e($label) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <?php if ($user): ?>
            <li class="nav-item dropdown ms-lg-2">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> <?= e(explode(' ', $user['name'])[0]) ?></a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= e(url('account/')) ?>"><i class="bi bi-grid me-2"></i>My funeral accounts</a></li>
                <?php if ($user['role'] === 'admin'): ?><li><a class="dropdown-item" href="<?= e(url('admin/')) ?>"><i class="bi bi-speedometer2 me-2"></i>Admin panel</a></li><?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item ms-lg-2"><a class="nav-link" href="<?= e(url('login.php')) ?>">Log in</a></li>
          <?php endif; ?>
          <li class="nav-item ms-lg-2 mt-3 mt-lg-0"><a class="btn btn-gold rounded-pill px-4" href="<?= e(url('create.php')) ?>"><i class="bi bi-plus-lg me-1"></i>Create a tribute</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>
<main>
<?php $fl = empty($deferFlash) ? flashes() : ''; if ($fl): ?><div class="container mt-3"><?= $fl ?></div><?php endif; ?>
