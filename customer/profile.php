<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
check_login();
require_once __DIR__ . '/../includes/header.php';

$user_id = get_user_id();
$success = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $state = sanitize($_POST['state']);
    $zip = sanitize($_POST['zip']);
    
    if (empty($name)) {
        $error = "Name is a required field.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, state = ?, zip = ? WHERE id = ?");
        try {
            $stmt->execute([$name, $phone, $address, $city, $state, $zip, $user_id]);
            $_SESSION['user_name'] = $name; // Sync session name
            $success = "Profile details updated successfully!";
        } catch (Exception $e) {
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $curr_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];
    
    if (empty($curr_pass) || empty($new_pass) || empty($conf_pass)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_pass !== $conf_pass) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        $pass_stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $pass_stmt->execute([$user_id]);
        $db_pass = $pass_stmt->fetchColumn();
        
        if (password_verify($curr_pass, $db_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_pass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_pass->execute([$hashed, $user_id]);
            $success = "Password changed successfully!";
        } else {
            $error = "Incorrect current password.";
        }
    }
}

// Fetch current user details
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3">Profile Settings</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Account Settings</li>
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
                    <a href="orders.php" class="list-group-item list-group-item-action border-0 py-3"><i class="bi bi-box-seam me-2"></i> My Orders</a>
                    <a href="profile.php" class="list-group-item list-group-item-action active border-0 py-3" style="background-color: var(--primary-gold); color: var(--navy-dark); font-weight: 600;"><i class="bi bi-person-gear me-2"></i> Account Settings</a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger border-0 py-3"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                </div>
            </div>
        </div>

        <!-- Profile Forms Content -->
        <div class="col-lg-9">
            <?php if ($success): ?>
                <div class="alert alert-success" style="border-radius: 0;"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius: 0;"><?= $error ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Update Details -->
                <div class="col-md-7">
                    <div class="bg-white p-4 border shadow-sm">
                        <h4 class="font-serif mb-4 pb-2 border-bottom">Profile Information</h4>
                        
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Full Name *</label>
                                <input type="text" class="form-control border-secondary" id="name" name="name" value="<?= sanitize($user['name']) ?>" required style="border-radius:0;">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control border-secondary" id="email" value="<?= sanitize($user['email']) ?>" readonly style="border-radius:0; background-color: var(--light-gray);">
                                <small class="text-muted">Email address cannot be changed.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" class="form-control border-secondary" id="phone" name="phone" value="<?= sanitize($user['phone'] ?? '') ?>" style="border-radius:0;">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label fw-semibold">Street Address</label>
                                <input type="text" class="form-control border-secondary" id="address" name="address" value="<?= sanitize($user['address'] ?? '') ?>" style="border-radius:0;">
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-4">
                                    <label for="city" class="form-label fw-semibold">City</label>
                                    <input type="text" class="form-control border-secondary" id="city" name="city" value="<?= sanitize($user['city'] ?? '') ?>" style="border-radius:0;">
                                </div>
                                <div class="col-4">
                                    <label for="state" class="form-label fw-semibold">State</label>
                                    <input type="text" class="form-control border-secondary" id="state" name="state" value="<?= sanitize($user['state'] ?? '') ?>" style="border-radius:0;">
                                </div>
                                <div class="col-4">
                                    <label for="zip" class="form-label fw-semibold">ZIP Code</label>
                                    <input type="text" class="form-control border-secondary" id="zip" name="zip" value="<?= sanitize($user['zip'] ?? '') ?>" style="border-radius:0;">
                                </div>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-gold w-100 py-2">Save Details</button>
                        </form>
                    </div>
                </div>

                <!-- Update Password -->
                <div class="col-md-5">
                    <div class="bg-white p-4 border shadow-sm">
                        <h4 class="font-serif mb-4 pb-2 border-bottom">Security</h4>
                        
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label fw-semibold">Current Password</label>
                                <input type="password" class="form-control border-secondary" id="current_password" name="current_password" required style="border-radius:0;">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label fw-semibold">New Password</label>
                                <input type="password" class="form-control border-secondary" id="new_password" name="new_password" required style="border-radius:0;">
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" class="form-control border-secondary" id="confirm_password" name="confirm_password" required style="border-radius:0;">
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-outline-gold w-100 py-2">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
