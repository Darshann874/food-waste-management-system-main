<?php
session_start();
include "../connection.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: ../signin.php");
    exit();
}

$deliverName = $_SESSION['name'];
$deliverEmail = $_SESSION['email'];
$deliverCity = $_SESSION['city'];

$pageTitle = "Profile";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
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

        <h3>Your Profile</h3>

        <div class="card" style="max-width:400px">
            <p><b>Name:</b> <?= $deliverName ?></p>
            <p><b>Email:</b> <?= $deliverEmail ?></p>
            <p><b>City:</b> <?= $deliverCity ?></p>
        </div>

    </main>
</div>

</body>
</html>
