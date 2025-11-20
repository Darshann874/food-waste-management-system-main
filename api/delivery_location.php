<?php
session_start();
include "../connection.php";
$fid = (int)($_GET['fid'] ?? 0);
$row = mysqli_fetch_assoc(mysqli_query($connection,"
  SELECT dp.live_lat, dp.live_lng
  FROM food_donations fd
  JOIN delivery_persons dp ON fd.delivery_by = dp.Did
  WHERE fd.Fid = $fid
"));
header('Content-Type: application/json');
echo json_encode(['lat'=>$row['live_lat']??null,'lng'=>$row['live_lng']??null]);
