<?php
require_once __DIR__ . '/includes/admin-header.php';

$view_order_no = isset($_GET['view']) ? sanitize($_GET['view']) : '';
$success_msg = '';
$error_msg = '';

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = (int)$_POST['order_id'];
    $order_status = sanitize($_POST['order_status']);
    $payment_status = sanitize($_POST['payment_status']);
    
    $update_stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
    try {
        $update_stmt->execute([$order_status, $payment_status, $order_id]);
        $success_msg = "Order status updated successfully!";
    } catch (Exception $e) {
        $error_msg = "Failed to update order: " . $e->getMessage();
    }
}

// Single View check
$viewing_single = false;
$order = null;
$order_items = [];

if (!empty($view_order_no)) {
    // Fetch details of a single order
    $order_stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                                 FROM orders o 
                                 LEFT JOIN users u ON o.user_id = u.id 
                                 WHERE o.order_number = ?");
    $order_stmt->execute([$view_order_no]);
    $order = $order_stmt->fetch();
    
    if ($order) {
        $viewing_single = true;
        // Fetch items
        $items_stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url 
                                     FROM order_items oi 
                                     LEFT JOIN products p ON oi.product_id = p.id 
                                     WHERE oi.order_id = ?");
        $items_stmt->execute([$order['id']]);
        $order_items = $items_stmt->fetchAll();
    }
}

// Listing orders if not single
if (!$viewing_single) {
    $all_orders = $pdo->query("SELECT o.*, u.name as customer_name 
                               FROM orders o 
                               LEFT JOIN users u ON o.user_id = u.id 
                               ORDER BY o.id DESC")->fetchAll();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2 class="font-serif"><?= $viewing_single ? 'Order Details Lookup' : 'Orders Fullfillment Center' ?></h2>
    <?php if ($viewing_single): ?>
        <a href="orders.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Orders</a>
    <?php endif; ?>
</div>

<!-- Alerts -->
<?php if ($success_msg): ?>
    <div class="alert alert-success" style="border-radius:0;"><?= $success_msg ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger" style="border-radius:0;"><?= $error_msg ?></div>
<?php endif; ?>

<?php if ($viewing_single): ?>
    <!-- Single Order Inspector -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="bg-white p-4 border shadow-sm mb-4">
                <h4 class="font-serif mb-4 pb-2 border-bottom">Order Details</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-uppercase small text-muted mb-2">Shipping Information</h6>
                        <p class="small mb-0">
                            <strong><?= sanitize($order['shipping_name']) ?></strong><br>
                            <?= sanitize($order['shipping_address']) ?><br>
                            <?= sanitize($order['shipping_city']) ?>, <?= sanitize($order['shipping_state']) ?> - <?= sanitize($order['shipping_zip']) ?><br>
                            <span class="text-muted">Phone:</span> <?= sanitize($order['shipping_phone']) ?><br>
                            <span class="text-muted">Email:</span> <?= sanitize($order['customer_email']) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-uppercase small text-muted mb-2">Order Information</h6>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Placed On:</span>
                            <span class="fw-semibold"><?= date('d F Y, h:i A', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Payment Method:</span>
                            <span class="fw-semibold"><?= sanitize($order['payment_method']) ?></span>
                        </div>
                    </div>
                </div>

                <h5 class="font-serif mb-3">Items Summary</h5>
                <div class="table-responsive" data-lenis-prevent>
                    <table class="table align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Item</th>
                                <th>Unit Price</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= BASE_URL ?>/<?= sanitize($item['image_url']) ?>" alt="" loading="lazy" decoding="async" width="40" height="40" style="object-fit: contain;" class="bg-light p-1 border">
                                            <span>
                                                <span class="fw-bold d-block"><?= sanitize($item['name']) ?></span>
                                                <?php if (!empty($item['variant_label'])): ?>
                                                    <small class="text-muted"><?= sanitize($item['variant_label']) ?></small>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?= format_price($item['price']) ?></td>
                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                    <td class="text-end fw-bold"><?= format_price($item['price'] * $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-light">
                                <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                <td class="text-end fw-bold text-gold fs-5"><?= format_price($order['total_amount']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Status Action Sidebar -->
        <div class="col-lg-4">
            <div class="bg-white p-4 border shadow-sm">
                <h4 class="font-serif mb-4 pb-2 border-bottom">Fulfillment Settings</h4>
                
                <form action="" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="order_status" class="form-label fw-bold">Fulfillment Status</label>
                        <select name="order_status" id="order_status" class="form-select border-secondary" style="border-radius:0;">
                            <option value="pending" <?= ($order['order_status'] == 'pending') ? 'selected' : '' ?>>Pending Review</option>
                            <option value="processing" <?= ($order['order_status'] == 'processing') ? 'selected' : '' ?>>Processing / Engraving</option>
                            <option value="shipped" <?= ($order['order_status'] == 'shipped') ? 'selected' : '' ?>>Shipped Out</option>
                            <option value="delivered" <?= ($order['order_status'] == 'delivered') ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= ($order['order_status'] == 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="payment_status" class="form-label fw-bold">Payment Status</label>
                        <select name="payment_status" id="payment_status" class="form-select border-secondary" style="border-radius:0;">
                            <option value="pending" <?= ($order['payment_status'] == 'pending') ? 'selected' : '' ?>>Pending Payment</option>
                            <option value="paid" <?= ($order['payment_status'] == 'paid') ? 'selected' : '' ?>>Paid (Settled)</option>
                            <option value="failed" <?= ($order['payment_status'] == 'failed') ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_order" class="btn btn-gold w-100 py-3"><i class="bi bi-save me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Orders Listing Table -->
    <div class="bg-white p-4 border shadow-sm">
        <div class="table-responsive" data-lenis-prevent>
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date Placed</th>
                        <th>Grand Total</th>
                        <th>Fulfillment</th>
                        <th>Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No orders found in database records.</td>
                        </tr>
                    <?php else: foreach ($all_orders as $ord): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= sanitize($ord['order_number']) ?></td>
                            <td><?= sanitize($ord['customer_name']) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?></td>
                            <td class="fw-bold text-gold"><?= format_price($ord['total_amount']) ?></td>
                            <td>
                                <?php 
                                $badge_class = 'bg-secondary';
                                if ($ord['order_status'] === 'pending') $badge_class = 'bg-warning text-dark';
                                elseif ($ord['order_status'] === 'processing') $badge_class = 'bg-info text-dark';
                                elseif ($ord['order_status'] === 'shipped') $badge_class = 'bg-primary';
                                elseif ($ord['order_status'] === 'delivered') $badge_class = 'bg-success';
                                elseif ($ord['order_status'] === 'cancelled') $badge_class = 'bg-danger';
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= strtoupper(sanitize($ord['order_status'])) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= strtoupper(sanitize($ord['payment_status'])) ?></span>
                            </td>
                            <td>
                                <a href="orders.php?view=<?= sanitize($ord['order_number']) ?>" class="btn btn-outline-gold btn-sm">Inspect</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
