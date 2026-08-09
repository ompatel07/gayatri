<?php
require_once __DIR__ . '/includes/admin-header.php';

// Fetch stats
$revenue_stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE order_status != 'cancelled'");
$total_revenue = $revenue_stmt->fetchColumn() ?: 0.00;

$orders_stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $orders_stmt->fetchColumn();

$products_stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $products_stmt->fetchColumn();

$customers_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$total_customers = $customers_stmt->fetchColumn();

// Fetch 5 recent pending orders
$pending_orders_stmt = $pdo->query("SELECT o.*, u.name as customer_name 
                                    FROM orders o 
                                    LEFT JOIN users u ON o.user_id = u.id 
                                    WHERE o.order_status = 'pending' 
                                    ORDER BY o.id DESC 
                                    LIMIT 5");
$pending_orders = $pending_orders_stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2 class="font-serif">Console Dashboard</h2>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-gold btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Product</a>
    </div>
</div>

<!-- Admin Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="dashboard-card text-center bg-white border border-secondary border-opacity-20">
            <i class="bi bi-currency-rupee fs-2 text-warning mb-2 d-block"></i>
            <h6 class="text-muted text-uppercase small">Total Revenue</h6>
            <h4 class="fw-bold text-gold"><?= format_price($total_revenue) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card text-center bg-white border border-secondary border-opacity-20">
            <i class="bi bi-box-seam fs-2 text-warning mb-2 d-block"></i>
            <h6 class="text-muted text-uppercase small">Total Orders</h6>
            <h4 class="fw-bold"><?= $total_orders ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card text-center bg-white border border-secondary border-opacity-20">
            <i class="bi bi-tag fs-2 text-warning mb-2 d-block"></i>
            <h6 class="text-muted text-uppercase small">Active Products</h6>
            <h4 class="fw-bold"><?= $total_products ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card text-center bg-white border border-secondary border-opacity-20">
            <i class="bi bi-people fs-2 text-warning mb-2 d-block"></i>
            <h6 class="text-muted text-uppercase small">Customers</h6>
            <h4 class="fw-bold"><?= $total_customers ?></h4>
        </div>
    </div>
</div>

<!-- Recent Pending Orders Section -->
<div class="bg-white p-4 border shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="font-serif mb-0">Pending Orders Fulfillment</h4>
        <a href="orders.php" class="btn btn-outline-gold btn-sm">Manage All</a>
    </div>

    <?php if (empty($pending_orders)): ?>
        <p class="text-muted mb-0 py-3">Great job! There are no pending orders left to process.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date Placed</th>
                        <th>Grand Total</th>
                        <th>Method</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_orders as $ord): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= sanitize($ord['order_number']) ?></td>
                            <td><?= sanitize($ord['customer_name']) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?></td>
                            <td class="fw-bold text-gold"><?= format_price($ord['total_amount']) ?></td>
                            <td><span class="small badge bg-secondary"><?= sanitize($ord['payment_method']) ?></span></td>
                            <td>
                                <a href="orders.php?view=<?= sanitize($ord['order_number']) ?>" class="btn btn-outline-gold btn-sm"><i class="bi bi-pencil-square"></i> Fulfill</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
