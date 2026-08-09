<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$cart_items = get_cart_details($pdo);
$cart_total = get_cart_total($pdo);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect shipping details
    $shipping_name = sanitize($_POST['shipping_name']);
    $shipping_phone = sanitize($_POST['shipping_phone']);
    $shipping_address = sanitize($_POST['shipping_address']);
    $shipping_city = sanitize($_POST['shipping_city']);
    $shipping_state = sanitize($_POST['shipping_state']);
    $shipping_zip = sanitize($_POST['shipping_zip']);
    $payment_method = sanitize($_POST['payment_method']);
    
    if (empty($shipping_name) || empty($shipping_phone) || empty($shipping_address) || empty($shipping_city) || empty($shipping_state) || empty($shipping_zip)) {
        $_SESSION['error_msg'] = "Please fill in all shipping fields.";
        header("Location: checkout.php");
        exit;
    }
    
    // Calculate final amount
    $shipping_charge = ($cart_total < 1999) ? 150.00 : 0.00;
    $grand_total = $cart_total + $shipping_charge;
    
    // Generate Order Number
    $order_number = "TGD-" . date('Ymd') . "-" . rand(1000, 9999);
    $user_id = get_user_id();
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Insert order
        $order_sql = "INSERT INTO orders (user_id, order_number, total_amount, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_zip, payment_method, payment_status, order_status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')";
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([
            $user_id,
            $order_number,
            $grand_total,
            $shipping_name,
            $shipping_phone,
            $shipping_address,
            $shipping_city,
            $shipping_state,
            $shipping_zip,
            $payment_method
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // 2. Insert items and decrement stock
        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price, variant_id, variant_label)
                     VALUES (?, ?, ?, ?, ?, ?)";
        $item_stmt = $pdo->prepare($item_sql);

        // Variant-priced lines draw down the variant's own stock; everything
        // else still draws down products.stock.
        $stock_stmt   = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $variant_stmt = $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($cart_items as $item) {
            // Verify stock once more
            if ($item['stock'] < $item['quantity']) {
                throw new Exception("Product " . $item['name'] . " does not have enough stock available.");
            }

            $item_stmt->execute([
                $order_id,
                $item['id'],
                $item['quantity'],
                $item['cart_price'],
                $item['variant_id'] ?? null,
                $item['variant_label'] ?? null
            ]);

            if (!empty($item['variant_id'])) {
                $variant_stmt->execute([$item['quantity'], $item['variant_id'], $item['quantity']]);
                $affected = $variant_stmt->rowCount();
            } else {
                $stock_stmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
                $affected = $stock_stmt->rowCount();
            }

            // The guarded UPDATE matches no row if someone else took the stock
            // between the check above and here.
            if ($affected < 1) {
                throw new Exception("Stock for " . $item['name'] . " ran out while placing your order.");
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Clear cart session
        clear_cart();
        
        // Redirect to success page
        header("Location: order-success.php?order_number=" . $order_number);
        exit;
        
    } catch (Exception $e) {
        // Only roll back if a transaction is actually open, otherwise this
        // throws a second fatal that hides the real error.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = "Order processing failed: " . $e->getMessage();
        header("Location: checkout.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
