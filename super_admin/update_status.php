<?php
session_start();
include "../connection.php";
include "components/admin_helpers.php";
if (!isset($_SESSION['role']) || $_SESSION['role']!=='super_admin'){ header("Location: ../signin.php"); exit(); }
$fid = (int)($_POST['fid'] ?? 0);
$status = $_POST['status'] ?? '';
if ($fid>0 && $status) {
    log_admin_action($connection, "update_status", json_encode(['fid'=>$fid,'status'=>$status]));
    $stmt = mysqli_prepare($connection,"UPDATE food_donations SET status=? WHERE Fid=?");
    mysqli_stmt_bind_param($stmt,"si",$status,$fid);
    mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
}
header("Location: donation_details.php?fid=$fid"); exit();
