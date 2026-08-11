<?php
/**
 * Minimal .env reader.
 *
 * This project has no Composer, so there is no vlucas/phpdotenv. Real
 * environment variables always win, so a host that sets RAZORPAY_KEY_SECRET
 * properly never needs the file on disk.
 */
function tgd_env($key, $default = null) {
    static $vars = null;

    if ($vars === null) {
        $vars = [];
        $file = __DIR__ . '/../.env';
        if (is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                $pos = strpos($line, '=');
                if ($pos === false) {
                    continue;
                }
                $k = trim(substr($line, 0, $pos));
                $v = trim(substr($line, $pos + 1));
                // strip matching surrounding quotes
                if (strlen($v) > 1 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                    $v = substr($v, 1, -1);
                }
                $vars[$k] = $v;
            }
        }
    }

    $fromEnv = getenv($key);
    if ($fromEnv !== false && $fromEnv !== '') {
        return $fromEnv;
    }
    return array_key_exists($key, $vars) ? $vars[$key] : $default;
}

/** Publishable. Safe to render into the page. */
function razorpay_key_id() {
    return (string)tgd_env('RAZORPAY_KEY_ID', '');
}

/** Server-side only. Must never be echoed into HTML or JSON. */
function razorpay_key_secret() {
    return (string)tgd_env('RAZORPAY_KEY_SECRET', '');
}

/** Online payment is offered only when both halves of the key pair exist. */
function razorpay_enabled() {
    return razorpay_key_id() !== '' && razorpay_key_secret() !== '';
}

/**
 * Call the Razorpay REST API with HTTP Basic auth.
 * Returns [httpStatus, decodedBody].
 */
function razorpay_api($method, $path, array $payload = null) {
    $ch = curl_init('https://api.razorpay.com/v1' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_USERPWD        => razorpay_key_id() . ':' . razorpay_key_secret(),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body   = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return [0, ['error' => ['description' => $err ?: 'Network error contacting Razorpay']]];
    }
    return [$status, json_decode($body, true) ?: []];
}

/**
 * Razorpay signs "<order_id>|<payment_id>" with the key secret.
 * hash_equals keeps the comparison constant-time.
 */
function razorpay_signature_valid($order_id, $payment_id, $signature) {
    if ($order_id === '' || $payment_id === '' || $signature === '') {
        return false;
    }
    $expected = hash_hmac('sha256', $order_id . '|' . $payment_id, razorpay_key_secret());
    return hash_equals($expected, (string)$signature);
}
