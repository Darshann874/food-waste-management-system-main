<?php
session_start();
include "../connection.php";

// Auth
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: ../signin.php");
    exit();
}

$Did = $_SESSION['Did'];
$deliverName = $_SESSION['name'];
$deliverCity = $_SESSION['city'];

$pageTitle = "Completed Orders";

// Fetch completed
$stmt = mysqli_prepare($connection, "
    SELECT * FROM food_donations 
    WHERE delivery_by=? AND status='delivered'
    ORDER BY Fid DESC
");
mysqli_stmt_bind_param($stmt, "i", $Did);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$orders = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Completed Orders</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        <?php include "delivery_styles.css"; ?>
    </style>
</head>
<body>

<div class="app">
    <?php include "sidebar_delivery.php"; ?>

    <main class="main">
        <?php include "topbar_delivery.php"; ?>

        <h3>Completed Orders</h3>
        <div class="orders">
            <?php if (empty($orders)): ?>
                <div class="empty">No completed orders.</div>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="order">
                        <div><b><?= $o['food'] ?></b> x <?= $o['quantity'] ?></div>
                        <div class="small"><?= $o['address'] ?>, <?= $o['location'] ?></div>
                        <div class="badge delivered">DELIVERED</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>
