<?php
// delivery/delivery_dashboard.php
session_start();
include("../connection.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: ../signin.php");
    exit();
}

$Did = (int)($_SESSION['Did'] ?? 0);

/* ----------------------------------------------------
   CONSENT CHECK (This must run BEFORE dashboard logic)
---------------------------------------------------- */
$stmt = mysqli_prepare($connection, "SELECT consent_signed FROM delivery_persons WHERE Did = ?");
mysqli_stmt_bind_param($stmt, "i", $Did);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $consent_signed);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($consent_signed == 0) {
    header("Location: delivery_consent.php");
    exit();
}
/* ---------------------------------------------------- */


// Accept / pick / deliver via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['accept']) && isset($_POST['fid'])) {
        $fid = (int)$_POST['fid'];
        $stmt = mysqli_prepare($connection, "UPDATE food_donations SET delivery_by=?, status='assigned' WHERE Fid=? AND (delivery_by IS NULL OR delivery_by=0)");
        if ($stmt) { mysqli_stmt_bind_param($stmt, "ii", $Did, $fid); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
        header("Location: delivery_dashboard.php"); exit();
    }

    if (isset($_POST['picked']) && isset($_POST['fid'])) {
        $fid = (int)$_POST['fid'];
        $stmt = mysqli_prepare($connection, "UPDATE food_donations SET status='picked_up', picked_at=NOW() WHERE Fid=? AND delivery_by=?");
        if ($stmt) { mysqli_stmt_bind_param($stmt, "ii", $fid, $Did); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
        header("Location: delivery_dashboard.php"); exit();
    }

    if (isset($_POST['delivered']) && isset($_POST['fid'])) {
        $fid = (int)$_POST['fid'];
        $stmt = mysqli_prepare($connection, "UPDATE food_donations SET status='delivered', delivered_at=NOW() WHERE Fid=? AND delivery_by=?");
        if ($stmt) { mysqli_stmt_bind_param($stmt, "ii", $fid, $Did); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
        header("Location: delivery_dashboard.php"); exit();
    }
}


// available & my orders
$available = mysqli_query($connection, "SELECT * FROM food_donations WHERE (delivery_by IS NULL OR delivery_by=0) AND status='assigned' ORDER BY Fid DESC LIMIT 100");

$myOrders = mysqli_prepare($connection, "SELECT * FROM food_donations WHERE delivery_by=? ORDER BY Fid DESC");
mysqli_stmt_bind_param($myOrders, "i", $Did);
mysqli_stmt_execute($myOrders);
$myOrdersRes = mysqli_stmt_get_result($myOrders);
$myOrdersArr = mysqli_fetch_all($myOrdersRes, MYSQLI_ASSOC) ?: [];
mysqli_stmt_close($myOrders);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Delivery Dashboard</title>
</head>

<body style="font-family:Inter,system-ui">
<div style="max-width:1100px;margin:20px auto">
  <h2 style="color:#06C167">Delivery Dashboard</h2>

  <h3>Available Orders</h3>
  <table style="width:100%;border-collapse:collapse">
    <thead><tr><th>Food</th><th>Address</th><th>Qty</th><th>Action</th></tr></thead>
    <tbody>
    <?php while($a = mysqli_fetch_assoc($available)): ?>
      <tr>
        <td><?=htmlspecialchars($a['food'])?></td>
        <td><?=htmlspecialchars($a['address']).', '.htmlspecialchars($a['location'])?></td>
        <td><?=htmlspecialchars($a['quantity'])?></td>
        <td>
          <form method="post" style="display:inline">
            <input type="hidden" name="fid" value="<?= (int)$a['Fid'] ?>">
            <button name="accept" style="background:#06C167;color:#fff;padding:8px 10px;border-radius:8px;border:none">Accept</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>

  <h3 style="margin-top:18px">Your Orders</h3>
  <table style="width:100%;border-collapse:collapse">
    <thead><tr><th>Food</th><th>Status</th><th>Picked</th><th>Delivered</th><th>Action</th></tr></thead>
    <tbody>
    <?php if(empty($myOrdersArr)): ?>
        <tr><td colspan="5" style="padding:12px">No assigned orders</td></tr>
    <?php else: foreach($myOrdersArr as $o): ?>
      <tr>
        <td><?=htmlspecialchars($o['food'])?></td>
        <td><?=htmlspecialchars($o['status'])?></td>
        <td><?=htmlspecialchars($o['picked_at'] ?? '-')?></td>
        <td><?=htmlspecialchars($o['delivered_at'] ?? '-')?></td>
        <td>
          <?php if ($o['status'] === 'assigned'): ?>
            <form method="post" style="display:inline"><input type="hidden" name="fid" value="<?= (int)$o['Fid'] ?>"><button name="picked" style="padding:8px;border-radius:8px;background:#1e90ff;color:#fff;border:none">Mark Picked</button></form>
          <?php elseif ($o['status'] === 'picked_up'): ?>
            <form method="post" style="display:inline"><input type="hidden" name="fid" value="<?= (int)$o['Fid'] ?>"><button name="delivered" style="padding:8px;border-radius:8px;background:#0a8a41;color:#fff;border:none">Mark Delivered</button></form>
          <?php else: echo "Done"; endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>

  <div style="margin-top:12px"><a href="../home.php" style="color:#06C167">← Back</a></div>
</div>
</body>
</html>
