<?php
session_start();
include_once __DIR__ . '/../connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['Did'])) {
    echo json_encode(['error' => 'not_authenticated']);
    exit;
}

$did = (int)$_SESSION['Did'];
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if (!$lat || !$lng) {
    echo json_encode(['error' => 'invalid_coordinates']);
    exit;
}

$q = mysqli_prepare($connection,
    "UPDATE delivery_persons SET live_lat=?, live_lng=?, last_location_update=NOW() WHERE Did=?"
);
mysqli_stmt_bind_param($q, "ssi", $lat, $lng, $did);
mysqli_stmt_execute($q);

echo json_encode(['ok'=>true]);
