<?php
// delivery/delivery.php — Professional Delivery Dashboard (uses includes)
// Requirements: connection.php in parent folder, session contains role='delivery' and Did
session_start();

$connectionPath = __DIR__ . '/../connection.php';
if (!file_exists($connectionPath)) $connectionPath = '../connection.php';
include $connectionPath;

// Auth
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: ../signin.php");
    exit();
}

$Did = (int)($_SESSION['Did'] ?? 0);

/* ----------------------------------------------------
   CONSENT CHECK (first login)
---------------------------------------------------- */
$stmt = mysqli_prepare($connection, "SELECT consent_signed FROM delivery_persons WHERE Did=?");
mysqli_stmt_bind_param($stmt, "i", $Did);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $consent_signed);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($consent_signed == 0) {
    header("Location: delivery_consent.php");
    exit();
}

$Did = (int)($_SESSION['Did'] ?? 0);
$deliverName = htmlspecialchars($_SESSION['name'] ?? 'Delivery');
$deliverCity = htmlspecialchars($_SESSION['city'] ?? '—');

// ensure DB connection
if (!isset($connection) || !$connection) {
    http_response_code(500);
    echo "Database connection error.";
    exit();
}

// --- POST HANDLERS (pickup / delivered) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fid'])) {
    $fid = (int)$_POST['fid'];

    // verify this delivery person owns the order
    $chk = mysqli_prepare($connection, "SELECT Fid FROM food_donations WHERE Fid=? AND delivery_by=? LIMIT 1");
    mysqli_stmt_bind_param($chk, "ii", $fid, $Did);
    mysqli_stmt_execute($chk);
    $resChk = mysqli_stmt_get_result($chk);
    $found = mysqli_fetch_assoc($resChk);
    mysqli_stmt_close($chk);

    if ($found) {
        if (isset($_POST['pickup'])) {
            $u = mysqli_prepare($connection, "UPDATE food_donations SET status='picked_up', picked_at=NOW() WHERE Fid=? AND delivery_by=?");
            mysqli_stmt_bind_param($u, "ii", $fid, $Did);
            mysqli_stmt_execute($u);
            mysqli_stmt_close($u);
        } elseif (isset($_POST['delivered'])) {
            $u = mysqli_prepare($connection, "UPDATE food_donations SET status='delivered', delivered_at=NOW() WHERE Fid=? AND delivery_by=?");
            mysqli_stmt_bind_param($u, "ii", $fid, $Did);
            mysqli_stmt_execute($u);
            mysqli_stmt_close($u);
        }
    }
    header("Location: delivery.php");
    exit();
}

// --- Stats for cards ---
function get_one($connection, $sql, $types = null, $params = []) {
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) return 0;
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($res);
    mysqli_stmt_close($stmt);
    return (int)($row[0] ?? 0);
}

$totalAssigned = get_one($connection, "SELECT COUNT(*) FROM food_donations WHERE delivery_by=?", "i", [$Did]);
$totalPickedUp = get_one($connection, "SELECT COUNT(*) FROM food_donations WHERE delivery_by=? AND status='picked_up'", "i", [$Did]);
$totalDelivered = get_one($connection, "SELECT COUNT(*) FROM food_donations WHERE delivery_by=? AND status='delivered'", "i", [$Did]);
$totalPending = get_one($connection, "SELECT COUNT(*) FROM food_donations WHERE delivery_by=? AND status='assigned'", "i", [$Did]);

