<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') { header("Location: ../signin.php"); exit(); }
if (empty($_SESSION['Rid'])) { header("Location: ../signin.php"); exit(); }
include "../connection.php";

$Rid = (int) $_SESSION['Rid'];
$receiverName = htmlspecialchars($_SESSION['name']);

// SUMMARY COUNTS
$totAssigned = (int) mysqli_fetch_row(mysqli_query($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=$Rid"))[0];
$pending     = (int) mysqli_fetch_row(mysqli_query($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=$Rid AND status='assigned'"))[0];
$enroute     = (int) mysqli_fetch_row(mysqli_query($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=$Rid AND status='picked_up'"))[0];
$delivered   = (int) mysqli_fetch_row(mysqli_query($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=$Rid AND status='delivered'"))[0];

// CATEGORY DATA
$category = mysqli_query($connection,"SELECT category, COUNT(*) AS total FROM food_donations WHERE assigned_to=$Rid GROUP BY category");
$catLabels=[];$catValues=[];
while($c=mysqli_fetch_assoc($category)){ $catLabels[]=$c['category']; $catValues[]=(int)$c['total']; }

// TREND DATA
$trend = mysqli_query($connection,"SELECT DATE(prepared_at) AS day, COUNT(*) AS total 
FROM food_donations WHERE assigned_to=$Rid GROUP BY DATE(prepared_at) ORDER BY day ASC");
$trendLabels=[];$trendValues=[];
while($t=mysqli_fetch_assoc($trend)){ $trendLabels[]=$t['day']; $trendValues[]=(int)$t['total']; }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Analytics Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

<style>
:root {
  --green:#06C167;
  --bg:#f3fff7;
  --card:#ffffff;
  --shadow:0 8px 28px rgba(0,0,0,0.08);
}
body{
  margin:0;font-family:Inter,Arial;background:var(--bg);
}
.sidebar{width:260px;background:white;height:100vh;position:fixed;left:0;top:0;
  border-right:1px solid #e6eee6;padding:22px;display:flex;flex-direction:column;gap:20px}
.sidebar a{padding:10px;border-radius:10px;text-decoration:none;font-weight:600;color:#222;display:flex;align-items:center;gap:10px}
.sidebar a.active,.sidebar a:hover{background:#e9f9ee;color:var(--green)}

.topbar{
  margin-left:260px;padding:20px;background:white;border-bottom:1px solid #e6eee6;
  display:flex;justify-content:space-between;align-items:center;
}
.topbar h3{margin:0;font-size:22px;font-weight:700;color:#333}

.container{
  margin-left:260px;padding:30px;
}

.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(230px,1fr));
  gap:20px;
}

.card{
  background:var(--card);
  padding:24px;
  border-radius:18px;
  box-shadow:var(--shadow);
  text-align:center;
  transition:.3s;
}
.card:hover{
  transform:translateY(-4px);
  box-shadow:0 14px 38px rgba(0,0,0,0.12);
}
.card .num{
  font-size:36px;
  font-weight:800;
  color:var(--green);
}
.card small{color:#5f7c6b;font-size:14px}

.chart-card{
  background:white;
  padding:28px;
  margin-top:25px;
  border-radius:20px;
  box-shadow:var(--shadow);
}

h2.section-title{
  margin:0 0 20px 0;font-size:20px;font-weight:700;color:#333;
}
</style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="topbar">
  <h3>📊 Receiver Analytics</h3>
  <div style="font-weight:700;color:#06C167">Hello, <?= $receiverName ?></div>
</div>

<div class="container">

  <!-- Summary Cards -->
  <div class="grid">
    <div class="card"><div class="num"><?= $totAssigned ?></div><small>Total Assigned</small></div>
    <div class="card"><div class="num"><?= $pending ?></div><small>Pending Pickup</small></div>
    <div class="card"><div class="num"><?= $enroute ?></div><small>On The Way</small></div>
    <div class="card"><div class="num"><?= $delivered ?></div><small>Delivered</small></div>
  </div>

  <!-- PIE CHART -->
  <div class="chart-card">
    <h2 class="section-title">Food Category Distribution</h2>
    <canvas id="pieChart" style="max-height:380px"></canvas>
  </div>

  <!-- LINE CHART -->
  <div class="chart-card">
    <h2 class="section-title">Daily Donation Trend</h2>
    <canvas id="lineChart" style="max-height:380px"></canvas>
  </div>

</div>

<script>
// DATA FROM PHP
const catLabels = <?= json_encode($catLabels) ?>;
const catValues = <?= json_encode($catValues) ?>;

const trendLabels = <?= json_encode($trendLabels) ?>;
const trendValues = <?= json_encode($trendValues) ?>;

// PIE CHART (Animated)
new Chart(document.getElementById("pieChart"), {
  type: "pie",
  data: {
    labels: catLabels,
    datasets: [{
      data: catValues,
      backgroundColor: ["#06C167","#10b981","#34d399","#6ee7b7","#99f6e4","#d1fae5"],
      borderWidth: 2,
    }]
  },
  options: {
    animation: { animateScale: true, animateRotate: true }
  }
});

// LINE CHART (Animated)
new Chart(document.getElementById("lineChart"), {
  type: "line",
  data: {
    labels: trendLabels,
    datasets: [{
      label: "Donations",
      data: trendValues,
      fill: true,
      borderColor: "#06C167",
      backgroundColor: "rgba(6,193,103,0.18)",
      tension: 0.35,
      borderWidth: 3,
      pointRadius: 4,
      pointBackgroundColor: "#06C167"
    }]
  },
  options: {
    animation: {
      duration: 1200,
      easing: 'easeOutQuart'
    },
    scales: {
      y: { beginAtZero: true }
    }
  }
});
</script>

</body>
</html>
