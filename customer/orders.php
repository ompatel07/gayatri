<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
check_login();
require_once __DIR__ . '/../includes/header.php';

$user_id = get_user_id();
$view_order_no = isset($_GET['view']) ? sanitize($_GET['view']) : '';

// Variable defaults
$viewing_single = false;
$order = null;
$order_items = [];

if (!empty($view_order_no)) {
    // Fetch details of a single order
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
    $order_stmt->execute([$view_order_no, $user_id]);
    $order = $order_stmt->fetch();
    
    if ($order) {
        $viewing_single = true;
        // Fetch items
        $items_stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url, p.slug 
                                     FROM order_items oi 
                                     LEFT JOIN products p ON oi.product_id = p.id 
                                     WHERE oi.order_id = ?");
        $items_stmt->execute([$order['id']]);
        $order_items = $items_stmt->fetchAll();
    }
}

// Fetch all orders if not viewing single
if (!$viewing_single) {
    $orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $orders_stmt->execute([$user_id]);
    $all_orders = $orders_stmt->fetchAll();
}
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3"><?= $viewing_single ? 'Order Details' : 'My Orders' ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $viewing_single ? 'Order: ' . $view_order_no : 'Orders History' ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Dashboard Sidebar Menu -->
        <div class="col-lg-3">
            <div class="card p-3 border shadow-sm" style="border-radius: 0; background-color: var(--white);">
                <div class="text-center py-3 border-bottom mb-3">
                    <i class="bi bi-person-circle fs-1 text-gold"></i>
                    <h5 class="font-serif mt-2 mb-0"><?= sanitize(get_user_name()) ?></h5>
                    <small class="text-muted"><?= sanitize($_SESSION['user_email']) ?></small>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action border-0 py-3"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                    <a href="orders.php" class="list-group-item list-group-item-action active border-0 py-3" style="background-color: var(--primary-gold); color: var(--navy-dark); font-weight: 600;"><i class="bi bi-box-seam me-2"></i> My Orders</a>
                    <a href="profile.php" class="list-group-item list-group-item-action border-0 py-3"><i class="bi bi-person-gear me-2"></i> Account Settings</a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger border-0 py-3"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                </div>
            </div>
        </div>

        <!-- Orders View Content -->
        <div class="col-lg-9">
            <?php if ($viewing_single): ?>
                <!-- Single Order Details View -->
                <div class="bg-white p-4 border shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                        <h4 class="font-serif mb-0">Order: <?= sanitize($order['order_number']) ?></h4>
                        <a href="orders.php" class="btn btn-outline-gold btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-uppercase small text-muted mb-2">Shipping Address</h6>
                            <p class="small mb-0">
                                <strong><?= sanitize($order['shipping_name']) ?></strong><br>
                                <?= sanitize($order['shipping_address']) ?><br>
                                <?= sanitize($order['shipping_city']) ?>, <?= sanitize($order['shipping_state']) ?> - <?= sanitize($order['shipping_zip']) ?><br>
                                <span class="text-muted">Phone:</span> <?= sanitize($order['shipping_phone']) ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-uppercase small text-muted mb-2">Order Information</h6>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span>Date Placed:</span>
                                <span class="fw-semibold"><?= date('d F Y, h:i A', strtotime($order['created_at'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span>Payment Method:</span>
                                <span class="fw-semibold"><?= sanitize($order['payment_method']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span>Payment Status:</span>
                                <span class="badge bg-secondary"><?= strtoupper(sanitize($order['payment_status'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Order Status:</span>
                                <?php 
                                $badge_class = 'bg-secondary';
                                if ($order['order_status'] === 'pending') $badge_class = 'bg-warning text-dark';
                                elseif ($order['order_status'] === 'processing') $badge_class = 'bg-info text-dark';
                                elseif ($order['order_status'] === 'shipped') $badge_class = 'bg-primary';
                                elseif ($order['order_status'] === 'delivered') $badge_class = 'bg-success';
                                elseif ($order['order_status'] === 'cancelled') $badge_class = 'bg-danger';
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= strtoupper(sanitize($order['order_status'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <h5 class="font-serif mb-3">Items Ordered</h5>
                    <div class="table-responsive" data-lenis-prevent>
                        <table class="table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= BASE_URL ?>/<?= sanitize($item['image_url']) ?>" alt="" loading="lazy" decoding="async" width="50" height="50" style="object-fit: contain;" class="bg-light p-1 border">
                                                <span>
                                                    <a href="../product.php?slug=<?= sanitize($item['slug']) ?>" class="text-decoration-none text-dark fw-bold d-block">
                                                        <?= sanitize($item['name']) ?>
                                                    </a>
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
            <?php else: ?>
                <!-- All Orders List View -->
                <div class="bg-white p-4 border shadow-sm">
                    <h4 class="font-serif mb-4">Orders History</h4>
                    
                    <?php if (empty($all_orders)): ?>
                        <p class="text-muted py-3 mb-0">You have not placed any orders yet. <a href="../shop.php" class="text-gold fw-bold">Browse shop catalog</a></p>
                    <?php else: ?>
                        <div class="table-responsive" data-lenis-prevent>
                            <table class="table table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_orders as $ord): ?>
                                        <tr>
                                            <td class="fw-bold"><?= sanitize($ord['order_number']) ?></td>
                                            <td><?= date('d M Y', strtotime($ord['created_at'])) ?></td>
                                            <td class="fw-bold text-gold"><?= format_price($ord['total_amount']) ?></td>
                                            <td><span class="small"><?= sanitize($ord['payment_method']) ?></span></td>
                                            <td>
                                                <?php 
                                                $badge_class = 'bg-secondary';
                                                if ($ord['order_status'] === 'pending') $badge_class = 'bg-warning text-dark';
                                                elseif ($ord['order_status'] === 'processing') $badge_class = 'bg-info text-dark';
                                                elseif ($ord['order_status'] === 'shipped') $badge_class = 'bg-primary';
                                                elseif ($ord['order_status'] === 'delivered') $badge_class = 'bg-success';
                                                elseif ($ord['order_status'] === 'cancelled') $badge_class = 'bg-danger';
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= ucfirst(sanitize($ord['order_status'])) ?></span>
                                            </td>
                                            <td>
                                                <a href="orders.php?view=<?= sanitize($ord['order_number']) ?>" class="btn btn-outline-gold btn-sm">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
