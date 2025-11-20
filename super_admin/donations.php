<?php
session_start(); 
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') { 
    header("Location: ../signin.php"); 
    exit();
}

include "../connection.php";

$sql = "
SELECT d.Fid,
       d.name AS donor_name,
       d.email AS donor_email,
       d.food,
       d.category,
       d.quantity,
       d.location,
       d.address,
       d.status,
       d.prepared_at,
       d.assigned_to,
       d.delivery_by,
       r.name AS receiver_name,
       dp.name AS delivery_name,
       fv.quality_verified,
       fv.quality_score,
       fv.quality_proof
FROM food_donations d
LEFT JOIN receivers r ON r.Rid = d.assigned_to
LEFT JOIN delivery_persons dp ON dp.Did = d.delivery_by
LEFT JOIN food_verification fv ON fv.Fid = d.Fid
ORDER BY d.Fid DESC
";

$res = mysqli_query($connection, $sql);
$donations = mysqli_fetch_all($res, MYSQLI_ASSOC);
?>

<?php include "components/sidebar.php"; ?>
<?php include "components/topbar.php"; ?>

<link rel="stylesheet" href="assets/admin.css">

<h1 class="sa-title">All Donations</h1>

<div class="sa-card">

<table class="sa-table">
<thead>
<tr>
<th>ID</th>
<th>Food</th>
<th>Donor</th>
<th>Receiver</th>
<th>Delivery</th>
<th>Status</th>
<th>Quality</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach($donations as $d): 
    $statusColor = [
        'pending'    => 'orange',
        'assigned'   => 'blue',
        'picked_up'  => 'purple',
        'delivered'  => 'green'
    ][$d['status']] ?? 'gray';
?>
<tr>
<td>#<?= $d['Fid'] ?></td>

<td>
    <?= htmlspecialchars($d['food']) ?><br>
    <small class="small-muted"><?= htmlspecialchars($d['category']) ?></small>
</td>

<td>
    <?= htmlspecialchars($d['donor_name']) ?><br>
    <small class="small-muted"><?= htmlspecialchars($d['donor_email']) ?></small>
</td>

<td>
    <?= $d['receiver_name'] 
       ? htmlspecialchars($d['receiver_name'])
       : "<span class='small-muted'>Not assigned</span>" ?>
</td>

<td>
    <?= $d['delivery_name']
       ? htmlspecialchars($d['delivery_name'])
       : "<span class='small-muted'>Not assigned</span>" ?>
</td>

<td><span class="badge <?= $statusColor ?>"><?= strtoupper($d['status']) ?></span></td>

<td>
    <?= $d['quality_verified'] == 1
        ? "<span class='badge green'>Verified</span>"
        : "<span class='badge red'>Not Verified</span>" ?>

    <?php if (!empty($d['quality_proof'])): ?>
        <br>
        <a href="../<?= htmlspecialchars($d['quality_proof']) ?>" target="_blank">View Proof</a>
    <?php endif; ?>
</td>

<td>
    <a class="sa-btn sm blue" href="donation_details.php?fid=<?= $d['Fid'] ?>">View</a>
    <?php if($d['quality_verified'] != 1): ?>
        <a class="sa-btn sm green" href="quality_verification.php?fid=<?= $d['Fid'] ?>">Verify</a>
    <?php endif; ?>
</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>

</div> <!-- END sa-container -->

<script src="assets/admin.js"></script>
