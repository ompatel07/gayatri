<?php
/**
 * Cash-on-delivery order submission.
 *
 * Razorpay orders never come through here - they are written by
 * api/verify-payment.php once the signature checks out. Both paths share
 * place_cart_order() so the two can never drift apart.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: " . login_url());
    exit;
}

csrf_guard();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

if (empty(get_cart_details($pdo))) {
    header("Location: " . BASE_URL . "/cart.php");
    exit;
}

try {
    $ship = collect_shipping($_POST);

    // Anything other than COD must go through the gateway, not this endpoint.
    $method = sanitize($_POST['payment_method'] ?? 'COD');
    if ($method !== 'COD') {
        throw new Exception("Please complete online payment from the checkout page.");
    }

    $order_number = place_cart_order($pdo, $ship, 'COD', 'pending');

    header("Location: " . BASE_URL . "/order-success.php?order_number=" . urlencode($order_number));
    exit;

} catch (Exception $e) {
    $_SESSION['error_msg'] = "Order processing failed: " . $e->getMessage();
    header("Location: " . BASE_URL . "/checkout.php");
    exit;
}