// --- Fetch assigned orders (list) ---
$listStmt = mysqli_prepare($connection, "
    SELECT Fid, name, address, location, phoneno, food, quantity, status, lat, lng, picked_at, delivered_at
    FROM food_donations
    WHERE delivery_by = ?
    ORDER BY Fid DESC
");
$assignedOrders = [];
if ($listStmt) {
    mysqli_stmt_bind_param($listStmt, "i", $Did);
    mysqli_stmt_execute($listStmt);
    $lr = mysqli_stmt_get_result($listStmt);
    $assignedOrders = mysqli_fetch_all($lr, MYSQLI_ASSOC) ?: [];
    mysqli_stmt_close($listStmt);
}

// active order for map (first with status 'assigned' or 'picked_up')
$activeOrder = null;
foreach ($assignedOrders as $ord) {
    if (in_array($ord['status'], ['assigned', 'picked_up'])) {
        $activeOrder = $ord;
        break;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Delivery — Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

<!-- Icon font (optional) -->
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

<!-- App CSS -->
<link rel="stylesheet" href="delivery_styles.css">

<style>
/* small fallback if delivery_styles.css is missing */
@media print { .sidebar, .main { display:block } }
</style>
</head>
<body>
<div class="app">

  <!-- include shared sidebar -->
  <?php
  // this file should exist: delivery/sidebar_delivery.php
  $sidebarFile = __DIR__ . '/sidebar_delivery.php';
  if (file_exists($sidebarFile)) {
      include $sidebarFile;
  } else {
      // fallback inline minimal sidebar if include missing
      echo '<aside class="sidebar"><div class="brand">Food <b style="color:#06C167">Donate</b></div><nav class="menu"><a href="delivery.php">Dashboard</a><a href="active_orders.php">Active</a><a href="completed_orders.php">Completed</a><a href="profile.php">Profile</a></nav><div class="logout"><a href="../logout.php">Logout</a></div></aside>';
  }
  ?>

  <main class="main">
    <?php
    // provide variables expected by topbar_delivery.php
    $pageTitle = "Dashboard";
    $deliverName = $deliverName;
    $deliverCity = $deliverCity;

    $topbarFile = __DIR__ . '/topbar_delivery.php';
    if (file_exists($topbarFile)) {
        include $topbarFile;
    } else {
        // fallback topbar
        echo "<div class='topbar'><div><h2>Delivery Dashboard</h2><div class='small'>Signed in as <strong>{$deliverName}</strong> — {$deliverCity}</div></div><div class='profile'><button onclick=\"window.location.href='profile.php'\" class='btn map'>Profile</button></div></div>";
    }
    ?>

    <!-- cards -->
    <div class="cards" role="region" aria-label="Summary cards">
      <div class="card"><h4>Total Assigned</h4><div class="val"><?= $totalAssigned ?></div><div class="small">All orders assigned to you</div></div>
      <div class="card"><h4>Picked Up</h4><div class="val"><?= $totalPickedUp ?></div><div class="small">Orders currently picked up</div></div>
      <div class="card"><h4>Pending Pickup</h4><div class="val"><?= $totalPending ?></div><div class="small">Orders awaiting pickup</div></div>
      <div class="card"><h4>Delivered</h4><div class="val"><?= $totalDelivered ?></div><div class="small">Successfully delivered</div></div>
    </div>

    <div class="row" style="margin-top:18px">
      <section>
        <h3 id="active">Orders</h3>
        <div class="orders" id="ordersList">
          <?php if (empty($assignedOrders)): ?>
            <div class="empty">No orders assigned to you yet.</div>
          <?php else: ?>
            <?php foreach ($assignedOrders as $o):
              $status = $o['status'];
              $badgeClass = ($status==='assigned' ? 'assigned' : ($status==='picked_up' ? 'picked' : 'delivered'));
            ?>
              <div class="order" data-fid="<?= (int)$o['Fid'] ?>" data-lat="<?= htmlspecialchars($o['lat'] ?? '') ?>" data-lng="<?= htmlspecialchars($o['lng'] ?? '') ?>">
                <div style="display:flex;justify-content:space-between;align-items:center">
                  <div>
                    <div style="font-weight:800"><?= htmlspecialchars($o['food']) ?> <small style="font-weight:600;color:#555">x<?= htmlspecialchars($o['quantity']) ?></small></div>
                    <div class="small"><?= htmlspecialchars($o['name']) ?> • <?= htmlspecialchars($o['phoneno']) ?></div>
                    <div class="small" style="margin-top:6px"><?= htmlspecialchars($o['address']) ?>, <?= htmlspecialchars($o['location']) ?></div>
                  </div>
                  <div style="text-align:right">
                    <div class="badge <?= $badgeClass ?>"><?= strtoupper($status) ?></div>
                    <div class="small" style="margin-top:6px">#<?= (int)$o['Fid'] ?></div>
                  </div>
                </div>

                <div class="actions" style="margin-top:10px">
                  <?php if ($status === 'assigned'): ?>
                    <form method="post" style="display:inline"><input type="hidden" name="fid" value="<?= (int)$o['Fid'] ?>"><button class="btn pick" name="pickup">Mark Picked Up</button></form>
                  <?php elseif ($status === 'picked_up'): ?>
                    <form method="post" style="display:inline"><input type="hidden" name="fid" value="<?= (int)$o['Fid'] ?>"><button class="btn deliver" name="delivered">Mark Delivered</button></form>
                  <?php else: ?>
                    <button class="btn map" disabled>Completed</button>
                  <?php endif; ?>

                  <button class="btn map" onclick="showOnMap(<?= (int)$o['Fid'] ?>)">Show on Map</button>

                  <button class="btn map" onclick="copyInfo('<?= htmlspecialchars(addslashes($o['address'])) ?>', '<?= htmlspecialchars(addslashes($o['phoneno'])) ?>')">Copy Contact</button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <h3 id="completed" style="margin-top:18px">Completed Orders</h3>
        <div class="orders">
          <?php
            $completed = array_filter($assignedOrders, function($r){ return ($r['status'] === 'delivered'); });
            if (empty($completed)) {
                echo '<div class="empty">No completed orders yet.</div>';
            } else {
                foreach ($completed as $c) {
                    echo '<div class="order"><div style="display:flex;justify-content:space-between"><div><b>'.htmlspecialchars($c['food']).'</b><div class="small">'.htmlspecialchars($c['name']).' • '.htmlspecialchars($c['phoneno']).'</div></div><div class="badge delivered">DELIVERED</div></div></div>';
                }
            }
          ?>
        </div>

      </section>

      <aside>
        <div class="map-card">
          <h3 style="margin-top:0">Active Order Map</h3>
          <?php if (!$activeOrder): ?>
            <div class="empty">No active order to display. When you have an order with status <strong>assigned</strong> or <strong>picked_up</strong>, it will appear here.</div>
          <?php else: ?>
            <div id="map"></div>
            <div class="small note" style="margin-top:8px">Map shows the active order and your live position (if you allow location access).</div>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </main>
</div>

<script>
const activeOrder = <?= json_encode($activeOrder ?: null) ?>;
const allOrders = <?= json_encode($assignedOrders) ?>;
let map = null, donorMarker = null, deliveryMarker = null, routeLine = null;

function initMap() {
  if (!activeOrder) return;
  const lat = parseFloat(activeOrder.lat);
  const lng = parseFloat(activeOrder.lng);
  const hasCoords = isFinite(lat) && isFinite(lng);

  if (!hasCoords) {
    document.getElementById('map').innerHTML = '<div style="padding:14px">Active order has no coordinates available.</div>';
    return;
  }

  map = L.map('map', { zoomControl: true }).setView([lat, lng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

  donorMarker = L.marker([lat, lng]).addTo(map).bindPopup(`<strong>${escapeHtml(activeOrder.food)}</strong><br>${escapeHtml(activeOrder.address)}`).openPopup();

  deliveryMarker = L.marker([lat, lng], {opacity:0.9}).addTo(map).bindPopup('You (live)');

  routeLine = L.polyline([[lat,lng],[lat,lng]], {color:'#06C167', weight:4, opacity:0.9}).addTo(map);

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
      updateDelivery(pos.coords.latitude, pos.coords.longitude, true);
    }, err => {}, { enableHighAccuracy: true, timeout:10000 });

    navigator.geolocation.watchPosition(pos => {
      updateDelivery(pos.coords.latitude, pos.coords.longitude, false);
    }, err => {}, { enableHighAccuracy: true, maximumAge:5000, timeout:10000 });
  }
}

function updateDelivery(lat, lng, fit=false) {
  if (!map || !donorMarker) return;
  const donorLL = donorMarker.getLatLng();
  if (!deliveryMarker) {
    deliveryMarker = L.marker([lat,lng]).addTo(map).bindPopup('You (live)');
  } else {
    deliveryMarker.setLatLng([lat,lng]);
  }
  routeLine.setLatLngs([ [donorLL.lat, donorLL.lng], [lat, lng] ]);
  if (fit) {
    const group = L.featureGroup([donorMarker, deliveryMarker]);
    map.fitBounds(group.getBounds().pad(0.25));
  }
}

function showOnMap(fid) {
  const order = allOrders.find(o => parseInt(o.Fid) === parseInt(fid));
  if (!order) return alert('Order not found.');
  if (!order.lat || !order.lng) return alert('No coordinates available for this order.');

  if (!map) {
    map = L.map('map', { zoomControl: true }).setView([parseFloat(order.lat), parseFloat(order.lng)], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    if (donorMarker) donorMarker.remove();
    donorMarker = L.marker([parseFloat(order.lat), parseFloat(order.lng)]).addTo(map).bindPopup(`${escapeHtml(order.food)}<br>${escapeHtml(order.address)}`).openPopup();
    return;
  }
  map.setView([parseFloat(order.lat), parseFloat(order.lng)], 14);
  donorMarker.setLatLng([parseFloat(order.lat), parseFloat(order.lng)]).openPopup();
}

function copyInfo(addr, phone) {
  const txt = `Address: ${addr}\nPhone: ${phone}`;
  navigator.clipboard?.writeText(txt).then(()=> alert('Copied contact to clipboard'), ()=> alert('Copy failed'));
}

function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>"'`=\/]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'}[c]; }); }

document.addEventListener('DOMContentLoaded', function(){
  initMap();
});
</script>
</body>
</html>
