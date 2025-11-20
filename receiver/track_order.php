<?php
session_start();
include "../connection.php";
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'],['receiver','super_admin'])) {
  header("Location: ../signin.php"); exit();
}
$fid = (int)($_GET['fid'] ?? 0);
$info = mysqli_fetch_assoc(mysqli_query($connection,"
  SELECT fd.Fid, fd.food, fd.quantity, dp.name as dname, dp.live_lat, dp.live_lng
  FROM food_donations fd
  LEFT JOIN delivery_persons dp ON fd.delivery_by = dp.Did
  WHERE fd.Fid = $fid
"));
?>
<!DOCTYPE html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Track #<?= $fid ?></title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
<style>#map{height:420px} .wrap{max-width:900px;margin:20px auto;padding:0 16px;font-family:Inter}</style>
</head><body>
<div class="wrap">
  <h2>Tracking Order #<?= $fid ?> — <?= htmlspecialchars($info['food']??'') ?></h2>
  <div id="map"></div>
</div>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
let map = L.map('map').setView([<?= $info['live_lat']?:'20.59' ?>, <?= $info['live_lng']?:'78.96' ?>], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
let marker = L.marker([<?= $info['live_lat']?:'20.59' ?>, <?= $info['live_lng']?:'78.96' ?>]).addTo(map);

async function tick(){
  try{
    const r = await fetch('../api/delivery_location.php?fid=<?= $fid ?>');
    const j = await r.json();
    if (j.lat && j.lng){
      marker.setLatLng([j.lat, j.lng]);
      map.setView([j.lat, j.lng]);
    }
  }catch(e){}
}
setInterval(tick, 10000);
tick();
</script>
</body></html>
