<?php
// super_admin/check_admin_auth.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/components/admin_helpers.php';

$ADMIN_ALLOWED_ID = 1; // same id as used earlier

// basic session checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin' || !isset($_SESSION['email'])) {
    header("Location: ../signin.php");
    exit();
}

// optional: require the special flag set by OTP flow
if (!empty($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] !== (int)$ADMIN_ALLOWED_ID) {
    // if the session's admin id is not the allowed admin id, block
    header("Location: ../signin.php");
    exit();
}

// extra headers for security
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
