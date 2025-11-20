<?php
session_start();
include "../connection.php";

// NGO must be logged in
if (!isset($_SESSION['Rid'])) {
    header("Location: ../receiversignin.php");
    exit();
}

$rid = (int) $_SESSION['Rid'];
$msg = "";

// FIXED server-side agreement text
$consent_text = <<<TXT
NGO / Receiver Agreement

As a registered NGO/Receiver, I agree to the following:

1. I will check the food before accepting it.
2. I will not accept stale, spoiled, expired or unsafe food.
3. I will upload proof photos honestly during verification.
4. I will maintain hygiene while handling received food.
5. I will not collaborate in fraud with any donor or delivery person.
6. I will not fake confirmations or verification reports.
7. I take responsibility for the food once it is accepted.
8. I understand that cheating or fraud can result in blacklisting.

I accept all rules and responsibilities as an NGO participant.
TXT;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_agreement'])) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $hash = hash('sha256', $consent_text);

    mysqli_begin_transaction($connection);

    try {
        // Step 1 — mark consent signed
        $stmt = mysqli_prepare($connection, "UPDATE receivers SET consent_signed = 1 WHERE Rid=?");
        mysqli_stmt_bind_param($stmt, "i", $rid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Step 2 — store full consent log
        $stmt2 = mysqli_prepare($connection,
        "INSERT INTO ngo_consent_logs (ngo_id, consent_text, consent_hash, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt2, "issss", $rid, $consent_text, $hash, $ip, $ua);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        mysqli_commit($connection);

        header("Location: receiver.php"); // redirect to dashboard
        exit();
    } catch (Exception $e) {
        mysqli_rollback($connection);
        $msg = "Error saving consent. Try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>NGO Agreement</title>
<style>
body{font-family:Arial;background:#f7f7f7;padding:20px;}
.card{max-width:800px;margin:40px auto;background:#fff;border:1px solid #ddd;padding:20px;border-radius:10px;}
button{padding:10px 16px;background:#06C167;color:#fff;font-weight:bold;border:none;border-radius:8px;}
pre{white-space:pre-wrap;}
</style>
</head>
<body>
<div class="card">
<h2>NGO / Receiver Agreement</h2>
<p>You must accept the agreement to continue.</p>

<pre><?php echo htmlspecialchars($consent_text); ?></pre>

<?php if($msg): ?>
<div style="color:red;margin:10px 0;"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="post">
  <label>
    <input type="checkbox" required> I accept the NGO Agreement
  </label>
  <br><br>
  <input type="hidden" name="accept_agreement" value="1">
  <button type="submit">Accept & Continue</button>
</form>

</div>
</body>
</html>
