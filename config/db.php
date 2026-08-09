<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'gayatri_db');

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

// 1. Try MySQL Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (Exception $e) {
    // 2. Try SQLite fallback (for Vercel / environment without active local MySQL)
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
