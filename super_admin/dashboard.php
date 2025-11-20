<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') { 
    header("Location: ../signin.php"); 
    exit(); 
}

include "../connection.php";

function count_query($c,$sql){
    $r=mysqli_query($c,$sql);
    $row=mysqli_fetch_row($r);
    return (int)$row[0];
}

$totalDonations      = count_query($connection, "SELECT COUNT(*) FROM food_donations");
$pendingDonations    = count_query($connection, "SELECT COUNT(*) FROM food_donations WHERE status='pending'");
$assignedDonations   = count_query($connection, "SELECT COUNT(*) FROM food_donations WHERE status='assigned'");
$deliveredDonations  = count_query($connection, "SELECT COUNT(*) FROM food_donations WHERE status='delivered'");
$verifiedQuality     = count_query($connection, "SELECT COUNT(*) FROM food_verification WHERE quality_verified=1");
$totalReceivers      = count_query($connection, "SELECT COUNT(*) FROM receivers");
$totalDelivery       = count_query($connection, "SELECT COUNT(*) FROM delivery_persons");
$totalUsers          = count_query($connection, "SELECT COUNT(*) FROM login");

/* -----------------------------
   Recent Donations
------------------------------*/
$recent = mysqli_query($connection, "
    SELECT d.Fid, d.food, d.name, d.status, d.prepared_at AS created_at, fv.quality_verified
    FROM food_donations d
    LEFT JOIN food_verification fv ON d.Fid = fv.Fid
    ORDER BY d.Fid DESC
    LIMIT 6
");
$recentDonations = mysqli_fetch_all($recent, MYSQLI_ASSOC);

/* -----------------------------
   Monthly Graph Data
------------------------------*/
$graphRes = mysqli_query($connection, "
    SELECT MONTH(prepared_at) AS m, COUNT(*) AS c
    FROM food_donations
    GROUP BY MONTH(prepared_at)
    ORDER BY MONTH(prepared_at)
");

$months = [];
$values = [];
$monthNames = [
    1=>"January",2=>"February",3=>"March",4=>"April",5=>"May",6=>"June",
    7=>"July",8=>"August",9=>"September",10=>"October",11=>"November",12=>"December"
];

while($g=mysqli_fetch_assoc($graphRes)){
    $months[] = $monthNames[(int)$g['m']];   // Convert 1 to "January"
    $values[] = (int)$g['c'];
}
?>

<?php include "components/sidebar.php"; ?>
<?php include "components/topbar.php"; ?>

<link rel="stylesheet" href="assets/admin.css">

<h1 class="sa-title">Dashboard Overview</h1>

<div class="sa-grid-4">
  <div class="sa-card"><h4>Total Donations</h4><div class="sa-num"><?= $totalDonations ?></div></div>
  <div class="sa-card"><h4>Delivered</h4><div class="sa-num"><?= $deliveredDonations ?></div></div>
  <div class="sa-card"><h4>Pending</h4><div class="sa-num"><?= $pendingDonations ?></div></div>
  <div class="sa-card"><h4>Quality Verified</h4><div class="sa-num"><?= $verifiedQuality ?></div></div>
</div>

<div class="sa-grid-3 mt-20">
  <div class="sa-small-card"><h4>Receivers</h4><p><?= $totalReceivers ?></p></div>
  <div class="sa-small-card"><h4>Delivery Persons</h4><p><?= $totalDelivery ?></p></div>
  <div class="sa-small-card"><h4>Total Users</h4><p><?= $totalUsers ?></p></div>
</div>

<div class="sa-card mt-20">
  <h2>Monthly Donation Trend</h2>
  <canvas id="donationChart" height="90"></canvas>
</div>

<div class="sa-card mt-20">
  <h2>Recent Donations</h2>
  <table class="sa-table">
    <thead>
      <tr><th>ID</th><th>Food</th><th>Donor</th><th>Status</th><th>Quality</th></tr>
    </thead>
    <tbody>
      <?php foreach($recentDonations as $d): ?>
        <tr>
          <td>#<?= $d['Fid'] ?></td>
          <td><?= htmlspecialchars($d['food']) ?></td>
          <td><?= htmlspecialchars($d['name']) ?></td>
          <td>
            <span class="badge <?= $d['status']=='delivered'?'green':'orange' ?>">
                <?= strtoupper($d['status']) ?>
            </span>
          </td>
          <td>
            <?= $d['quality_verified']==1 
              ? '<span class="badge green">Verified</span>'
              : '<span class="badge red">Not Verified</span>' ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</div> <!-- close container -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Better modern graph
const ctx = document.getElementById('donationChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',  // ⬅ Changed to BAR chart
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: "Total Donations",
            data: <?= json_encode($values) ?>,
            backgroundColor: "rgba(6, 193, 103, 0.45)",
            borderColor: "#06C167",
            borderWidth: 2,
            borderRadius: 8,
            hoverBackgroundColor: "rgba(6,193,103,0.75)",
        }]
    },
    options: {
        responsive: true,
        animation: {
            delay: 200,
            duration: 1200,
            easing: "easeOutElastic"
        },
        plugins: {
            tooltip: {
                backgroundColor: "#0f3b28",
                padding: 12,
                titleFont: { size: 14, weight: "bold" },
                bodyFont: { size: 13 },
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<script src="assets/admin.js"></script>
