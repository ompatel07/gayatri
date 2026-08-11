<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

csrf_guard();   // every cart action is state-changing

// Handle cart updates
$action     = isset($_POST['action']) ? trim($_POST['action']) : '';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$variant_id = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
$key        = isset($_POST['cart_key']) ? trim($_POST['cart_key']) : '';
$qty        = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add':
            $variants = get_variants($pdo, $product_id);

            // A product that has variants can only be added with one chosen.
            if ($variants && !$variant_id) {
                $_SESSION['error_msg'] = "Please choose a size and LED option first.";
                break;
            }

            if ($variant_id) {
                $variant = get_variant($pdo, $variant_id);
                if (!$variant || (int)$variant['product_id'] !== $product_id) {
                    $_SESSION['error_msg'] = "That option is no longer available.";
                    break;
                }
                $stock = (int)$variant['stock'];
                $label = ' (' . variant_label($variant) . ')';
            } else {
                $st = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $st->execute([$product_id]);
                $stock = (int)$st->fetchColumn();
                $label = '';
            }

            if ($stock < 1) {
                $_SESSION['error_msg'] = "Sorry, this option is currently out of stock.";
                break;
            }

            // Cap against what is already in the cart for this exact option.
            $existing = get_cart()[cart_key($product_id, $variant_id)]['qty'] ?? 0;
            if ($existing + $qty > $stock) {
                $qty = $stock - $existing;
                if ($qty < 1) {
                    $_SESSION['error_msg'] = "You already have all available stock in your cart.";
                    break;
                }
                $_SESSION['error_msg'] = "Only {$stock} in stock - quantity adjusted.";
            }

            add_to_cart($product_id, $qty, $variant_id);
            $_SESSION['success_msg'] = "Added to cart{$label}.";
            break;

        case 'update':
            $line = get_cart()[$key] ?? null;
            if (!$line) {
                break;
            }
            if (!empty($line['variant_id'])) {
                $variant = get_variant($pdo, $line['variant_id']);
                $stock = $variant ? (int)$variant['stock'] : 0;
            } else {
                $st = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $st->execute([(int)$line['product_id']]);
                $stock = (int)$st->fetchColumn();
            }
            if ($qty > $stock) {
                $qty = $stock;
                $_SESSION['error_msg'] = "Maximum available stock reached for this option.";
            }
            update_cart($key, $qty);
            $_SESSION['success_msg'] = "Cart updated successfully!";
            break;

        case 'remove':
            remove_from_cart($key);
            $_SESSION['success_msg'] = "Product removed from cart.";
            break;

        case 'clear':
            clear_cart();
            $_SESSION['success_msg'] = "Cart cleared.";
            break;
    }
    
    // Redirect to prevent form resubmission (safe to do here as no output has started)
    header("Location: cart.php");
    exit;
}

// Load navigation header after POST redirect logic
require_once __DIR__ . '/includes/header.php';

$cart_items = get_cart_details($pdo);
$cart_total = get_cart_total($pdo);

// Shipping calculation (free shipping over ₹1999, otherwise ₹150)
$shipping = 0;
if ($cart_total > 0 && $cart_total < 1999) {
    $shipping = 150.00;
}
$grand_total = $cart_total + $shipping;
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3">Shopping Cart</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cart</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <!-- Success/Error alerts -->
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:0;">
            <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:0;">
            <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5 shadow-sm" style="background-color: var(--white); border: 1px solid rgba(15, 23, 42, 0.08);">
            <i class="bi bi-cart-x fs-1 text-muted mb-3 d-block"></i>
            <h3 class="font-serif">Your Cart is Empty</h3>
            <p class="text-muted">You haven't added any products to your shopping cart yet.</p>
            <a href="shop.php" class="btn btn-gold mt-3">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Items Table -->
            <div class="col-lg-8">
                <div class="table-responsive bg-white shadow-sm p-3 border" data-lenis-prevent>
                    <table class="table cart-table align-middle">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 100px;">Product</th>
                                <th scope="col">Details</th>
                                <th scope="col">Price</th>
                                <th scope="col" style="width: 120px;">Qty</th>
                                <th scope="col">Total</th>
                                <th scope="col">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <?= responsive_img($item['image_url'], $item['name'], SIZES_CART, ['class' => 'img-fluid', 'width' => 80, 'height' => 80, 'style' => 'max-height: 80px; object-fit: contain;']) ?>
                                    </td>
                                    <td>
                                        <h6 class="mb-1 fw-bold">
                                            <a href="product.php?slug=<?= sanitize($item['slug']) ?>" class="text-decoration-none text-dark hover-gold">
                                                <?= sanitize($item['name']) ?>
                                            </a>
                                        </h6>
                                        <span class="small text-muted d-block">Category: <?= sanitize($item['category_name']) ?></span>
                                        <?php if (!empty($item['variant_label'])): ?>
                                            <span class="badge bg-dark-subtle text-dark border mt-1"><?= sanitize($item['variant_label']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= format_price($item['cart_price']) ?></td>
                                    <td>
                                        <form action="cart.php" method="POST" class="d-flex align-items-center">
        <?= csrf_field() ?>
                                            <input type="hidden" name="cart_key" value="<?= sanitize($item['cart_key']) ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="number" name="qty" class="form-control text-center border-secondary form-control-sm" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" style="width: 60px; border-radius:0;" onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td><?= format_price($item['subtotal']) ?></td>
                                    <td>
                                        <form action="cart.php" method="POST" onsubmit="return confirm('Are you sure you want to remove this item?');">
        <?= csrf_field() ?>
                                            <input type="hidden" name="cart_key" value="<?= sanitize($item['cart_key']) ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" style="border-radius:0;"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="shop.php" class="btn btn-outline-gold"><i class="bi bi-arrow-left me-2"></i> Continue Shopping</a>
                        <form action="cart.php" method="POST" onsubmit="return confirm('Are you sure you want to clear your cart?');">
        <?= csrf_field() ?>
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger" style="border-radius: 0;">Clear Cart</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card p-4 shadow-sm" style="border-radius: 0; background: var(--white); border: 1px solid rgba(15,23,42,0.1);">
                    <h4 class="font-serif mb-4 pb-2 border-bottom">Order Summary</h4>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-bold"><?= format_price($cart_total) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Shipping</span>
                        <span class="fw-bold"><?= ($shipping == 0) ? 'FREE' : format_price($shipping) ?></span>
                    </div>
                    <?php if ($shipping > 0): ?>
                        <div class="alert alert-warning p-2 small mb-3" style="border-radius: 0;">
                            <i class="bi bi-info-circle me-1"></i> Add items worth <?= format_price(1999 - $cart_total) ?> more for FREE shipping!
                        </div>
                    <?php endif; ?>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5 fw-bold">Total</span>
                        <span class="fs-5 fw-bold text-gold"><?= format_price($grand_total) ?></span>
                    </div>
                    
                    <a href="checkout.php" class="btn btn-gold w-100 py-3"><i class="bi bi-shield-lock me-2"></i> Proceed to Checkout</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
