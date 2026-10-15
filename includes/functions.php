<?php
// =========================================================
// Shared Helper Functions
// =========================================================

function clean($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

// Restrict a page to specific role(s). Call at the very top of a protected page.
function requireRole($roles) {
    if (!is_array($roles)) $roles = [$roles];
    if (!isLoggedIn() || !in_array($_SESSION['role'], $roles)) {
        redirect('/auth/login.php?error=unauthorized');
    }
}

function flash($key, $msg = null) {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $out = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $out;
    }
    return null;
}

function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

function formatMoney($amount) {
    return 'KSh ' . number_format((float)$amount, 2);
}

function roleHomePath($role) {
    switch ($role) {
        case 'admin': return '/admin/dashboard.php';
        case 'doctor': return '/doctor/dashboard.php';
        case 'receptionist': return '/receptionist/dashboard.php';
        case 'patient': return '/patient/dashboard.php';
        default: return '/auth/login.php';
    }
}
