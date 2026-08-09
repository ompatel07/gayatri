<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: customer/dashboard.php");
    }
    exit;
}

$error = '';
$redirect = isset($_GET['redirect']) ? sanitize($_GET['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password']; // Do not sanitize password as it can contain special characters
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Query user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Log in user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect logic
            if (!empty($redirect)) {
                header("Location: " . $redirect);
            } elseif ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: customer/dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}

// Load navigation header template after processing redirects
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3">Account Login</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Login</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5" style="max-width: 480px;">
    <div class="card p-4 border shadow-sm bg-white" style="border-radius: 0;">
        <h3 class="font-serif text-center mb-4">Login to Your Account</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="border-radius: 0;"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success" style="border-radius: 0;">
                <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger" style="border-radius: 0;">
                <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <input type="email" class="form-control border-secondary" id="email" name="email" required style="border-radius:0;">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password" class="form-control border-secondary" id="password" name="password" required style="border-radius:0;">
            </div>
            
            <button type="submit" class="btn btn-gold w-100 py-2 mb-3">Sign In</button>
        </form>
        
        <div class="text-center">
            <p class="small text-muted mb-0">Don't have an account yet? <a href="register.php<?= !empty($redirect) ? '?redirect=' . $redirect : '' ?>" class="text-gold text-decoration-none fw-bold">Register here</a></p>
            <?php // Admin credentials are deliberately not printed here - this page is publicly reachable. ?>
            <p class="small text-muted mt-2 mb-0">Demo login: <span class="fw-bold">customer@tgd.com / customer123</span></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
