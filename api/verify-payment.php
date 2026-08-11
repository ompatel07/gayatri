<?php
/**
 * POST /api/verify-payment.php
 *
 * Verifies the Razorpay signature and, only if it matches, writes the order.
 * A mismatch returns 400 and nothing is marked paid.
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
if (!is_logged_in()) {
    reply(401, ['error' => 'Please sign in to continue.']);
}

$payment_id = trim($_POST['razorpay_payment_id'] ?? '');
$order_id   = trim($_POST['razorpay_order_id'] ?? '');
$signature  = trim($_POST['razorpay_signature'] ?? '');

if ($payment_id === '' || $order_id === '' || $signature === '') {
    reply(400, ['error' => 'Missing payment details.']);
}

// The order must be the one this session started, otherwise a valid signature
// from some other (cheaper) order could be replayed here.
$pending = $_SESSION['rzp_pending'] ?? null;
if (!$pending || !hash_equals((string)$pending['order_id'], $order_id)) {
    reply(400, ['error' => 'This payment does not match your checkout session.']);
}

if (!razorpay_signature_valid($order_id, $payment_id, $signature)) {
    error_log('Razorpay signature mismatch for order ' . $order_id);
    reply(400, ['error' => 'Payment could not be verified.']);
}

// Signature is good, but never take the client's word that money moved. Ask
// Razorpay directly whether this payment exists, is captured, belongs to this
// order, and is for the exact amount we asked for.
list($status, $payment) = razorpay_api('GET', '/payments/' . rawurlencode($payment_id));

if ($status === 0) {
    // Transport failure - we genuinely do not know. Record the order as pending
    // for manual confirmation rather than declaring it paid on a guess.
    $payment_status = 'pending';
    $unconfirmed    = true;
    error_log('Razorpay unreachable while confirming payment ' . $payment_id
              . ' - order recorded as pending');
} elseif ($status >= 200 && $status < 300 && !empty($payment['id'])) {
    $confirmed = in_array(($payment['status'] ?? ''), ['captured', 'authorized'], true)
                 && (int)($payment['amount'] ?? 0) === (int)$pending['amount']
                 && hash_equals($order_id, (string)($payment['order_id'] ?? ''));
    if (!$confirmed) {
        error_log('Razorpay payment ' . $payment_id . ' failed confirmation: status='
                  . ($payment['status'] ?? '?') . ' amount=' . ($payment['amount'] ?? '?')
                  . ' expected=' . $pending['amount']);
        reply(400, ['error' => 'Payment was not completed successfully.']);
    }
    $payment_status = 'paid';
    $unconfirmed    = false;
} else {
    // Razorpay answered and does not recognise this payment.
    error_log('Razorpay does not recognise payment ' . $payment_id . ' (HTTP ' . $status . ')');
    reply(400, ['error' => 'Payment could not be confirmed.']);
}

try {
    $order_number = place_cart_order(
        $pdo,
        $pending['shipping'],
        'Razorpay',
        $payment_status,
        ['payment_id' => $payment_id, 'order_id' => $order_id]
    );
} catch (Exception $e) {
    // Money was taken but the order could not be written - never silently drop this.
    error_log('PAID BUT ORDER FAILED: payment=' . $payment_id . ' order=' . $order_id
              . ' user=' . get_user_id() . ' :: ' . $e->getMessage());
    reply(500, [
        'error' => 'Your payment succeeded but we could not finalise the order. '
                 . 'Please contact us quoting payment ID ' . $payment_id . '.',
        'payment_id' => $payment_id,
    ]);
}

unset($_SESSION['rzp_pending']);

reply(200, [
    'success'      => true,
    'order_number' => $order_number,
    'unconfirmed'  => $unconfirmed,
    'redirect'     => BASE_URL . '/order-success.php?order_number=' . urlencode($order_number),
]);
