<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Save an admin log entry
 * Example:
 * log_admin_action($connection, $_SESSION['email'], "Updated donation", json_encode(["fid"=>5]))
 */
function log_admin_action($connection, $admin_email, $action, $context = null) {

    // Prevent crash if table missing
    $check = mysqli_query($connection, "SHOW TABLES LIKE 'admin_logs'");
    if (mysqli_num_rows($check) == 0) return;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $sql = "INSERT INTO admin_logs (admin_email, action, context, ip)
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($connection, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $admin_email, $action, $context, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/* ---------------- LOGIN ATTEMPT HELPERS ---------------- */

function get_login_attempts($connection, $email) {
    $stmt = mysqli_prepare($connection, "SELECT attempts, last_attempt FROM login_attempts WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    return $row ? [(int)$row['attempts'], $row['last_attempt']] : [0, null];
}

function increment_login_attempt($connection, $email) {
    $stmt = mysqli_prepare($connection, "
        INSERT INTO login_attempts (email, attempts, last_attempt)
        VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()
    ");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function reset_login_attempts($connection, $email) {
    $stmt = mysqli_prepare($connection, "DELETE FROM login_attempts WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/* ---------------- OTP HELPER ---------------- */

function generate_admin_otp($length = 6) {
    $min = pow(10, $length - 1);
    $max = pow(10, $length) - 1;
    return strval(rand($min, $max));
}
