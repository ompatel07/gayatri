<?php
/**
 * POST /api/create-order.php
 *
 * Creates a Razorpay order for the signed-in user's current cart.
 *
 * The amount is computed on the server from the cart - never taken from the
 * request - so a tampered client cannot pay one rupee for a ten thousand
 * rupee basket. The shipping block and the expected amount are stashed in the
 * session and re-checked at verification time.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function reply($status, array $body) {
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, ['error' => 'Method not allowed']);
}
csrf_guard(true);

if (!is_logged_in()) {
    reply(401, ['error' => 'Please sign in to continue.']);
}
if (!razorpay_enabled()) {
    reply(503, ['error' => 'Online payment is not configured.']);
}

try {
    $ship = collect_shipping($_POST);
} catch (Exception $e) {
    reply(400, ['error' => $e->getMessage()]);
}

$cart = get_cart_details($pdo);
if (empty($cart)) {
    reply(400, ['error' => 'Your cart is empty.']);
}

$totals = cart_totals($pdo);
$paise  = (int)round($totals['grand'] * 100);
if ($paise < 100) {
    reply(400, ['error' => 'Order total must be at least Rs 1.']);
}

// Receipt must be <= 40 chars for Razorpay
$receipt = 'tgd_' . get_user_id() . '_' . time();

list($status, $body) = razorpay_api('POST', '/orders', [
    'amount'          => $paise,
    'currency'        => 'INR',
    'receipt'         => substr($receipt, 0, 40),
    'payment_capture' => 1,
    'notes'           => [
        'user_id'    => (string)get_user_id(),
        'cart_items' => (string)count($cart),
    ],
]);

if ($status === 401) {
    error_log('Razorpay auth failed - check RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET');
    reply(401, ['error' => 'Payment gateway authentication failed.']);
}
if ($status < 200 || $status >= 300 || empty($body['id'])) {
    $desc = $body['error']['description'] ?? 'Could not reach the payment gateway.';
    error_log('Razorpay order create failed (' . $status . '): ' . $desc);
    reply(500, ['error' => $desc]);
}

// Remember what we expect back, so verification cannot be replayed with a
// cheaper order created elsewhere.
$_SESSION['rzp_pending'] = [
    'order_id' => $body['id'],
    'amount'   => $paise,
    'shipping' => $ship,
    'created'  => time(),
];

$user_name = get_user_name();
$user_mail = $_SESSION['user_email'] ?? '';

reply(200, [
    'order_id' => $body['id'],
    'amount'   => $paise,
    'currency' => $body['currency'] ?? 'INR',
    'key_id'   => razorpay_key_id(),   // publishable half only
    'name'     => 'The Gayatri Decors',
    'prefill'  => [
        'name'    => $user_name,
        'email'   => $user_mail,
        'contact' => $ship['phone'],
    ],
]);
