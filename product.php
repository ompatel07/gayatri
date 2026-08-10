<?php
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: shop.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';


// Fetch main product details
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, m.name as material_name 
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       LEFT JOIN materials m ON p.material_id = m.id 
                       WHERE p.slug = ?");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='container py-5 text-center'><h2>Product not found.</h2><a href='shop.php' class='btn btn-gold mt-3'>Back to Shop</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$product_id = $product['id'];

// Size / LED options from the client's price list. Empty for unlisted products,
// which keep their single products.price.
$variants = get_variants($pdo, $product_id);
$variant_sizes = [];
$variant_map   = [];
foreach ($variants as $v) {
    $variant_sizes[$v['size_label']] = true;
    $variant_map[$v['size_label'] . '|' . (int)$v['has_led']] = [
        'id'    => (int)$v['id'],
        'price' => (float)$v['price'],
        'label' => format_price($v['price']),
        'stock' => (int)$v['stock'],
    ];
}
$variant_sizes = array_keys($variant_sizes);

// Some products vary by size only; others also carry an option dimension
// (currently "Lighting": With LED / Without LED). Keyed by has_led so the
// option radio values line up with the variant map.
$option_group   = null;
$option_choices = [];
foreach ($variants as $v) {
    if (!empty($v['option_group'])) {
        $option_group = $v['option_group'];
        $option_choices[(int)$v['has_led']] = $v['option_label'];
    }
}
krsort($option_choices);                     // "With LED" first
$has_option_choice = count($option_choices) > 1;
$default_variant   = $variants[0] ?? null;
$in_stock        = $variants
    ? array_sum(array_map(fn($v) => (int)$v['stock'], $variants)) > 0
    : $product['stock'] > 0;

// Process review submission
$review_success = '';
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!is_logged_in()) {
        $review_error = "You must be logged in to submit a review.";
    } else {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        $user_id = get_user_id();
        
        if ($rating < 1 || $rating > 5) {
            $review_error = "Please select a valid rating between 1 and 5 stars.";
        } else {
            $insert_review = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            try {
                $insert_review->execute([$product_id, $user_id, $rating, $comment]);
                $review_success = "Thank you! Your review has been published.";
            } catch (Exception $e) {
                $review_error = "You have already reviewed this product.";
            }
        }
    }
}

