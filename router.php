<?php
/**
 * Front controller for `php -S` (local dev and tunnelled demos).
 *
 * The built-in server ignores .htaccess entirely, so the deny rules that
 * protect secrets under Apache do nothing here. Without this, .env - which
 * holds RAZORPAY_KEY_SECRET - and database.sqlite are downloadable by anyone
 * who has the URL.
 *
 * Usage:  php -S 127.0.0.1:8000 -t . router.php
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$blocked = '#^/\.env
           | ^/\.git
           | ^/config/
           | ^/tools/
           | \.sqlite(-journal|-wal|-shm)?$
           | \.sql$
           | \.ini$
           | \.log$
           | \.bak$
           #ix';

if (preg_match($blocked, $path)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "Not Found";
    return true;   // handled here; do not fall through to the file
}

return false;      // let the built-in server serve it normally
