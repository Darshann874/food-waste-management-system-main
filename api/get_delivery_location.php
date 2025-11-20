<?php
session_start();
include_once __DIR__ . '/../connection.php';

header("Content-Type: application/json");

$did = isset($_GET['did']) ? (int)$_GET['did'] : 0;
if (!$did) {
    echo json_encode(["error" => "missing_id"]);
    exit;
}

$q = mysqli_prepare($connection,
    "SELECT live_lat, live_lng, last_location_update
     FROM delivery_persons WHERE Did=? LIMIT 1"
);
mysqli_stmt_bind_param($q, "i", $did);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);

$data = mysqli_fetch_assoc($res);
echo json_encode($data ?: []);
