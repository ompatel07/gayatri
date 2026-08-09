<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
check_login();
require_once __DIR__ . '/../includes/header.php';

$user_id = get_user_id();

// Fetch summary stats
$orders_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$orders_count_stmt->execute([$user_id]);
$total_orders = $orders_count_stmt->fetchColumn();

$total_spent_stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND order_status != 'cancelled'");
$total_spent_stmt->execute([$user_id]);
$total_spent = $total_spent_stmt->fetchColumn() ?: 0.00;

// Fetch last 3 orders
$recent_orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 3");
$recent_orders_stmt->execute([$user_id]);
$recent_orders = $recent_orders_stmt->fetchAll();
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3">Customer Portal</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active border-0 py-3" style="background-color: var(--primary-gold); color: var(--navy-dark); font-weight: 600;"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                    <a href="orders.php" class="list-group-item list-group-item-action border-0 py-3"><i class="bi bi-box-seam me-2"></i> My Orders</a>
                    <a href="profile.php" class="list-group-item list-group-item-action border-0 py-3"><i class="bi bi-person-gear me-2"></i> Account Settings</a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger border-0 py-3"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="col-lg-9">
            <div class="row g-3 mb-4">
                <!-- Stat Card 1 -->
                <div class="col-md-6">
                    <div class="dashboard-card text-center">
                        <i class="bi bi-box-seam fs-1 text-gold mb-2 d-block"></i>
                        <h6 class="text-muted text-uppercase small tracking-widest">Total Orders</h6>
                        <h3 class="display-6 fw-bold"><?= $total_orders ?></h3>
                    </div>
                </div>
                <!-- Stat Card 2 -->
                <div class="col-md-6">
                    <div class="dashboard-card text-center">
                        <i class="bi bi-currency-rupee fs-1 text-gold mb-2 d-block"></i>
                        <h6 class="text-muted text-uppercase small tracking-widest">Total Investment</h6>
                        <h3 class="display-6 fw-bold text-gold"><?= format_price($total_spent) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white p-4 shadow-sm border">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="font-serif mb-0">Recent Orders</h4>
                    <a href="orders.php" class="btn btn-outline-gold btn-sm">View All</a>
                </div>
                
                <?php if (empty($recent_orders)): ?>
                    <p class="text-muted py-3 mb-0">You have not placed any orders yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $ord): ?>
                                    <tr>
                                        <td class="fw-bold"><?= sanitize($ord['order_number']) ?></td>
                                        <td><?= date('d M Y', strtotime($ord['created_at'])) ?></td>
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
                                            <span class="badge <?= $badge_class ?>"><?= ucfirst(sanitize($ord['order_status'])) ?></span>
                                        </td>
                                        <td>
                                            <a href="orders.php?view=<?= sanitize($ord['order_number']) ?>" class="btn btn-outline-gold btn-sm">Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
