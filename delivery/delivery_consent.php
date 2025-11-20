<?php
session_start();
include "../connection.php"; // adjust path as needed

// ensure delivery user logged in
if (!isset($_SESSION['Did'])) {
    header("Location: ../delivery_login.php");
    exit();
}
$did = (int) $_SESSION['Did'];
$msg = "";

// Fixed consent text (server-side)
$consent_text = <<<TXT
Delivery Partner Agreement
As a registered Delivery Partner, I agree to the following:
1. I will not accept bribes or illegal payments.
2. I will follow the pickup → delivery flow without skipping checkpoints.
3. I confirm food will not be tampered with during delivery.
4. I will deliver the food safely, on time, and in the condition received.
5. I accept random inspections by system admin.
6. I understand cheating, bribery, late delivery, or fraud may result in suspension.
I accept full responsibility for my conduct while performing deliveries.
TXT;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_agreement'])) {
    // verify checkbox present (front-end required attribute prevents missing)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // compute hash for integrity
    $hash = hash('sha256', $consent_text);

    // start transaction for atomic update + log
    mysqli_begin_transaction($connection);

    try {
        // 1) update delivery_persons consent flag
        $stmt = mysqli_prepare($connection, "UPDATE delivery_persons SET consent_signed = 1 WHERE Did = ?");
        mysqli_stmt_bind_param($stmt, "i", $did);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 2) insert into logs
        $stmt2 = mysqli_prepare($connection, "INSERT INTO delivery_consent_logs (delivery_id, consent_text, consent_hash, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt2, "issss", $did, $consent_text, $hash, $ip, $ua);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        mysqli_commit($connection);
        header("Location: delivery.php"); // change to your dashboard
        exit();
    } catch (Exception $e) {
        mysqli_rollback($connection);
        $msg = "Error saving consent, please contact admin.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Delivery Agreement</title>
  <style>
    body{font-family:Arial, sans-serif;background:#f7f9fc;padding:20px;}
    .card{max-width:800px;margin:36px auto;padding:20px;border-radius:10px;background:#fff;border:1px solid #e6eef8;}
    pre{white-space:pre-wrap;font-family:inherit;}
    .agree{margin-top:16px;}
    button{background:#06C167;color:#fff;padding:12px 18px;border-radius:8px;border:none;font-weight:700;}
  </style>
</head>
<body>
  <div class="card">
    <h2>Delivery Partner Agreement</h2>
    <p>Please read and accept the agreement to continue using the delivery features.</p>

    <pre><?php echo htmlspecialchars($consent_text); ?></pre>

    <?php if($msg): ?><div style="color:red;margin-bottom:10px;"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <form method="post">
      <div class="agree">
        <input type="checkbox" id="agree" name="agree_checkbox" required>
        <label for="agree">I have read and accept the Delivery Partner Agreement</label>
      </div>
      <input type="hidden" name="accept_agreement" value="1">
      <br>
      <button type="submit">Accept & Continue</button>
    </form>
  </div>
</body>
</html>
