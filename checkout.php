<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['error_msg'] = "Please login to proceed to checkout.";
    header("Location: login.php?redirect=checkout.php");
    exit;
}

$cart_items = get_cart_details($pdo);
$cart_total = get_cart_total($pdo);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

// Shipping charge
$shipping = ($cart_total < 1999) ? 150.00 : 0.00;
$grand_total = $cart_total + $shipping;

// Retrieve logged in user's profile details to pre-populate shipping form
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([get_user_id()]);
$user = $user_stmt->fetch();

// Load layout header after processing redirects
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3">Secure Checkout</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <form action="place-order.php" method="POST">
        <div class="row g-4">
            <!-- Shipping Form -->
            <div class="col-lg-7">
                <div class="bg-white p-4 shadow-sm border">
                    <h4 class="font-serif mb-4 pb-2 border-bottom">Shipping Information</h4>
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="shipping_name" class="form-label fw-semibold">Recipient Full Name</label>
                            <input type="text" class="form-control border-secondary" id="shipping_name" name="shipping_name" value="<?= sanitize($user['name']) ?>" required style="border-radius:0;">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="shipping_phone" class="form-label fw-semibold">Contact Phone Number</label>
                            <input type="tel" class="form-control border-secondary" id="shipping_phone" name="shipping_phone" value="<?= sanitize($user['phone'] ?? '') ?>" required style="border-radius:0;">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="shipping_email" class="form-label fw-semibold">Email Address (for invoice)</label>
                            <input type="email" class="form-control border-secondary" id="shipping_email" value="<?= sanitize($user['email']) ?>" readonly style="border-radius:0; background-color: var(--light-gray);">
                        </div>
                        
                        <div class="col-12">
                            <label for="shipping_address" class="form-label fw-semibold">Complete Address (House, Block, Street)</label>
                            <textarea class="form-control border-secondary" id="shipping_address" name="shipping_address" rows="3" required style="border-radius:0;"><?= sanitize($user['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="shipping_city" class="form-label fw-semibold">City</label>
                            <input type="text" class="form-control border-secondary" id="shipping_city" name="shipping_city" value="<?= sanitize($user['city'] ?? '') ?>" required style="border-radius:0;">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="shipping_state" class="form-label fw-semibold">State</label>
                            <input type="text" class="form-control border-secondary" id="shipping_state" name="shipping_state" value="<?= sanitize($user['state'] ?? '') ?>" required style="border-radius:0;">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="shipping_zip" class="form-label fw-semibold">ZIP Code</label>
                            <input type="text" class="form-control border-secondary" id="shipping_zip" name="shipping_zip" value="<?= sanitize($user['zip'] ?? '') ?>" required style="border-radius:0;">
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 shadow-sm border mt-4">
                    <h4 class="font-serif mb-4 pb-2 border-bottom">Payment Method</h4>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="COD" checked>
                        <label class="form-check-label fw-bold" for="pay_cod">
                            Cash on Delivery (COD)
                        </label>
                        <p class="text-muted small mb-0">Pay with cash upon delivery of the handcrafted products at your doorstep.</p>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="pay_online" value="Online" disabled>
                        <label class="form-check-label fw-bold text-muted" for="pay_online">
                            Credit/Debit Card / UPI (Under Maintenance)
                        </label>
                        <p class="text-muted small mb-0">Online gateway integration is coming soon.</p>
                    </div>
                </div>
            </div>
            
            <!-- Order Sidebar Summary -->
            <div class="col-lg-5">
                <div class="bg-white p-4 shadow-sm border">
                    <h4 class="font-serif mb-4 pb-2 border-bottom">Order Details</h4>
                    
                    <div class="checkout-items-list mb-4" style="max-height: 250px; overflow-y: auto;" data-lenis-prevent>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 me-2">
                                <div class="d-flex align-items-center gap-2">
                                    <?= responsive_img($item['image_url'], $item['name'], SIZES_CHECKOUT, ['class' => 'bg-light p-1 border', 'width' => 50, 'height' => 50, 'style' => 'object-fit: contain;']) ?>
                                    <div>
                                        <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= sanitize($item['name']) ?>
                                        </h6>
                                        <?php if (!empty($item['variant_label'])): ?>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;"><?= sanitize($item['variant_label']) ?></small>
                                        <?php endif; ?>
                                        <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                                    </div>
                                </div>
                                <span class="fw-bold small"><?= format_price($item['subtotal']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Items Subtotal</span>
                        <span><?= format_price($cart_total) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Shipping Cost</span>
                        <span><?= ($shipping == 0) ? 'FREE' : format_price($shipping) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5 fw-bold">Grand Total</span>
                        <span class="fs-5 fw-bold text-gold"><?= format_price($grand_total) ?></span>
                    </div>
                    
                    <button type="submit" class="btn btn-gold w-100 py-3"><i class="bi bi-bag-check-fill me-2"></i> Place Order (COD)</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
