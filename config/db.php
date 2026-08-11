<?php
// .env is read here so DB_* and RAZORPAY_* are available everywhere.
require_once __DIR__ . '/env.php';

// Database configuration
define('DB_HOST', tgd_env('DB_HOST') ?: 'localhost');
define('DB_USER', tgd_env('DB_USER') ?: 'root');
define('DB_PASS', tgd_env('DB_PASS') ?: '');
define('DB_NAME', tgd_env('DB_NAME') ?: 'gayatri_db');

// Dynamically determine BASE_URL as a full absolute URL so assets/images
// work correctly on localhost, ngrok, serveo, or any tunnel/production domain.
if (!defined('BASE_URL')) {
    // Detect protocol (handle reverse proxies like ngrok/serveo which forward HTTPS)
    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
    ) {
        $scheme = 'https';
    }

    // Detect host (use forwarded host from reverse proxy if available)
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Take only the first host if multiple are forwarded
    $host = trim(explode(',', $host)[0]);

    // Detect subfolder path (/gayatri if running locally in subfolder)
    $subPath = '';
    $scriptName = $_SERVER['PHP_SELF'] ?? '';
    $requestUri  = $_SERVER['REQUEST_URI'] ?? '';
    if (
        strpos($scriptName, '/gayatri/') === 0 ||
        strpos($requestUri, '/gayatri/') === 0 ||
        $requestUri === '/gayatri'
    ) {
        $subPath = '/gayatri';
    }

    define('BASE_URL', $scheme . '://' . $host . $subPath);
}

$pdo = null;

/**
 * Is a MySQL server actually listening?
 *
 * Without this, every page issued a blind PDO connect to localhost:3306. When
 * MySQL is not running that call blocks on a TCP timeout before the SQLite
 * fallback is reached - roughly four seconds, on every single request. A short
 * socket probe makes the "no MySQL here" case cost about a millisecond, and
 * costs nothing when MySQL *is* running.
 *
 * Set DB_DRIVER=sqlite in the environment to skip MySQL outright.
 */
function tgd_mysql_reachable($host, $port = 3306, $timeout = 0.15) {
    $driver = strtolower((string)tgd_env('DB_DRIVER'));
    if ($driver === 'sqlite') return false;
    if ($driver === 'mysql')  return true;   // trust the operator, skip the probe

    // Windows does not report "connection refused" immediately - fsockopen
    // burns the whole timeout on a closed port. Cache the answer for a minute
    // so only one request in sixty pays for it, and it still self-heals if
    // MySQL is started or stopped later.
    $cacheFile = sys_get_temp_dir() . '/tgd_dbprobe_' . md5($host . ':' . $port);
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
        return trim((string)@file_get_contents($cacheFile)) === '1';
    }

    // "localhost" can resolve to ::1 first and stall; probe IPv4 directly.
    $probeHost = ($host === 'localhost') ? '127.0.0.1' : $host;
    $sock = @fsockopen($probeHost, $port, $errno, $errstr, $timeout);
    $ok = false;
    if ($sock) {
        fclose($sock);
        $ok = true;
    }
    @file_put_contents($cacheFile, $ok ? '1' : '0');
    return $ok;
}

// 1. Try MySQL, but only if something is listening on the port
if (tgd_mysql_reachable(DB_HOST)) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 3,
        ]);
    } catch (Exception $e) {
        $pdo = null;
    }
}

if (!$pdo) {
    // 2. SQLite fallback (Vercel, or any box without a local MySQL)
    try {
        $sqliteFile = __DIR__ . '/../database.sqlite';
        if (file_exists($sqliteFile)) {
            $pdo = new PDO("sqlite:" . $sqliteFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
    } catch (Exception $ex) {
        $pdo = null;
    }

    // 3. Fallback to DummyPDO if neither MySQL nor SQLite connection could be established
    if (!$pdo) {
        class DummyPDO {
            public function query() { return new DummyPDOStatement(); }
            public function prepare() { return new DummyPDOStatement(); }
            public function lastInsertId() { return 1; }
            public function exec() { return 1; }
        }
        class DummyPDOStatement {
            public function fetchAll() { return []; }
            public function fetch() { return false; }
            public function execute() { return true; }
            public function bindParam() { return true; }
            public function bindValue() { return true; }
            public function rowCount() { return 0; }
        }
        $pdo = new DummyPDO();
        define('DB_MOCK_MODE', true);
    }
}
?>
