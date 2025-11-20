<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') { header("Location: ../signin.php"); exit(); }
if (!isset($_SESSION['Rid']) || empty($_SESSION['Rid'])) { header("Location: ../signin.php"); exit(); }

include "../connection.php";
$Rid = (int) $_SESSION['Rid'];
$message = "";

$stmt = mysqli_prepare($connection, "SELECT * FROM receivers WHERE Rid=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $Rid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rec = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$rec) {
    $rec = ['name'=>$_SESSION['name'],'email'=>$_SESSION['email'],'location'=>$_SESSION['city'],'address'=>'','phoneno'=>''];
}

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $address = trim($_POST['address']);
    $phoneno = trim($_POST['phoneno']);

    $u = mysqli_prepare($connection, "UPDATE receivers SET name=?, location=?, address=?, phoneno=? WHERE Rid=?");
    mysqli_stmt_bind_param($u, "ssssi", $name, $location, $address, $phoneno, $Rid);
    mysqli_stmt_execute($u);
    mysqli_stmt_close($u);

    $_SESSION['name'] = $name;
    $_SESSION['city'] = $location;
    $message = "Profile updated successfully.";

    // reload
    $stmt = mysqli_prepare($connection, "SELECT * FROM receivers WHERE Rid=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $Rid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rec = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Receiver Profile</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

<style>
/* ---------- GLOBAL LAYOUT ---------- */
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: #f4fdf8;
}

/* Sidebar kept as it is */
.sidebar{
    width:240px;
    background:#fff;
    height:100vh;
    position:fixed;
    left:0;
    top:0;
    border-right:1px solid #e6eee6;
    padding:22px;
    display:flex;
    flex-direction:column;
    gap:20px;
}
.sidebar a {
    padding:12px;
    border-radius:12px;
    text-decoration:none;
    font-weight:600;
    color:#222;
    display:flex;
    align-items:center; gap:10px;
    transition:0.25s;
}
.sidebar a.active,
.sidebar a:hover {
    background:#e9f9ee;
    color:#06C167;
}

/* ---------- TOP BAR ---------- */
.topbar {
    margin-left:280px;
    padding:18px 28px;
    background:white;
    border-bottom:1px solid #e6eee6;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.topbar h3 {
    margin: 0;
    color:#06C167;
    font-size:22px;
    font-weight:700;
}

/* ---------- MAIN CONTAINER ---------- */
.container {
    margin-left:240px;
    padding:35px;
}

/* ---------- PROFILE CARD ---------- */
.card {
    max-width:680px;
    margin:auto;
    background:white;
    padding:32px;
    border-radius:18px;
    box-shadow:0 6px 25px rgba(0,0,0,0.06);
    animation:fade 0.4s ease-in-out;
}

@keyframes fade { from {opacity:0; transform:translateY(10px);} to {opacity:1;} }

.card h2 {
    margin-top:0;
    font-size:24px;
    color:#333;
    font-weight:700;
}

/* ---------- INPUTS & LABELS ---------- */
label {
    font-weight:600;
    color:#333;
    margin-bottom:6px;
    display:block;
}

input, select, textarea {
    width:100%;
    padding:12px 14px;
    border-radius:10px;
    border:1px solid #dce7e1;
    background:#fafffc;
    font-size:15px;
    transition:0.25s;
}

input:focus, select:focus, textarea:focus {
    border-color:#06C167;
    box-shadow:0 0 0 3px rgba(6,193,103,0.18);
    outline:none;
}

/* ---------- UPDATE BUTTON ---------- */
.btn {
    width:100%;
    background:#06C167;
    color:white;
    padding:14px;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:700;
    margin-top:10px;
    cursor:pointer;
    transition:0.3s;
}

.btn:hover {
    background:#05a957;
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(6,193,103,0.25);
}

/* ---------- SUCCESS MESSAGE ---------- */
.msg {
    background:#e8ffe8;
    border-left:6px solid #06C167;
    padding:12px;
    margin-bottom:18px;
    color:#056c3c;
    font-weight:600;
    border-radius:10px;
}
</style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="topbar">
    <h3>Profile Settings</h3>
</div>

<div class="container">

  <div class="card">

    <h2>Your Profile</h2>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
      
      <label>Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($rec['name']) ?>" required>

      <label>Email</label>
      <input type="text" value="<?= htmlspecialchars($rec['email']) ?>" disabled>

      <label>District</label>
      <select name="location" required>
        <?php
        $districts = [
          "Dehradun","Haridwar","Nainital","Pauri Garhwal","Tehri Garhwal",
          "Chamoli","Pithoragarh","Almora","Bageshwar","Champawat",
          "Rudraprayag","Uttarkashi","Udham Singh Nagar"
        ];

        foreach ($districts as $d) {
          $sel = ($rec['location'] == $d) ? "selected" : "";
          echo "<option value='$d' $sel>$d</option>";
        }
        ?>
      </select>

      <label>Address</label>
      <textarea name="address" rows="3"><?= htmlspecialchars($rec['address']) ?></textarea>

      <label>Phone</label>
      <input type="text" name="phoneno" value="<?= htmlspecialchars($rec['phoneno']) ?>">

      <button class="btn" name="update_profile">Update Profile</button>

    </form>
  </div>

</div>

</body>
</html>
