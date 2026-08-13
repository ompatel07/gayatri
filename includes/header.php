<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Prepare active page class helper
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php // Available to scripts that need to POST without a rendered form. ?>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <?php // A page may set $page_title / $page_description before including this file. ?>
    <title><?= isset($page_title)
        ? sanitize($page_title) . ' | The Gayatri Decors'
        : 'The Gayatri Decors | Premium Home Décor &amp; Clocks' ?></title>
    <meta name="description" content="<?= isset($page_description)
        ? sanitize($page_description)
        : 'Shop premium 3D MDF wall clocks, metal wall art, canvas frames, tabletop structures, and decorative mirrors from The Gayatri Decors.' ?>">
    <?php if ($current_page === 'index.php'):
        // Preload the first hero slide (the LCP element). imagesrcset/imagesizes
        // are used so the browser preloads exactly the variant it will render.
        $first_slide = hero_slides()[0]['img']; ?>
    <link rel="preload" as="image" type="image/webp"
          imagesrcset="<?= htmlspecialchars(img_srcset($first_slide), ENT_QUOTES, 'UTF-8') ?>"
          imagesizes="100vw">
    <?php endif; ?>
    <?php // Open the CDN/font connections while the HTML is still parsing ?>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <?php // Fonts were imported from inside style.css, which forced the browser to
          // download and parse the CSS before it could even request them. ?>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <?php if ($current_page === 'index.php'): ?>
    <?php // GSAP drives the intro overlay and nothing else, so it only loads on
          // the home page, and deferred rather than blocking the parser.
          // ScrollTrigger was removed: it was never used for any animation, only
          // as Lenis glue. Lenis went too - it hijacked the wheel for no gain,
          // and body.intro-active already handles the scroll lock. ?>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <?php endif; ?>

    <!-- Custom Style -->
    <link href="<?= asset_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/index.php">
            <img src="<?= BASE_URL ?>/assets/images/gayatri-full-logo.png" alt="The Gayatri Decors Logo" class="navbar-full-logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'shop.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/shop.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'about.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/about.php">About</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Categories
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach (category_tree($pdo) as $cat): ?>
                            <li>
                                <a class="dropdown-item<?= $cat['children'] ? ' fw-semibold' : '' ?>"
                                   href="<?= BASE_URL ?>/shop.php?category=<?= sanitize($cat['slug']) ?>">
                                    <?= sanitize($cat['name']) ?>
                                    <span class="text-muted small">(<?= (int)$cat['item_count'] ?>)</span>
                                </a>
                            </li>
                            <?php foreach ($cat['children'] as $sub): ?>
                                <li>
                                    <a class="dropdown-item dropdown-subitem"
                                       href="<?= BASE_URL ?>/shop.php?category=<?= sanitize($sub['slug']) ?>">
                                        <?= sanitize($sub['name']) ?>
                                        <span class="text-muted small">(<?= (int)$sub['item_count'] ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                <!-- Search -->
                <form action="<?= BASE_URL ?>/shop.php" method="GET" class="d-flex" role="search">
                    <input type="search" name="search" class="form-control form-control-sm border-secondary"
                           placeholder="Search products..." aria-label="Search products"
                           value="<?= isset($_GET['search']) ? sanitize($_GET['search']) : '' ?>"
                           style="border-radius: 0; max-width: 170px;">
                    <button class="btn btn-gold btn-sm" type="submit" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <!-- Cart -->
                <?php $cart_count = get_cart_count(); ?>
                <a href="<?= BASE_URL ?>/cart.php"
                   class="nav-link position-relative <?= ($current_page == 'cart.php') ? 'active' : '' ?>"
                   aria-label="Shopping cart<?= $cart_count ? ' (' . $cart_count . ' items)' : '' ?>">
                    <i class="bi bi-bag fs-5"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>

                <!-- Account -->
                <?php if (is_logged_in()): ?>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="d-none d-xl-inline"><?= sanitize(get_user_name()) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Console</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/customer/dashboard.php"><i class="bi bi-grid me-2"></i>My Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/customer/orders.php"><i class="bi bi-box-seam me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/customer/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-gold btn-sm">Login</a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-gold btn-sm d-none d-xl-inline-block">Register</a>
                <?php endif; ?>
            </div>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
</nav>
