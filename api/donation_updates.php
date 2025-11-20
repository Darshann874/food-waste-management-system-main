<?php
session_start();
include "../connection.php";

$email = $_SESSION['email'] ?? '';
$role  = $_SESSION['role'] ?? '';

$rows = [];
if ($role === 'donor') {
  $q = mysqli_prepare($connection, "SELECT Fid, food, status, picked_at, delivered_at FROM food_donations WHERE email=? ORDER BY Fid DESC LIMIT 20");
  mysqli_stmt_bind_param($q,"s",$email);
  mysqli_stmt_execute($q);
  $res = mysqli_stmt_get_result($q);
  while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
  mysqli_stmt_close($q);
} elseif ($role === 'receiver') {
  // receiver sees their assigned donations
  $rid = (int)($_SESSION['Rid'] ?? 0);
  $res = mysqli_query($connection, "SELECT Fid, food, status, picked_at, delivered_at FROM food_donations WHERE assigned_to=$rid ORDER BY Fid DESC LIMIT 20");
  while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
}

header('Content-Type: application/json');
echo json_encode($rows);
