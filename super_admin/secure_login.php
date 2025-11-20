<?php
// super_admin/secure_login.php
session_start();
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/components/admin_helpers.php';

$ADMIN_ALLOWED_ID = 1; // change if your super-admin user id is different
$MAX_ATTEMPTS = 5;
$LOCK_MINUTES = 10;

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $err = "Provide email and password.";
    } else {
        // check attempts
        list($attempts, $last_attempt) = get_login_attempts($connection, $email);
        if ($attempts >= $MAX_ATTEMPTS) {
            // calculate minutes since last attempt
            $minutes = (time() - strtotime($last_attempt)) / 60;
            if ($minutes < $LOCK_MINUTES) {
                $err = "Too many attempts. Try again after " . ceil($LOCK_MINUTES - $minutes) . " minute(s).";
            } else {
                reset_login_attempts($connection, $email);
                $attempts = 0;
            }
        }

        if (!$err) {
            $stmt = mysqli_prepare($connection, "SELECT id, email, password, role FROM login WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!$row) {
                $err = "Account not found.";
                increment_login_attempt($connection, $email);
            } else {
                if (!password_verify($password, $row['password'])) {
                    $err = "Invalid credentials.";
                    increment_login_attempt($connection, $email);
                } else {
                    // correct password — but ensure this user is the allowed admin id and role
                    if ($row['role'] !== 'super_admin' || (int)$row['id'] !== (int)$ADMIN_ALLOWED_ID) {
                        $err = "Unauthorized for Admin access.";
                        // do not increment attempts too aggressively for this case, but you may log it
                        log_admin_action($connection, $email, "Unauthorized admin login attempt (role/id mismatch)");
                    } else {
                        // reset attempts on successful stage-1
                        reset_login_attempts($connection, $email);

                        // create temporary session state and generate OTP
                        $_SESSION['admin_temp_email'] = $email;
                        $_SESSION['admin_temp_id'] = (int)$row['id'];
                        $_SESSION['admin_otp'] = generate_admin_otp(6);
                        $_SESSION['admin_otp_expires'] = time() + 5 * 60; // 5 minutes expiry

                        // send OTP to admin email
                        $subject = "Your Admin Panel OTP";
                        $message = "Your one-time admin OTP is: " . $_SESSION['admin_otp'] . "\n\nIt will expire in 5 minutes.";
                        $headers = "From: no-reply@yourdomain.com\r\n";
                        // NOTE: PHP mail() may require config; use SMTP if needed.
                        @mail($email, $subject, $message, $headers);

                        // log stage
                        log_admin_action($connection, $email, "Admin login stage-1 successful: password verified; OTP sent.");

                        header("Location: verify_admin_otp.php");
                        exit();
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Secure Login</title>
<style>
body{font-family:Inter,system-ui;background:#f7fff9;color:#062318;display:flex;align-items:center;justify-content:center;height:100vh}
.card{background:#fff;padding:26px;border-radius:12px;box-shadow:0 12px 40px rgba(6,193,103,0.08);width:420px}
input{width:100%;padding:10px;border-radius:8px;border:1px solid #e6f3ec;margin-bottom:10px}
button{background:#06C167;color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer}
.err{color:#b91c1c;margin-bottom:10px}
.small{font-size:13px;color:#536463}
</style>
</head>
<body>
<div class="card">
  <h2>Admin Secure Login</h2>
  <p class="small">This page is for Super Admin only. Enter your credentials to receive a one-time OTP.</p>

  <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="post">
    <input type="email" name="email" placeholder="Admin email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Send OTP</button>
  </form>

  <p class="small" style="margin-top:12px">OTP will be sent to the account email. If you cannot receive email, configure SMTP in PHP or use an SMTP library (PHPMailer).</p>
</div>
</body>
</html>
