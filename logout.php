<?php
require_once __DIR__ . '/includes/functions.php';
logout_user();

function logout_user() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
