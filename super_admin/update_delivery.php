<?php
session_start();
include "../connection.php";
include "components/admin_helpers.php";
if (!isset($_SESSION['role']) || $_SESSION['role']!=='super_admin'){ header("Location: ../signin.php"); exit(); }
$fid = (int)($_POST['fid'] ?? 0);
$delivery_id = (int)($_POST['delivery_id'] ?? 0);
if ($fid>0) {
    log_admin_action($connection, "assign_delivery", json_encode(['fid'=>$fid,'delivery_by'=>$delivery_id]));
    $stmt = mysqli_prepare($connection,"UPDATE food_donations SET delivery_by=?, status='assigned' WHERE Fid=?");
    mysqli_stmt_bind_param($stmt,"ii",$delivery_id,$fid);
    mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
}
header("Location: donation_details.php?fid=$fid"); exit();
