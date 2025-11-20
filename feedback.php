<?php
session_start();
include "connection.php";

if (!isset($_SESSION['email'])) {
    header("Location: signin.php"); exit();
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {

    $name = $_SESSION['name'] ?? '';
    $email = $_SESSION['email'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
        $msg = "Please write a message.";
    } else {
        $stmt = mysqli_prepare($connection, 
            "INSERT INTO user_feedback (name,email,message,created_at) VALUES (?,?,?,NOW())"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $message);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg = "success";  // for animation
        } 
        else $msg = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Feedback</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
/* ------------------- GLOBAL STYLE ------------------- */
body {
    font-family: "Inter", sans-serif;
    background: #f0fdf4;
    margin: 0;
    padding: 0;
    animation: fadeIn 0.6s ease;
}

/* fade animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.wrapper {
    max-width: 700px;
    margin: 60px auto;
    padding: 20px;
}

/* ------------------- CARD ------------------- */
.card {
    background: white;
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.06);
    animation: slideUp .6s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

h2 {
    color: #06C167;
    margin: 0 0 20px 0;
    font-size: 26px;
}

/* ------------------- TEXTAREA ------------------- */
textarea {
    width: 100%;
    min-height: 150px;
    padding: 14px;
    font-size: 16px;
    border-radius: 12px;
    border: 1px solid #d1e7dd;
    outline: none;
    background: #fdfefc;
    transition: 0.25s;
}

textarea:focus {
    border-color: #06C167;
    box-shadow: 0 0 0 3px rgba(6,193,103,0.2);
}

/* ------------------- BUTTON ------------------- */
button {
    width: 100%;
    margin-top: 15px;
    padding: 14px;
    background: #06C167;
    color: white;
    font-size: 17px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: 0.25s;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0px 6px 14px rgba(6,193,103,0.35);
}

/* ------------------- SUCCESS POPUP ------------------- */
.success-popup {
    display: none;
    text-align: center;
    margin-top: 20px;
    animation: pop .4s ease;
}

@keyframes pop {
    0% { transform: scale(0.7); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.success-popup .check {
    font-size: 60px;
    color: #06C167;
}

/* ------------------- LOADING ------------------- */
.loader {
    display: none;
    margin: 10px auto;
    border: 4px solid #e0ffe9;
    border-top: 4px solid #06C167;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.back {
    margin-top: 20px;
    display: inline-block;
    color: #06C167;
    font-weight: 600;
}
</style>
</head>

<body>

<div class="wrapper">
  <div class="card">
    
    <h2>Share Your Feedback 💬</h2>

    <!-- SUCCESS ANIMATION -->
    <?php if ($msg === "success"): ?>
        <div class="success-popup" style="display:block;">
            <div class="check">✔</div>
            <p style="color:#06C167;font-size:18px;">Thank you! Your feedback was received.</p>
        </div>
    <?php endif; ?>

    <form method="post" onsubmit="showLoader()">

      <textarea name="message" placeholder="Write your feedback..." required></textarea>

      <div class="loader" id="loader"></div>

      <button name="send" type="submit" id="submitBtn">Send Feedback</button>

    </form>

    <a href="home.php" class="back">← Back</a>

  </div>
</div>

<script>
function showLoader() {
    document.getElementById("loader").style.display = "block";
    document.getElementById("submitBtn").style.opacity = "0.5";
    document.getElementById("submitBtn").style.pointerEvents = "none";
}
</script>

</body>
</html>
