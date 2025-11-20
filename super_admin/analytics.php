<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../signin.php");
    exit();
}
include "../connection.php";

include "components/sidebar.php";
include "components/topbar.php";


// -------------------------------
// 1. Monthly donations count
// -------------------------------
$monthly = mysqli_query($connection, "
    SELECT MONTH(prepared_at) AS month, COUNT(*) AS total
    FROM food_donations
    GROUP BY MONTH(prepared_at)
");
$months = [];
$monthValues = [];
while ($m = mysqli_fetch_assoc($monthly)) {
    $months[] = $m['month'];
    $monthValues[] = $m['total'];
}

// -------------------------------
// 2. Category Distribution
// -------------------------------
$catRes = mysqli_query($connection, "
    SELECT category, COUNT(*) AS total
    FROM food_donations
    GROUP BY category
");
$catNames = [];
$catValues = [];
while ($c = mysqli_fetch_assoc($catRes)) {
    $catNames[] = $c['category'];
    $catValues[] = $c['total'];
}

// -------------------------------
// 3. Status Distribution
// -------------------------------
$statusRes = mysqli_query($connection, "
    SELECT status, COUNT(*) AS total
    FROM food_donations
    GROUP BY status
");
$statuses = [];
$statusValues = [];
while ($s = mysqli_fetch_assoc($statusRes)) {
    $statuses[] = $s['status'];
    $statusValues[] = $s['total'];
}

// -------------------------------
// 4. Top 5 Donors
// -------------------------------
$donorRes = mysqli_query($connection, "
    SELECT name, COUNT(*) AS donations
    FROM food_donations
    GROUP BY name
    ORDER BY donations DESC
    LIMIT 5
");
$donorNames = [];
$donorCounts = [];
while ($d = mysqli_fetch_assoc($donorRes)) {
    $donorNames[] = $d['name'];
    $donorCounts[] = $d['donations'];
}

// -------------------------------
// 5. Monthly total food quantity
// -------------------------------
$qtyRes = mysqli_query($connection, "
    SELECT MONTH(prepared_at) AS month, SUM(quantity) AS qty
    FROM food_donations
    GROUP BY MONTH(prepared_at)
");
$qtyMonths = [];
$qtyValues = [];
while ($q = mysqli_fetch_assoc($qtyRes)) {
    $qtyMonths[] = $q['month'];
    $qtyValues[] = $q['qty'];
}

// -------------------------------
// 6. Receiver demand heatmap
// -------------------------------
$recvRes = mysqli_query($connection, "
    SELECT r.name, COUNT(fd.Fid) AS received
    FROM receivers r
    LEFT JOIN food_donations fd ON fd.assigned_to = r.Rid
    GROUP BY r.Rid
");
$receivers = mysqli_fetch_all($recvRes, MYSQLI_ASSOC);

// -------------------------------
// 7. Avg delivery time
// -------------------------------
$avgTimeRes = mysqli_query($connection, "
    SELECT AVG(TIMESTAMPDIFF(MINUTE, picked_at, delivered_at)) AS avg_minutes
    FROM food_donations
    WHERE picked_at IS NOT NULL AND delivered_at IS NOT NULL
");
$avgDelivery = (int) mysqli_fetch_assoc($avgTimeRes)['avg_minutes'];

// -------------------------------
// 8. Quality verification stats
// -------------------------------
$qv = mysqli_query($connection, "
    SELECT 
        SUM(quality_verified = 1) AS verified,
        SUM(quality_verified = 0) AS not_verified
    FROM food_verification
");
$qvs = mysqli_fetch_assoc($qv);
$verified = $qvs['verified'];
$notVerified = $qvs['not_verified'];

?>

<link rel="stylesheet" href="assets/admin.css">

<style>
.analytics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap:20px;
}

.analytics-card {
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(12px);
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    animation: fadeUp .6s;
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}

.heatmap td {
    padding:10px;
    border-radius:8px;
    color:#fff;
    font-weight:600;
}

</style>


<h1 class="sa-title">Analytics Dashboard</h1>

<div class="analytics-grid">

    <!-- Monthly Donations -->
    <div class="analytics-card">
        <h2>Monthly Donation Trend</h2>
        <canvas id="monthlyChart" height="130"></canvas>
    </div>

    <!-- Category Pie -->
    <div class="analytics-card">
        <h2>Food Category Distribution</h2>
        <canvas id="categoryChart" height="130"></canvas>
    </div>

    <!-- Status Chart -->
    <div class="analytics-card">
        <h2>Status Breakdown</h2>
        <canvas id="statusChart" height="130"></canvas>
    </div>

    <!-- Top Donors -->
    <div class="analytics-card">
        <h2>Top 5 Donors</h2>
        <canvas id="donorChart" height="130"></canvas>
    </div>

    <!-- Quantity Chart -->
    <div class="analytics-card">
        <h2>Monthly Food Quantity</h2>
        <canvas id="qtyChart" height="130"></canvas>
    </div>

    <!-- Avg delivery -->
    <div class="analytics-card">
        <h2>Delivery Performance</h2>
        <p style="font-size:40px;font-weight:800;color:#06C167;">
            <?= $avgDelivery ?> <span style="font-size:20px;color:#555;">min avg</span>
        </p>
    </div>

    <!-- Quality -->
    <div class="analytics-card">
        <h2>Quality Verification</h2>
        <canvas id="qualityChart" height="130"></canvas>
    </div>

    <!-- Receiver Heatmap -->
    <div class="analytics-card">
        <h2>Receiver Demand Heatmap</h2>
        <table class="heatmap">
            <?php foreach($receivers as $r): 
                $value = (int)$r['received'];
                $color = $value >= 10 ? "#065f46"
                       : ($value >= 5 ? "#0d9488"
                       : "#60a5fa"); 
            ?>
            <tr>
                <td style="background:<?= $color ?>;">
                    <?= htmlspecialchars($r['name']) ?> — <?= $value ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById("monthlyChart"), {
  type:"line",
  data:{
    labels: <?= json_encode($months) ?>,
    datasets:[{
      label:"Donations",
      data: <?= json_encode($monthValues) ?>,
      borderWidth:2,
      borderColor:"#06C167",
      tension:.4,
      fill:true,
      backgroundColor:"rgba(6,193,103,0.15)"
    }]
  }
});

new Chart(document.getElementById("categoryChart"), {
  type:"pie",
  data:{
    labels: <?= json_encode($catNames) ?>,
    datasets:[{
      data: <?= json_encode($catValues) ?>,
      backgroundColor:["#06C167","#2563eb","#f59e0b","#dc2626","#7c3aed"]
    }]
  }
});

new Chart(document.getElementById("statusChart"), {
  type:"doughnut",
  data:{
    labels: <?= json_encode($statuses) ?>,
    datasets:[{
      data: <?= json_encode($statusValues) ?>,
      backgroundColor:["#06C167","#f59e0b","#2563eb","#7c3aed"]
    }]
  }
});

new Chart(document.getElementById("donorChart"), {
  type:"bar",
  data:{
    labels: <?= json_encode($donorNames) ?>,
    datasets:[{
      label:"Donations",
      data: <?= json_encode($donorCounts) ?>,
      backgroundColor:"#06C167"
    }]
  }
});

new Chart(document.getElementById("qtyChart"), {
  type:"bar",
  data:{
    labels: <?= json_encode($qtyMonths) ?>,
    datasets:[{
      label:"Quantity",
      data: <?= json_encode($qtyValues) ?>,
      backgroundColor:"#2563eb"
    }]
  }
});

new Chart(document.getElementById("qualityChart"), {
  type:"doughnut",
  data:{
    labels:["Verified","Not Verified"],
    datasets:[{
      data:[<?= $verified ?>, <?= $notVerified ?>],
      backgroundColor:["#06C167","#dc2626"]
    }]
  }
});
</script>

</div> <!-- end .sa-container -->
<script src="assets/admin.js"></script>
    