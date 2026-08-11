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
    <form action="<?= BASE_URL ?>/place-order.php" method="POST" id="checkoutForm">
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
                        <input class="form-check-input" type="radio" name="payment_method" id="pay_online"
                               value="Razorpay" <?= razorpay_enabled() ? '' : 'disabled' ?>>
                        <label class="form-check-label fw-bold <?= razorpay_enabled() ? '' : 'text-muted' ?>" for="pay_online">
                            Card / UPI / Netbanking / Wallet
                            <?= razorpay_enabled() ? '' : '(Unavailable)' ?>
                        </label>
                        <?php if (razorpay_enabled()): ?>
                            <p class="text-muted small mb-0">
                                Pay securely via Razorpay. Your card details never touch our servers.
                            </p>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Online payment is not configured yet.</p>
                        <?php endif; ?>
                    </div>

                    <div id="payErrorBox" class="alert alert-danger mt-3 mb-0 d-none" style="border-radius:0;"
                         role="alert" aria-live="polite"></div>
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
                    
                    <button type="submit" class="btn btn-gold w-100 py-3" id="placeOrderBtn">
                        <span id="placeOrderLabel"><i class="bi bi-bag-check-fill me-2"></i> Place Order (COD)</span>
                        <span id="placeOrderSpinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php if (razorpay_enabled()): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
    var form    = document.getElementById('checkoutForm');
    var btn     = document.getElementById('placeOrderBtn');
    var label   = document.getElementById('placeOrderLabel');
    var spinner = document.getElementById('placeOrderSpinner');
    var errBox  = document.getElementById('payErrorBox');
    var CREATE  = '<?= BASE_URL ?>/api/create-order.php';
    var VERIFY  = '<?= BASE_URL ?>/api/verify-payment.php';
    if (!form || !btn) return;

    function online() {
        var r = form.querySelector('input[name="payment_method"]:checked');
        return r && r.value === 'Razorpay';
    }
    function busy(on) {
        btn.disabled = on;
        label.classList.toggle('d-none', on);
        spinner.classList.toggle('d-none', !on);
    }
    function fail(msg) {
        busy(false);
        errBox.textContent = msg;
        errBox.classList.remove('d-none');
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function clearErr() { errBox.classList.add('d-none'); errBox.textContent = ''; }

    // Keep the button honest about what it will do
    function syncLabel() {
        label.innerHTML = online()
            ? '<i class="bi bi-shield-lock-fill me-2"></i> Pay Securely'
            : '<i class="bi bi-bag-check-fill me-2"></i> Place Order (COD)';
    }
    form.querySelectorAll('input[name="payment_method"]').forEach(function (r) {
        r.addEventListener('change', function () { clearErr(); syncLabel(); });
    });
    syncLabel();

    form.addEventListener('submit', function (e) {
        if (!online()) return;              // COD posts normally
        e.preventDefault();
        clearErr();

        if (typeof Razorpay === 'undefined') {
            fail('Could not load the payment gateway. Check your connection and try again.');
            return;
        }
        busy(true);

        fetch(CREATE, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
            if (!res.ok) throw new Error(res.d.error || 'Could not start the payment.');
            var d = res.d;

            var rzp = new Razorpay({
                key: d.key_id,
                order_id: d.order_id,
                amount: d.amount,
                currency: d.currency,
                name: d.name,
                description: 'Order payment',
                image: '<?= BASE_URL ?>/assets/images/logo.png',
                prefill: d.prefill,
                theme: { color: '#6B7A2F' },
                handler: function (resp) {
                    busy(true);
                    var fd = new FormData();
                    fd.append('razorpay_payment_id', resp.razorpay_payment_id);
                    fd.append('razorpay_order_id',   resp.razorpay_order_id);
                    fd.append('razorpay_signature',  resp.razorpay_signature);

                    fetch(VERIFY, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function (r) { return r.json().then(function (x) { return { ok: r.ok, d: x }; }); })
                        .then(function (v) {
                            if (v.ok && v.d.success) {
                                window.location.href = v.d.redirect;
                            } else {
                                fail(v.d.error || 'We could not verify your payment.');
                            }
                        })
                        .catch(function () {
                            fail('Payment taken but verification failed. Please contact us before paying again.');
                        });
                },
                modal: {
                    ondismiss: function () {
                        busy(false);
                        fail('Payment cancelled. Your cart is still saved.');
                    }
                }
            });

            rzp.on('payment.failed', function (resp) {
                var d = (resp && resp.error) || {};
                fail('Payment failed: ' + (d.description || 'please try another method.'));
            });

            busy(false);
            rzp.open();
        })
        .catch(function (err) { fail(err.message || 'Something went wrong. Please try again.'); });
    });
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
