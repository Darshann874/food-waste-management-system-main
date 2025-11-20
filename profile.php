<?php
// profile.php
session_start();
include "connection.php";

if (!isset($_SESSION['email'])) {
    header("Location: signin.php"); exit();
}

$email = $_SESSION['email'];
$role = $_SESSION['role'] ?? 'donor';
$msg = '';

// handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $phone = trim($_POST['phoneno'] ?? '');
    $location = trim($_POST['location'] ?? '');
    // update login table
    $stmt = mysqli_prepare($connection, "UPDATE login SET name=?, gender=?, city=? WHERE email=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $name, $gender, $location, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['name'] = $name;
        $_SESSION['location'] = $location;
        $msg = "Profile updated.";
    } else {
        $msg = "Unable to update profile.";
    }
}

// fetch current info
$stmt = mysqli_prepare($connection, "SELECT name, gender, city FROM login WHERE email=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res) ?: [];
mysqli_stmt_close($stmt);
$name = htmlspecialchars($user['name'] ?? $_SESSION['name'] ?? '');
$gender = htmlspecialchars($user['gender'] ?? $_SESSION['gender'] ?? '');
$location = htmlspecialchars($user['city'] ?? $_SESSION['location'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><title>Profile</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Inter,system-ui;background:#f7fff9;margin:0}
.card{max-width:720px;margin:40px auto;background:#fff;padding:22px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.06)}
h2{color:#06C167;margin-top:0}
.input{display:block;margin-bottom:12px}
input,select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6efe6}
button{background:#06C167;color:#fff;padding:10px 14px;border-radius:8px;border:none}
.alert{padding:10px;border-radius:8px;margin-bottom:12px}
.alert.ok{background:#ecfdf5;border:1px solid #bbf7d0;color:#064e3b}
</style>
</head>
<body>
<div class="card">
  <h2>Profile</h2>
  <?php if($msg): ?><div class="alert ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post">
    <label class="input">Name
      <input name="name" value="<?= $name ?>" required>
    </label>
    <label class="input">Gender
      <select name="gender">
        <option <?= $gender==='Male' ? 'selected':'' ?>>Male</option>
        <option <?= $gender==='Female' ? 'selected':'' ?>>Female</option>
        <option <?= $gender==='Other' ? 'selected':'' ?>>Other</option>
        <option <?= $gender==='N/A' ? 'selected':'' ?>>N/A</option>
      </select>
    </label>
    <label class="input">City / Location
      <input name="location" value="<?= $location ?>">
    </label>
    <label class="input">Email
      <input disabled value="<?= htmlspecialchars($email) ?>">
    </label>
    <button name="update" type="submit">Save</button>
  </form>
  <div style="margin-top:12px"><a href="home.php" style="color:#06C167">← Back</a></div>
</div>
</body>
</html>