// Fetch reviews
$reviews_stmt = $pdo->prepare("SELECT r.*, u.name as user_name 
                               FROM reviews r 
                               LEFT JOIN users u ON r.user_id = u.id 
                               WHERE r.product_id = ? 
                               ORDER BY r.created_at DESC");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate average rating
$avg_rating = 0;
if (count($reviews) > 0) {
    $total_stars = 0;
    foreach ($reviews as $rev) {
        $total_stars += $rev['rating'];
    }
    $avg_rating = round($total_stars / count($reviews), 1);
}

// Fetch related products
$related_stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE p.category_id = ? AND p.id != ? 
                               LIMIT 4");
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll();
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3"><?= sanitize($product['name']) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                <li class="breadcrumb-item"><a href="shop.php?category=<?= $product['category_slug'] ?>"><?= sanitize($product['category_name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= sanitize($product['name']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Gallery Column -->
        <div class="col-lg-6">
            <div class="product-detail-img p-2 bg-white mb-3 text-center">
                <?= responsive_img($product['image_url'], $product['name'], SIZES_DETAIL, [
                        'id' => 'mainProductImage', 'class' => 'img-fluid',
                        'style' => 'max-height: 500px; object-fit: contain;',
                        'lazy' => false,
                    ]) ?>
            </div>
            
            <!-- Gallery Thumbnails -->
            <?php
            // Prefer the full photo set; fall back to main + hover for products
            // that were never given a gallery.
            $gallery = get_gallery($pdo, $product_id);
            if (!$gallery) {
                $gallery = [$product['image_url']];
                if (!empty($product['hover_image_url']) && $product['hover_image_url'] !== $product['image_url']) {
                    $gallery[] = $product['hover_image_url'];
                }
            }
            ?>
            <?php if (count($gallery) > 1): ?>
                <div class="row g-2 product-gallery">
                    <?php foreach ($gallery as $gi => $shot): ?>
                        <div class="col-3">
                            <?= responsive_img($shot, $product['name'] . ' photo ' . ($gi + 1), SIZES_GALLERY, [
                                    'class' => 'img-thumbnail img-gallery-thumb' . ($gi === 0 ? ' is-active' : ''),
                                    'width' => 120, 'height' => 120,
                                ]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Info Column -->
        <div class="col-lg-6">
            <div class="ps-lg-3">
                <span class="text-uppercase text-muted small tracking-widest d-block mb-1">
                    <?= sanitize($product['category_name']) ?> <?= !empty($product['material_name']) ? ' - ' . sanitize($product['material_name']) : '' ?>
                </span>
                <h2 class="display-6 font-serif mb-2 text-dark"><?= sanitize($product['name']) ?></h2>
                
                <!-- Ratings summary -->
                <div class="d-flex align-items-center mb-3">
                    <div class="text-warning me-2">
                        <?php
                        $stars_count = round($avg_rating);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $stars_count) {
                                echo '<i class="bi bi-star-fill"></i>';
                            } else {
                                echo '<i class="bi bi-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <span class="small text-muted">(<?= count($reviews) ?> reviews)</span>
                </div>
                
                <div class="detail-price mb-3">
                    <?php if ($variants): ?>
                        <span id="variantPrice"><?= format_price($default_variant['price']) ?></span>
                    <?php elseif (!empty($product['sale_price'])): ?>
                        <del><?= format_price($product['price']) ?></del>
                        <span><?= format_price($product['sale_price']) ?></span>
                    <?php else: ?>
                        <span><?= format_price($product['price']) ?></span>
                    <?php endif; ?>
                </div>
                
                <p class="text-muted mb-4"><?= nl2br(sanitize($product['description'])) ?></p>
                
                <!-- Stock Status -->
                <div class="mb-4">
                    <span class="fw-bold">Availability: </span>
                    <?php if (!$in_stock): ?>
                        <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Out of Stock</span>
                    <?php elseif ($variants): ?>
                        <span class="text-success" id="variantStock">
                            <i class="bi bi-check-circle-fill"></i> In Stock (<?= (int)$default_variant['stock'] ?> units left)
                        </span>
                    <?php else: ?>
                        <span class="text-success"><i class="bi bi-check-circle-fill"></i> In Stock (<?= $product['stock'] ?> units left)</span>
                    <?php endif; ?>
                </div>

                <!-- Cart Form -->
                <?php if ($in_stock): ?>
                    <form action="cart.php" method="POST" class="mb-3" id="addToCartForm">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="action" value="add">

                        <?php if ($variants): ?>
                            <input type="hidden" name="variant_id" id="variantId" value="<?= (int)$default_variant['id'] ?>">

                            <div class="mb-3 variant-picker">
                                <label class="form-label fw-bold text-uppercase small letter-spacing-1">Size (inches)</label>
                                <div class="d-flex flex-wrap gap-2" id="sizeOptions">
                                    <?php foreach ($variant_sizes as $i => $size): ?>
                                        <input type="radio" class="btn-check" name="size" id="size<?= $i ?>"
                                               value="<?= sanitize($size) ?>" <?= $i === 0 ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-gold btn-sm" for="size<?= $i ?>"><?= sanitize($size) ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($has_option_choice): ?>
                                <div class="mb-3 variant-picker">
                                    <label class="form-label fw-bold text-uppercase small letter-spacing-1"><?= sanitize($option_group) ?></label>
                                    <div class="d-flex flex-wrap gap-2" id="ledOptions">
                                        <?php foreach ($option_choices as $val => $label): ?>
                                            <input type="radio" class="btn-check" name="led" id="opt<?= (int)$val ?>"
                                                   value="<?= (int)$val ?>" <?= (int)$val === (int)$default_variant['has_led'] ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-gold btn-sm" for="opt<?= (int)$val ?>">
                                                <i class="bi bi-lightbulb<?= $val ? '-fill' : '' ?> me-1"></i><?= sanitize($label) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="led" value="<?= (int)$default_variant['has_led'] ?>">
                                <?php if (!empty($default_variant['option_label'])): ?>
                                    <p class="small text-muted mb-3">
                                        <i class="bi bi-lightbulb-fill text-warning me-1"></i>
                                        <?= sanitize($option_group) ?>:
                                        <strong><?= sanitize($default_variant['option_label']) ?></strong>
                                        &mdash; standard on this design.
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <label for="quantity" class="visually-hidden">Quantity</label>
                                <input type="number" name="qty" id="quantity" class="form-control text-center border-secondary"
                                       value="1" min="1"
                                       max="<?= $variants ? (int)$default_variant['stock'] : (int)$product['stock'] ?>"
                                       style="width: 80px; border-radius:0;">
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-gold w-100" id="addToCartBtn">
                                    <i class="bi bi-cart-plus me-2"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php if ($variants): ?>
                        <script>window.TGD_VARIANTS = <?= json_encode($variant_map, JSON_UNESCAPED_UNICODE) ?>;</script>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- WhatsApp Customization / Inquiry Button -->
                <div class="mb-4">
                    <a href="https://wa.me/919227147646?text=Hello%20The%20Gayatri%20Decors%2C%20I%20am%20interested%20in%20'<?= urlencode($product['name']) ?>'%20and%20want%20more%20details." 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="btn btn-outline-success w-100 py-2 d-flex align-items-center justify-content-center gap-2" 
                       style="border-radius: 0; font-weight: 600;">
                        <i class="bi bi-whatsapp fs-5 text-success"></i>
                        <span>Ask About This Design</span>
                    </a>
                </div>
                
                <!-- Extra Product Info Accordion -->
                <div class="accordion accordion-flush border-top border-bottom" id="productSpecs">
                    <div class="accordion-item bg-transparent">
                        <h2 class="accordion-header" id="specsHeading">
                            <button class="accordion-button collapsed bg-transparent text-dark fw-bold px-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#specsCollapse" aria-expanded="false" aria-controls="specsCollapse">
                                Features & Specifications
                            </button>
                        </h2>
                        <div id="specsCollapse" class="accordion-collapse collapse" aria-labelledby="specsHeading" data-bs-parent="#productSpecs">
                            <div class="accordion-body px-0 small text-muted" data-lenis-prevent>
                                <ul>
                                    <li>Material: Premium MDF Wood / Grade A Metal / Glossy Acrylic (Based on selection)</li>
                                    <li>Precision laser-cut detailing for crisp, modern edges</li>
                                    <li>Easy mounting system (hooks provided)</li>
                                    <li>For Clocks: Silent high-torque sweep movement mechanism (No ticking sound)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="row mt-5 pt-4 border-top">
        <div class="col-lg-6">
            <h3 class="font-serif mb-4">Customer Reviews (<?= count($reviews) ?>)</h3>
            
            <?php if (empty($reviews)): ?>
                <p class="text-muted">No reviews yet for this product. Be the first to share your thoughts!</p>
            <?php else: ?>
                <div class="review-list">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="card p-3 mb-3" style="border-radius: 0; background: var(--white); border: 1px solid rgba(15, 23, 42, 0.08);">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="mb-0 fw-bold"><?= sanitize($rev['user_name']) ?></h6>
                                <span class="small text-muted"><?= date('F d, Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div class="text-warning mb-2 small">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $rev['rating']) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                }
                                ?>
                            </div>
                            <p class="mb-0 text-muted small"><?= nl2br(sanitize($rev['comment'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-6">
            <div class="card p-4" style="border-radius: 0; border: 1px solid rgba(15,23,42,0.1); background-color: var(--white);">
                <h4 class="font-serif mb-3">Write a Review</h4>
                
                <?php if ($review_success): ?>
                    <div class="alert alert-success"><?= $review_success ?></div>
                <?php endif; ?>
                <?php if ($review_error): ?>
                    <div class="alert alert-danger"><?= $review_error ?></div>
                <?php endif; ?>
                
                <?php if (is_logged_in()): ?>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label d-block fw-semibold">Your Rating</label>
                            <div class="rating-select fs-4 d-flex gap-2">
                                <i class="bi bi-star-fill text-warning" data-rating="1" style="cursor:pointer;"></i>
                                <i class="bi bi-star-fill text-warning" data-rating="2" style="cursor:pointer;"></i>
                                <i class="bi bi-star-fill text-warning" data-rating="3" style="cursor:pointer;"></i>
                                <i class="bi bi-star-fill text-warning" data-rating="4" style="cursor:pointer;"></i>
                                <i class="bi bi-star-fill text-warning" data-rating="5" style="cursor:pointer;"></i>
                            </div>
                            <input type="hidden" name="rating" id="rating-input" value="5">
                        </div>
                        <div class="mb-3">
                            <label for="reviewComment" class="form-label fw-semibold">Your Comment</label>
                            <textarea class="form-control border-secondary" id="reviewComment" name="comment" rows="4" style="border-radius:0;" required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-gold w-100">Submit Review</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">You must be logged in to submit a review.</p>
                    <a href="login.php" class="btn btn-outline-gold w-100">Login to Review</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div class="mt-5 pt-5 border-top">
            <div class="text-center mb-5">
                <h3 class="font-serif">You May Also Like</h3>
                <div style="width: 40px; height: 3px; background-color: var(--primary-gold); margin: 10px auto;"></div>
            </div>
            <div class="row">
                <?php foreach ($related_products as $rel): ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="card product-card">
                            <div class="img-container">
                                <?= responsive_img($rel['image_url'], $rel['name'], SIZES_FEATURED, ['class' => 'img-main', 'width' => 280, 'height' => 280]) ?>
                                <?php if (!empty($rel['hover_image_url'])): ?>
                                    <?= responsive_img($rel['hover_image_url'], $rel['name'], SIZES_FEATURED, ['class' => 'img-hover', 'width' => 280, 'height' => 280]) ?>
                                <?php endif; ?>
                                <div class="product-actions">
                                    <a href="<?= BASE_URL ?>/product.php?slug=<?= sanitize($rel['slug']) ?>" class="btn btn-gold btn-sm"><i class="bi bi-eye"></i> View</a>
                                </div>
                            </div>
                            <div class="product-info">
                                <span class="product-cat"><?= sanitize($rel['category_name']) ?></span>
                                <h4 class="product-title">
                                    <a href="<?= BASE_URL ?>/product.php?slug=<?= sanitize($rel['slug']) ?>"><?= sanitize($rel['name']) ?></a>
                                </h4>
                                <div class="product-price">
                                    <?php if (!empty($rel['sale_price'])): ?>
                                        <del><?= format_price($rel['price']) ?></del>
                                        <span><?= format_price($rel['sale_price']) ?></span>
                                    <?php else: ?>
                                        <span><?= format_price($rel['price']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
