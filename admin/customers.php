<?php
require_once __DIR__ . '/includes/admin-header.php';

// Query customers list
$customers_stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC");
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2 class="font-serif">Customer Lookup Directory</h2>
</div>

<div class="bg-white p-4 border shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone Number</th>
                    <th>Shipping Destination</th>
                    <th>Date Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No customers have registered accounts yet.</td>
                    </tr>
                <?php else: foreach ($customers as $cust): ?>
                    <tr>
                        <td><span class="font-monospace text-muted">#<?= $cust['id'] ?></span></td>
                        <td class="fw-bold"><?= sanitize($cust['name']) ?></td>
                        <td><?= sanitize($cust['email']) ?></td>
                        <td><?= sanitize($cust['phone'] ?? 'N/A') ?></td>
                        <td class="small">
                            <?php if (!empty($cust['address'])): ?>
                                <?= sanitize($cust['address']) ?>, <?= sanitize($cust['city']) ?>, <?= sanitize($cust['state']) ?> - <?= sanitize($cust['zip']) ?>
                            <?php else: ?>
                                <span class="text-muted">Not specified</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($cust['created_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
