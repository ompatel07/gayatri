<?php
require_once __DIR__ . '/includes/header.php';

// Retrieve filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$material_slug = isset($_GET['material']) ? trim($_GET['material']) : '';
$price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : '';
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'newest';

// Build dynamic SQL query
$query = "SELECT p.*, c.name as category_name, c.slug as category_slug, m.name as material_name, m.slug as material_slug 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN materials m ON p.material_id = m.id 
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_slug !== '') {
    $query .= " AND c.slug = ?";
    $params[] = $category_slug;
}

if ($material_slug !== '') {
    $query .= " AND m.slug = ?";
    $params[] = $material_slug;
}

if ($price_range !== '') {
    switch ($price_range) {
        case 'under_1500':
            $query .= " AND COALESCE(p.sale_price, p.price) < 1500";
            break;
        case '1500_3000':
            $query .= " AND COALESCE(p.sale_price, p.price) BETWEEN 1500 AND 3000";
            break;
        case 'above_3000':
            $query .= " AND COALESCE(p.sale_price, p.price) > 3000";
            break;
    }
}

// Sorting logic
switch ($sort_by) {
    case 'price_low':
        $query .= " ORDER BY COALESCE(p.sale_price, p.price) ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY COALESCE(p.sale_price, p.price) DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.id DESC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch filters options for panels
$all_categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$all_materials = $pdo->query("SELECT * FROM materials")->fetchAll();
?>

<!-- Shop Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-2">Product Catalog</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shop</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card p-4 shadow-sm" style="border-radius: 0; background-color: var(--white); border: 1px solid rgba(15, 23, 42, 0.08);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 font-serif">Filters</h5>
                    <a href="shop.php" class="small text-gold text-decoration-none">Clear All</a>
                </div>
                
                <form action="shop.php" method="GET">
                    <!-- Search inside filters (mobile support) -->
                    <?php if ($search !== ''): ?>
                        <input type="hidden" name="search" value="<?= sanitize($search) ?>">
                    <?php endif; ?>

                    <!-- Categories Filter -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-uppercase small letter-spacing-1">Category</label>
                        <select name="category" class="form-select border-secondary" onchange="this.form.submit()" style="border-radius:0;">
                            <option value="">All Categories</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?= $cat['slug'] ?>" <?= ($category_slug == $cat['slug']) ? 'selected' : '' ?>>
                                    <?= sanitize($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Material Filter -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-uppercase small letter-spacing-1">Material</label>
                        <select name="material" class="form-select border-secondary" onchange="this.form.submit()" style="border-radius:0;">
                            <option value="">All Materials</option>
                            <?php foreach ($all_materials as $mat): ?>
                                <option value="<?= $mat['slug'] ?>" <?= ($material_slug == $mat['slug']) ? 'selected' : '' ?>>
                                    <?= sanitize($mat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Price Filter -->
                    

                    <!-- Sort Filter -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-uppercase small letter-spacing-1">Sort By</label>
                        <select name="sort_by" class="form-select border-secondary" onchange="this.form.submit()" style="border-radius:0;">
                            <option value="newest" <?= ($sort_by == 'newest') ? 'selected' : '' ?>>Newest Arrivals</option>
                            <option value="price_low" <?= ($sort_by == 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_high" <?= ($sort_by == 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="text-muted mb-0">Showing <strong><?= count($products) ?></strong> product(s) found</p>
                <?php if ($search !== ''): ?>
                    <span class="badge bg-secondary p-2">Search: "<?= sanitize($search) ?>" <a href="shop.php" class="text-white ms-1"><i class="bi bi-x"></i></a></span>
                <?php endif; ?>
            </div>

            <?php if (empty($products)): ?>
                <div class="text-center py-5 shadow-sm" style="background-color: var(--white); border: 1px solid rgba(15, 23, 42, 0.08);">
                    <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                    <h3 class="font-serif">No Products Found</h3>
                    <p class="text-muted">Try adjusting your filters or search keywords.</p>
                    <a href="shop.php" class="btn btn-gold mt-3">Reset All Filters</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="card product-card">
                                <div class="img-container">
                                    <?php if ($product['is_featured']): ?>
                                        <span class="product-badge">Featured</span>
                                    <?php endif; ?>
                                    <?= responsive_img($product['image_url'], $product['name'], SIZES_CARD, ['class' => 'img-main', 'width' => 280, 'height' => 280]) ?>
                                    <?php if (!empty($product['hover_image_url'])): ?>
                                        <?= responsive_img($product['hover_image_url'], $product['name'], SIZES_CARD, ['class' => 'img-hover', 'width' => 280, 'height' => 280]) ?>
                                    <?php endif; ?>
                                    <div class="product-actions">
                                        <a href="<?= BASE_URL ?>/product.php?slug=<?= sanitize($product['slug']) ?>" class="btn btn-gold btn-sm"><i class="bi bi-eye"></i> View</a>
                                        <?php if (has_variants($pdo, $product['id'])): ?>
                                            <!-- Size / LED must be picked on the detail page -->
                                            <a href="<?= BASE_URL ?>/product.php?slug=<?= sanitize($product['slug']) ?>" class="btn btn-outline-gold btn-sm text-white border-white"><i class="bi bi-sliders"></i> Options</a>
                                        <?php else: ?>
                                            <form action="<?= BASE_URL ?>/cart.php" method="POST" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="qty" value="1">
                                                <button type="submit" class="btn btn-outline-gold btn-sm text-white border-white"><i class="bi bi-cart-plus"></i> Add</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <span class="product-cat"><?= sanitize($product['category_name']) ?> <?= !empty($product['material_name']) ? ' - ' . sanitize($product['material_name']) : '' ?></span>
                                    <h4 class="product-title">
                                        <a href="<?= BASE_URL ?>/product.php?slug=<?= sanitize($product['slug']) ?>"><?= sanitize($product['name']) ?></a>
                                    </h4>
                                    <div class="product-price">
                                        <?php $from = product_from_price($pdo, $product['id']); ?>
                                        <?php if ($from !== null): ?>
                                            <small class="text-muted">from</small> <span><?= format_price($from) ?></span>
                                        <?php elseif (!empty($product['sale_price'])): ?>
                                            <del><?= format_price($product['price']) ?></del>
                                            <span><?= format_price($product['sale_price']) ?></span>
                                        <?php else: ?>
                                            <span><?= format_price($product['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
