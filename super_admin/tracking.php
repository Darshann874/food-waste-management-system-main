<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../signin.php");
    exit();
}

include "../connection.php";

// ---- Fetch Active Deliveries ----
$sql = "
SELECT 
    fd.Fid, fd.food, fd.quantity, fd.status,
    dp.Did, dp.name AS dname, dp.live_lat, dp.live_lng, dp.last_updated
FROM food_donations fd
LEFT JOIN delivery_persons dp ON fd.delivery_by = dp.Did
WHERE fd.status IN ('assigned','picked_up')
ORDER BY fd.Fid DESC
";

$resultActive = mysqli_query($connection, $sql);

if (!$resultActive) {
    die("SQL ERROR: " . mysqli_error($connection));
}

include "components/sidebar.php";
include "components/topbar.php";
?>

<link rel="stylesheet" href="assets/admin.css">

<h1 class="sa-title">Live Delivery Tracking</h1>

<div class="sa-card">
    <h2>Active Deliveries</h2>

    <table class="sa-table">
        <thead>
            <tr>
                <th>Donation ID</th>
                <th>Food</th>
                <th>Status</th>
                <th>Delivery Person</th>
                <th>Live Location</th>
                <th>Last Updated</th>
            </tr>
        </thead>

        <tbody>
        <?php if (mysqli_num_rows($resultActive) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($resultActive)): ?>

                <?php
                $badge = ($row['status'] === 'picked_up')
                    ? '<span class="badge purple">Picked Up</span>'
                    : '<span class="badge blue">Assigned</span>';

                $locationLink = ($row['live_lat'] && $row['live_lng'])
                    ? '<a class="sa-btn sm" target="_blank"
                         href="https://www.openstreetmap.org/?mlat='.$row['live_lat'].'&mlon='.$row['live_lng'].'#map=16/'.$row['live_lat'].'/'.$row['live_lng'].'">
                         View Map
                       </a>'
                    : '<span class="small-muted">—</span>';
                ?>

                <tr>
                    <td>#<?= $row['Fid'] ?></td>
                    <td><?= htmlspecialchars($row['food']) ?> (<?= htmlspecialchars($row['quantity']) ?>)</td>
                    <td><?= $badge ?></td>
                    <td>
                        <?= $row['Did'] 
                            ? "D#{$row['Did']} · ".htmlspecialchars($row['dname'])
                            : '<span class="small-muted">Not assigned</span>' ?>
                    </td>
                    <td><?= $locationLink ?></td>
                    <td><?= htmlspecialchars($row['last_updated'] ?? '—') ?></td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>
            <tr>
                <td colspan="6" class="small-muted" style="text-align:center; padding:20px;">
                    No active deliveries.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div> <!-- close sa-container -->
<script src="assets/admin.js"></script>
