<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (ob_get_level() === 0) {
    ob_start();
}

// Security: Sanitize input data
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Authentication Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Absolute targets: a relative "login.php" resolves to /customer/login.php or
// /admin/login.php when these are called from a subdirectory, which 404s.
function login_url($redirect = '') {
    $base = defined('BASE_URL') ? BASE_URL : '';
    return $base . '/login.php' . ($redirect ? '?redirect=' . urlencode($redirect) : '');
}

function check_admin() {
    if (!is_admin()) {
        header("Location: " . login_url());
        exit;
    }
}

function check_login() {
    if (!is_logged_in()) {
        header("Location: " . login_url());
        exit;
    }
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_user_name() {
    return $_SESSION['user_name'] ?? 'Guest';
}

// Format Price
function format_price($price) {
    return '₹' . number_format($price, 2);
}

// ---------------------------------------------------------------------------
// Responsive images
//
// build_images generates WebP variants at 400/800/1200/1600 into
// assets/images/products/responsive/ named "{filename-stem}-{width}.webp".
// responsive_img() emits a srcset over whichever widths actually exist and
// falls back to the original file, so a product with no variants still renders.
// ---------------------------------------------------------------------------

const IMG_WIDTHS = [400, 800, 1200, 1600];

// Rendered width of each image slot, so the browser downloads the right variant
// instead of always taking the largest. Mirrors the Bootstrap grid in each view.
const SIZES_CARD      = '(max-width: 576px) 92vw, (max-width: 992px) 46vw, 30vw'; // shop grid
const SIZES_FEATURED  = '(max-width: 576px) 92vw, (max-width: 992px) 46vw, 23vw'; // home 4-up
const SIZES_CATEGORY  = '(max-width: 576px) 92vw, (max-width: 992px) 46vw, 31vw'; // home 3-up
const SIZES_DETAIL    = '(max-width: 992px) 94vw, 46vw';                          // product hero
const SIZES_GALLERY   = '120px';
const SIZES_CART      = '80px';
const SIZES_CHECKOUT  = '50px';

function img_variants($url) {
    static $cache = [];
    $stem = pathinfo($url, PATHINFO_FILENAME);
    if (isset($cache[$stem])) {
        return $cache[$stem];
    }
    // Discovered by glob rather than assumed: the generator also emits a variant
    // at the source's native width, which is not one of the ladder values.
    $dir = __DIR__ . '/../assets/images/products/responsive/';
    $widths = [];
    foreach (glob($dir . $stem . '-*.webp') ?: [] as $file) {
        if (preg_match('/-(\d+)\.webp$/', $file, $m)) {
            $widths[] = (int)$m[1];
        }
    }
    sort($widths);
    return $cache[$stem] = ['stem' => $stem, 'widths' => $widths];
}

// srcset string on its own, for the gallery thumbnails that swap the hero image.
function img_srcset($url) {
    $v = img_variants(ltrim((string)$url, '/'));
    $set = [];
    foreach ($v['widths'] as $w) {
        $set[] = BASE_URL . '/assets/images/products/responsive/' . $v['stem'] . '-' . $w . '.webp ' . $w . 'w';
    }
    return implode(', ', $set);
}

/**
 * $sizes  CSS "sizes" describing the rendered width at each breakpoint.
 * $opts   lazy (default true), id, class, style, width, height, alt fallback.
 */
function responsive_img($url, $alt = '', $sizes = '100vw', $opts = []) {
    $url = ltrim((string)$url, '/');
    $v = img_variants($url);
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

    $html = '<img src="' . $e(BASE_URL . '/' . $url) . '"';

    if (!empty($v['widths'])) {
        $set = [];
        foreach ($v['widths'] as $w) {
            $set[] = BASE_URL . '/assets/images/products/responsive/' . $v['stem'] . '-' . $w . '.webp ' . $w . 'w';
        }
        $html .= ' srcset="' . $e(implode(', ', $set)) . '"';
        $html .= ' sizes="' . $e($sizes) . '"';
    }

    $html .= ' alt="' . $e($alt) . '"';
    foreach (['id', 'class', 'style', 'width', 'height'] as $k) {
        if (!empty($opts[$k])) {
            $html .= ' ' . $k . '="' . $e($opts[$k]) . '"';
        }
    }
    // The product-detail hero is the LCP element, so it opts out of lazy loading.
    $lazy = $opts['lazy'] ?? true;
    $html .= $lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high" decoding="async"';

    return $html . '>';
}

// ---------------------------------------------------------------------------
// Product variants (size + LED), priced from the client's price list.
// Only listed products have rows; everything else falls back to products.price.
// ---------------------------------------------------------------------------

function get_variants($pdo, $product_id) {
    static $cache = [];
    $product_id = (int)$product_id;
    if (isset($cache[$product_id])) {
        return $cache[$product_id];
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?
                               ORDER BY sort_order, price");
        $stmt->execute([$product_id]);
        $rows = $stmt->fetchAll() ?: [];
    } catch (Exception $e) {
        $rows = []; // table absent (e.g. a MySQL DB that has not been migrated)
    }
    return $cache[$product_id] = $rows;
}

// One query for the whole page: which products need an option chosen before
// they can be added to the cart.
function products_with_variants($pdo) {
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }
    try {
        $rows = $pdo->query("SELECT DISTINCT product_id FROM product_variants")->fetchAll();
        $ids = [];
        foreach ($rows as $r) {
            $ids[(int)$r['product_id']] = true;
        }
    } catch (Exception $e) {
        $ids = [];
    }
    return $ids;
}

