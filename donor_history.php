<?php
session_start();
include "connection.php";

if (!isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$donorEmail = $_SESSION['email'];

// Fetch donations by this donor
$stmt = mysqli_prepare($connection,
"SELECT Fid, food, category, quantity, date, location, status, picked_at, delivered_at
 FROM food_donations
 WHERE email = ?
 ORDER BY Fid DESC");
mysqli_stmt_bind_param($stmt, "s", $donorEmail);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$donations = [];
while ($row = mysqli_fetch_assoc($result)) $donations[] = $row;
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Donation History</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body { background:#ffffff; font-family:Inter, system-ui; margin:0; padding:0; }
.container { max-width:900px; margin:40px auto; padding:0 20px; }

h2 { color:#06C167; font-size:32px; text-align:center; margin-bottom:24px; }

.table-wrapper { overflow-x:auto; }
table { width:100%; border-collapse:separate; border-spacing:0 10px; }
th { text-align:left; padding:12px; color:#5f6b7b; font-size:14px; }
td { background:#f9fffc; padding:14px; border-radius:10px; font-size:15px; border:1px solid #c5f3db; }

.badge { padding:6px 12px; border-radius:30px; font-size:13px; font-weight:600; text-transform:capitalize; }
.badge.pending     { background:#eee; color:#666; }
.badge.assigned    { background:#d1fae5; color:#065f46; }
.badge.picked_up   { background:#dbeafe; color:#1e40af; }
.badge.delivered   { background:#dcfce7; color:#14532d; }

.back-btn {
  display:inline-block; margin-bottom:18px; padding:10px 18px;
  background:#06C167; color:white; border-radius:8px; text-decoration:none; font-weight:600;
  transition:.3s;
}
.back-btn:hover { box-shadow:0 6px 18px rgba(6,193,103,.3); transform:translateY(-2px); }
</style>
</head>

<body>
<div class="container">

<a href="home.php" class="back-btn">← Back to Home</a>

<h2>Your Donation History</h2>

<div class="table-wrapper">
<table>
<thead>
<tr>
  <th>Food</th>
  <th>Qty</th>
  <th>District</th>
  <th>Date</th>
  <th>Status</th>
</tr>
</thead>
<tbody>

<?php if(!empty($donations)): ?>
  <?php foreach ($donations as $d): ?>
  <tr>
    <td><?= htmlspecialchars($d['food']) ?> <small>(<?= htmlspecialchars($d['category']) ?>)</small></td>
    <td><?= htmlspecialchars($d['quantity']) ?></td>
    <td><?= htmlspecialchars($d['location']) ?></td>
    <td><?= htmlspecialchars($d['date']) ?></td>
    <td>
      <span class="badge <?= htmlspecialchars($d['status']) ?>">
        <?= htmlspecialchars($d['status']) ?>
      </span>
    </td>
  </tr>
  <?php endforeach; ?>

<?php else: ?>
<tr><td colspan="5" style="text-align:center;">No donations yet.</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>

</div>
</body>
</html>
