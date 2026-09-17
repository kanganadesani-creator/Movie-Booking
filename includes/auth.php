<?php
// ==========================================
// Authentication Helper Functions
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- USER AUTH ----
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// ---- ADMIN AUTH ----
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: " . BASE_URL . "admin/login.php");
        exit();
    }
}

// ---- SECURITY HELPERS ----
function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function generateBookingCode() {
    return 'CB' . strtoupper(substr(uniqid(), -8)) . rand(10, 99);
}

function generateTransactionId() {
    return 'TXN' . strtoupper(substr(uniqid(), -10)) . rand(100, 999);
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
    } else {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}