function has_variants($pdo, $product_id) {
    return isset(products_with_variants($pdo)[(int)$product_id]);
}

function get_variant($pdo, $variant_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE id = ?");
        $stmt->execute([(int)$variant_id]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

// "13x13 in - With LED" where an option applies, plain "12x12 in" where the
// product varies by size only (wall clocks, tabletop).
// Every photo for a product, in insertion order. Empty for products that were
// never given a gallery, in which case the caller falls back to main + hover.
function get_gallery($pdo, $product_id) {
    try {
        $stmt = $pdo->prepare("SELECT image_url FROM product_gallery
                               WHERE product_id = ? ORDER BY id");
        $stmt->execute([(int)$product_id]);
        return array_map(fn($r) => $r['image_url'], $stmt->fetchAll());
    } catch (Exception $e) {
        return [];
    }
}

function variant_label($v) {
    if (!$v) {
        return '';
    }
    $label = $v['size_label'] . ' in';
    if (!empty($v['option_label'])) {
        $label .= ' - ' . $v['option_label'];
    }
    return $label;
}

// ---------------------------------------------------------------------------
// Cart (session-based, variant aware)
//
// $_SESSION['cart'][$key] = ['product_id'=>int, 'variant_id'=>?int, 'qty'=>int]
// $key is "p{id}" or "p{id}v{variant}", so one product can sit in the cart more
// than once at different size / LED combinations.
// ---------------------------------------------------------------------------

function cart_key($product_id, $variant_id = null) {
    return 'p' . (int)$product_id . ($variant_id ? 'v' . (int)$variant_id : '');
}

function init_cart() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
        return;
    }
    // Upgrade carts stored before variants existed: [product_id => qty]
    foreach ($_SESSION['cart'] as $k => $line) {
        if (!is_array($line)) {
            unset($_SESSION['cart'][$k]);
            $_SESSION['cart'][cart_key($k)] = [
                'product_id' => (int)$k,
                'variant_id' => null,
                'qty'        => max(1, (int)$line),
            ];
        }
    }
}

function get_cart() {
    init_cart();
    return $_SESSION['cart'];
}

function add_to_cart($product_id, $qty = 1, $variant_id = null) {
    init_cart();
    $qty = max(1, (int)$qty);
    $key = cart_key($product_id, $variant_id);

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => (int)$product_id,
            'variant_id' => $variant_id ? (int)$variant_id : null,
            'qty'        => $qty,
        ];
    }
}

