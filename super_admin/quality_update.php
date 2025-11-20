<?php
session_start();
include "../connection.php";
include "components/admin_helpers.php";
if (!isset($_SESSION['role']) || $_SESSION['role']!=='super_admin'){ header("Location: ../signin.php"); exit(); }
$fid = (int)($_POST['fid'] ?? 0);
$action = $_POST['action_type'] ?? 'approve';
$score = isset($_POST['score']) ? (int)$_POST['score'] : null;
$reason = trim($_POST['reason'] ?? null);
$proof_url = null;
// handle optional file... (omit here if using quality_verification.php upload)
if ($fid>0) {
    // upsert
    $check = mysqli_prepare($connection,"SELECT vid FROM food_verification WHERE Fid=? LIMIT 1"); mysqli_stmt_bind_param($check,"i",$fid); mysqli_stmt_execute($check); $res = mysqli_stmt_get_result($check); $exists = (bool)mysqli_fetch_assoc($res); mysqli_stmt_close($check);
    $verifiedVal = $action==='approve'?1:0;
    if ($exists) {
        $stmt = mysqli_prepare($connection,"UPDATE food_verification SET quality_verified=?, quality_score=?, quality_reason=? WHERE Fid=?");
        mysqli_stmt_bind_param($stmt,"iisi",$verifiedVal,$score,$reason,$fid);
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($connection,"INSERT INTO food_verification (Fid,quality_verified,quality_score,quality_reason,verification_time) VALUES(?,?,?,?,NOW())");
        mysqli_stmt_bind_param($stmt,"iiis",$fid,$verifiedVal,$score,$reason);
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
    }
    log_admin_action($connection, "verify_quality", json_encode(['fid'=>$fid,'action'=>$action,'score'=>$score,'reason'=>$reason,'proof'=>$proof_url]));
}
header("Location: quality_verification.php"); exit();
