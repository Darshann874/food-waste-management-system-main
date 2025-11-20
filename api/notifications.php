<?php
// api/notifications.php
session_start();
include_once __DIR__ . '/../connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) { http_response_code(401); echo json_encode([]); exit; }

$email = $_SESSION['email'];
$role = $_SESSION['role'] ?? 'donor';

// For receiver: return donations assigned to them changed recently (last 30s)
if ($role === 'receiver' && isset($_SESSION['Rid'])) {
    $rid = (int)$_SESSION['Rid'];
    $q = mysqli_prepare($connection, "SELECT Fid, food, status, delivery_by, picked_at, delivered_at FROM food_donations WHERE assigned_to=? AND (status='assigned' OR status='picked_up' OR status='delivered') ORDER BY Fid DESC LIMIT 10");
    mysqli_stmt_bind_param($q, "i", $rid);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);
    $arr = [];
    while ($r = mysqli_fetch_assoc($res)) $arr[] = $r;
    echo json_encode($arr);
    exit;
}

// For donor: return updates for donations posted by them
if ($role === 'donor') {
    $q = mysqli_prepare($connection, "SELECT Fid, food, status, assigned_to, delivery_by FROM food_donations WHERE email=? AND (status<>'pending') ORDER BY date DESC LIMIT 10");
    mysqli_stmt_bind_param($q, "s", $email);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);
    $arr = [];
    while ($r = mysqli_fetch_assoc($res)) $arr[] = $r;
    echo json_encode($arr);
    exit;
}

// For delivery: show deliveries assigned to this delivery person
if ($role === 'delivery' && isset($_SESSION['Did'])) {
    $did = (int)$_SESSION['Did'];
    $q = mysqli_prepare($connection, "SELECT Fid, food, status, picked_at, delivered_at FROM food_donations WHERE delivery_by=? ORDER BY Fid DESC LIMIT 10");
    mysqli_stmt_bind_param($q, "i", $did);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);
    $arr = [];
    while ($r = mysqli_fetch_assoc($res)) $arr[] = $r;
    echo json_encode($arr);
    exit;
}

echo json_encode([]);
