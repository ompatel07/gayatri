<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$redirect = isset($_GET['redirect']) ? sanitize($_GET['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $state = sanitize($_POST['state']);
    $zip = sanitize($_POST['zip']);
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            $error = "Email address is already registered.";
        } else {
            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $ins_stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, city, state, zip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            try {
                $ins_stmt->execute([
                    $name,
                    $email,
                    $hashed_password,
                    $phone,
                    $address,
                    $city,
                    $state,
                    $zip
                ]);
                
                $_SESSION['success_msg'] = "Registration successful! You can now log in.";
                header("Location: login.php" . (!empty($redirect) ? "?redirect=" . $redirect : ""));
                exit;
            } catch (Exception $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}

// Load navigation header template after processing redirects
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3">Register Account</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Register</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5" style="max-width: 650px;">
    <div class="card p-4 border shadow-sm bg-white" style="border-radius: 0;">
        <h3 class="font-serif text-center mb-4">Create An Account</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="border-radius: 0;"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <h5 class="font-serif mb-3 border-bottom pb-2">Personal Credentials</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">Full Name *</label>
                    <input type="text" class="form-control border-secondary" id="name" name="name" required style="border-radius:0;">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Email Address *</label>
                    <input type="email" class="form-control border-secondary" id="email" name="email" required style="border-radius:0;">
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label fw-semibold">Password *</label>
                    <input type="password" class="form-control border-secondary" id="password" name="password" required style="border-radius:0;">
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
                    <input type="password" class="form-control border-secondary" id="confirm_password" name="confirm_password" required style="border-radius:0;">
                </div>
            </div>

            <h5 class="font-serif mb-3 border-bottom pb-2">Default Shipping Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="phone" class="form-label fw-semibold">Phone Number</label>
                    <input type="tel" class="form-control border-secondary" id="phone" name="phone" style="border-radius:0;">
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label fw-semibold">Street Address</label>
                    <input type="text" class="form-control border-secondary" id="address" name="address" style="border-radius:0;">
                </div>
                <div class="col-md-4">
                    <label for="city" class="form-label fw-semibold">City</label>
                    <input type="text" class="form-control border-secondary" id="city" name="city" style="border-radius:0;">
                </div>
                <div class="col-md-4">
                    <label for="state" class="form-label fw-semibold">State</label>
                    <input type="text" class="form-control border-secondary" id="state" name="state" style="border-radius:0;">
                </div>
                <div class="col-md-4">
                    <label for="zip" class="form-label fw-semibold">ZIP Code</label>
                    <input type="text" class="form-control border-secondary" id="zip" name="zip" style="border-radius:0;">
                </div>
            </div>
            
            <button type="submit" class="btn btn-gold w-100 py-2 mb-3">Sign Up</button>
        </form>
        
        <div class="text-center">
            <p class="small text-muted mb-0">Already have an account? <a href="login.php<?= !empty($redirect) ? '?redirect=' . $redirect : '' ?>" class="text-gold text-decoration-none fw-bold">Login here</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
