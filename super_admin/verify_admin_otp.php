<?php
// super_admin/verify_admin_otp.php
session_start();
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/components/admin_helpers.php';

$err = '';
if (!isset($_SESSION['admin_temp_email'])) {
    header("Location: secure_login.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $saved = $_SESSION['admin_otp'] ?? null;
    $expires = $_SESSION['admin_otp_expires'] ?? 0;

    if (!$saved || time() > $expires) {
        $err = "OTP expired. Please start login again.";
        // clear temp session
        unset($_SESSION['admin_temp_email'], $_SESSION['admin_temp_id'], $_SESSION['admin_otp'], $_SESSION['admin_otp_expires']);
    } else if ($otp !== $saved) {
        $err = "Invalid OTP. Try again.";
        // optional: track attempts per OTP here if desired
        // log failed otp
        log_admin_action($connection, $_SESSION['admin_temp_email'] ?? 'unknown', "Failed admin OTP attempt");
    } else {
        // OTP valid — create full admin session
        $email = $_SESSION['admin_temp_email'];
        $_SESSION['email'] = $email;
        $_SESSION['name']  = ''; // optionally fetch name
        $_SESSION['role'] = 'super_admin';
        $_SESSION['is_super_admin_verified'] = true; // extra flag

        // optional: fetch name for session
        $stmt = mysqli_prepare($connection, "SELECT name, id FROM login WHERE email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
            if ($row) {
                $_SESSION['name'] = $row['name'];
                $_SESSION['admin_id'] = (int)$row['id'];
            }
        }

        // log admin login
        log_admin_action($connection, $email, "Admin logged in successfully (OTP verified)");

        // cleanup temp
        unset($_SESSION['admin_temp_email'], $_SESSION['admin_otp'], $_SESSION['admin_otp_expires']);

        header("Location: dashboard.php");
        exit();
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify Admin OTP</title>
<style>body{font-family:Inter,system-ui;background:#f7fff9;display:flex;align-items:center;justify-content:center;height:100vh}.card{background:#fff;padding:20px;border-radius:10px;box-shadow:0 12px 34px rgba(6,193,103,.06);width:380px}input{width:100%;padding:10px;border-radius:8px;border:1px solid #e6f3ec}button{background:#06C167;color:#fff;padding:10px;border-radius:8px;border:none;cursor:pointer}.err{color:#b91c1c}</style></head><body>
<div class="card">
  <h3>Enter OTP</h3>
  <p class="small">We have sent an OTP to the admin email.</p>
  <?php if($err): ?><div class="err"><?=htmlspecialchars($err)?></div><?php endif;?>
  <form method="post">
    <input name="otp" placeholder="6-digit OTP" required>
    <button type="submit" style="margin-top:10px">Verify & Login</button>
  </form>
  <p style="font-size:13px;color:#536463;margin-top:10px">If you didn't receive the OTP, return to <a href="secure_login.php">Secure Login</a> to resend.</p>
</div>
</body></html>