function update_cart($key, $qty) {
    init_cart();
    if (!isset($_SESSION['cart'][$key])) {
        return;
    }
    $qty = (int)$qty;
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } else {
        $_SESSION['cart'][$key]['qty'] = $qty;
    }
}

function remove_from_cart($key) {
    init_cart();
    unset($_SESSION['cart'][$key]);
}

function clear_cart() {
    $_SESSION['cart'] = [];
}

function get_cart_count() {
    init_cart();
    $count = 0;
    foreach ($_SESSION['cart'] as $line) {
        $count += (int)$line['qty'];
    }
    return $count;
}

// Get detailed cart items from database. Result is memoised against the cart
// contents so calling this and get_cart_total() on one page is a single query.
function get_cart_details($pdo) {
    init_cart();
    static $cache = [];

    $cart = $_SESSION['cart'];
    if (empty($cart)) {
        return [];
    }
    $sig = md5(serialize($cart));
    if (isset($cache[$sig])) {
        return $cache[$sig];
    }

    $product_ids = array_values(array_unique(array_map(
        fn($line) => (int)$line['product_id'], $cart)));
    $ph = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                           FROM products p
                           LEFT JOIN categories c ON p.category_id = c.id
                           WHERE p.id IN ($ph)");
    $stmt->execute($product_ids);
    $products = [];
    foreach ($stmt->fetchAll() as $p) {
        $products[$p['id']] = $p;
    }

    $variant_ids = array_values(array_filter(array_map(
        fn($line) => $line['variant_id'] ?? null, $cart)));
    $variants = [];
    if ($variant_ids) {
        try {
            $ph2 = implode(',', array_fill(0, count($variant_ids), '?'));
            $vst = $pdo->prepare("SELECT * FROM product_variants WHERE id IN ($ph2)");
            $vst->execute($variant_ids);
            foreach ($vst->fetchAll() as $v) {
                $variants[$v['id']] = $v;
            }
        } catch (Exception $e) {
            $variants = [];
        }
    }

    $details = [];
    foreach ($cart as $key => $line) {
        $pid = (int)$line['product_id'];
        if (!isset($products[$pid])) {
            continue; // product deleted since it was added
        }
        $item = $products[$pid];
        $qty  = max(1, (int)$line['qty']);
        $vid  = $line['variant_id'] ?? null;

        if ($vid && isset($variants[$vid])) {
            $v = $variants[$vid];
            $price = (float)$v['price'];
            $item['stock']         = (int)$v['stock'];
            $item['variant_id']    = (int)$v['id'];
            $item['variant_label'] = variant_label($v);
        } else {
            $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
            $item['variant_id']    = null;
            $item['variant_label'] = '';
        }

        $item['cart_key']   = $key;
        $item['quantity']   = $qty;
        $item['cart_price'] = $price;
        $item['subtotal']   = $price * $qty;

        $details[] = $item;
    }

    return $cache[$sig] = $details;
}

function get_cart_total($pdo) {
    $total = 0;
    foreach (get_cart_details($pdo) as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

// Cheapest variant per product in one query, for "from Rs X" on listing pages.
function variant_min_prices($pdo) {
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = [];
    try {
        $rows = $pdo->query("SELECT product_id, MIN(price) AS min_price
                             FROM product_variants GROUP BY product_id")->fetchAll();
        foreach ($rows as $r) {
            $map[(int)$r['product_id']] = (float)$r['min_price'];
        }
    } catch (Exception $e) {
        $map = [];
    }
    return $map;
}

function product_from_price($pdo, $product_id) {
    return variant_min_prices($pdo)[(int)$product_id] ?? null;
}
?>
