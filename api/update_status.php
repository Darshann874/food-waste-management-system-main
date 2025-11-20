<?php
session_start();
include_once __DIR__ . '/../connection.php';
header("Content-Type: application/json");

$action = $_POST['action'] ?? '';
$fid = (int)($_POST['fid'] ?? 0);

if (!$fid || !$action) {
    echo json_encode(['error'=>'missing_fields']);
    exit;
}

// DELIVERY PERSON PICKUP / DELIVER
if (isset($_SESSION['Did'])) {
    $did = (int)$_SESSION['Did'];

    if ($action === 'pick') {
        $q = mysqli_prepare($connection,
            "UPDATE food_donations SET delivery_by=?, status='picked_up', picked_at=NOW()
             WHERE Fid=? AND (delivery_by IS NULL OR delivery_by=? )"
        );
        mysqli_stmt_bind_param($q, "iii", $did, $fid, $did);
        mysqli_stmt_execute($q);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'deliver') {
        $q = mysqli_prepare($connection,
            "UPDATE food_donations SET status='delivered', delivered_at=NOW()
             WHERE Fid=? AND delivery_by=?"
        );
        mysqli_stmt_bind_param($q, "ii", $fid, $did);
        mysqli_stmt_execute($q);
        echo json_encode(['ok'=>true]);
        exit;
    }
}

echo json_encode(['error'=>'unauthorized']);
