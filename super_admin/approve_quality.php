<?php
session_start();
include "../connection.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../signin.php");
    exit();
}

if (isset($_POST['fid'])) {
    $fid = (int)$_POST['fid'];

    $q = mysqli_prepare($connection,
        "UPDATE food_donations 
         SET quality_verified = 1 
         WHERE Fid = ?");
    mysqli_stmt_bind_param($q, "i", $fid);
    mysqli_stmt_execute($q);
}

header("Location: quality_verification.php");
exit();
?>
