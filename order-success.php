<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';

if (empty($order_number)) {
    header("Location: index.php");
    exit;
}

// Load layout header after processing redirects
require_once __DIR__ . '/includes/header.php';

// Fetch order
$order_stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$order_stmt->execute([$order_number, get_user_id()]);
$order = $order_stmt->fetch();

if (!$order) {
    echo "<div class='container py-5 text-center'><h2>Order not found.</h2><a href='index.php' class='btn btn-gold mt-3'>Back to Home</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$order_id = $order['id'];

// Fetch order items
$items_stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url 
                             FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();
?>

<div class="container py-5 text-center" style="max-width: 600px;">
    <div class="card p-5 border shadow-sm bg-white" style="border-radius: 0;">
        <i class="bi bi-patch-check-fill text-success" style="font-size: 4rem;"></i>
        <h2 class="font-serif mt-3">Order Placed Successfully!</h2>
        <p class="text-muted">Thank you for shopping with The Gayatri Decors. Your order is being processed.</p>
        
        <div class="bg-light p-3 my-4 border text-start">
            <h6 class="fw-bold border-bottom pb-2">Summary Details</h6>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Order Number:</span>
                <span class="fw-bold text-dark"><?= sanitize($order['order_number']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Amount:</span>
                <span class="fw-bold text-gold"><?= format_price($order['total_amount']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Payment Type:</span>
                <span class="fw-bold"><?= sanitize($order['payment_method']) ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Delivery Address:</span>
                <span class="fw-bold text-end" style="max-width: 250px; font-size: 0.85rem;">
                    <?= sanitize($order['shipping_name']) ?><br>
                    <?= sanitize($order['shipping_address']) ?>, <?= sanitize($order['shipping_city']) ?>, <?= sanitize($order['shipping_state']) ?> - <?= sanitize($order['shipping_zip']) ?>
                </span>
            </div>
        </div>
        
        <div class="text-start mb-4">
            <h6 class="fw-bold mb-3">Items Ordered</h6>
            <?php foreach ($items as $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted">
                        <?= sanitize($item['name']) ?> <strong>x <?= $item['quantity'] ?></strong>
                        <?php if (!empty($item['variant_label'])): ?>
                            <em class="d-block" style="font-size: 0.78rem;"><?= sanitize($item['variant_label']) ?></em>
                        <?php endif; ?>
                    </span>
                    <span class="small fw-semibold"><?= format_price($item['price'] * $item['quantity']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex flex-column gap-2">
            <a href="customer/orders.php" class="btn btn-gold"><i class="bi bi-clock-history me-2"></i> Track Your Orders</a>
            <a href="shop.php" class="btn btn-outline-gold"><i class="bi bi-bag-plus me-2"></i> Back to Shop</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
