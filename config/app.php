<?php
// =========================================================
// App Bootstrap: session + core config
// =========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'HMS - Hospital Management System');
define('BASE_URL', '/hms'); // change to match your XAMPP htdocs subfolder

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';
