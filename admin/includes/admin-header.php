<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enforce admin check
if (!is_admin()) {
    $_SESSION['error_msg'] = "Access denied. Admin access required.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TGD Admin Console | The Gayatri Decors</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse px-0" id="sidebarMenu">
            <div class="position-sticky pt-3">
                <div class="text-center py-4 border-bottom border-secondary mb-3">
                    <h5 class="text-gold font-serif mb-1">TGD Admin</h5>
                    <span class="badge bg-gold text-dark font-monospace small">Control Center</span>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'products.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/products.php">
                            <i class="bi bi-tag-fill me-2"></i> Products Manager
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'categories.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/categories.php">
                            <i class="bi bi-grid-fill me-2"></i> Categories & Materials
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'orders.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/orders.php">
                            <i class="bi bi-box-seam-fill me-2"></i> Orders Fulfillment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'customers.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/customers.php">
                            <i class="bi bi-people-fill me-2"></i> Customers Lookup
                        </a>
                    </li>
                    <li class="nav-item mt-4 border-top border-secondary">
                        <a class="nav-link text-white-50" href="<?= BASE_URL ?>/index.php" target="_blank">
                            <i class="bi bi-arrow-up-right-circle me-2"></i> View Front Store
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?= BASE_URL ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Panel Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <!-- Mobile Toggle Header -->
            <div class="d-md-none d-flex justify-content-between align-items-center mb-3 bg-dark text-white p-3">
                <h5 class="mb-0">TGD Control Panel</h5>
                <button class="btn btn-outline-gold" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
            </div>
