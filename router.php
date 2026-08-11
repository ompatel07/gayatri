<?php
/**
 * Router for PHP's built-in dev server:
 *
 *     php -S 127.0.0.1:8000 -t . router.php
 *
 * The built-in server ignores .htaccess completely, so without this it will
 * happily serve /.env, /database.sqlite and /config/*.php as plain text. The
 * deny rules in .htaccess only protect an Apache deployment; this reproduces
 * them for local development and for anything tunnelled to the public.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$blocked = '#(^/\.env|^/\.git|\.sqlite$|\.sql$|^/config/|^/tools/|\.log$|\.md$|^/composer\.|\.ini$)#i';

if (preg_match($blocked, $path)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "Not found";
    return true;
}

return false;   // anything else: let the server handle it normally
