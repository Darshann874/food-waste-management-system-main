<?php
// receiver/receiver.php
session_start();
include "../connection.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') {
    header("Location: ../signin.php");
    exit();
}

if (!isset($_SESSION['Rid']) || empty($_SESSION['Rid'])) {
    header("Location: ../signin.php");
    exit();
}

$Rid = (int) $_SESSION['Rid'];

/* -------------------------
   CONSENT CHECK
--------------------------- */
$stmt = mysqli_prepare($connection, "SELECT consent_signed FROM receivers WHERE Rid=?");
mysqli_stmt_bind_param($stmt, "i", $Rid);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $consent_signed);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($consent_signed == 0) {
    header("Location: reciever_consent.php");
    exit();
}


include "../connection.php";

$Rid = (int) $_SESSION['Rid'];
$receiverName = htmlspecialchars($_SESSION['name']);
$receiverCity = trim($_SESSION['city'] ?? '');

// POST handlers (assign, mark received)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // mark received
    if (isset($_POST['mark_received'], $_POST['fid'])) {
        $fid = (int) $_POST['fid'];
        $stmt = mysqli_prepare($connection, "UPDATE food_donations SET status='delivered', delivered_at=NOW() WHERE Fid=? AND assigned_to=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $fid, $Rid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: receiver.php"); exit();
    }

    // assign delivery
    if (isset($_POST['assign_delivery'], $_POST['fid'], $_POST['delivery_id'])) {
        $fid = (int) $_POST['fid'];
        $delivery_id = (int) $_POST['delivery_id'];

        // verify belongs to receiver
        $chk = mysqli_prepare($connection, "SELECT Fid FROM food_donations WHERE Fid=? AND assigned_to=? LIMIT 1");
        mysqli_stmt_bind_param($chk, "ii", $fid, $Rid);
        mysqli_stmt_execute($chk);
        $res = mysqli_stmt_get_result($chk);
        $valid = mysqli_fetch_assoc($res);
        mysqli_stmt_close($chk);

        if ($valid) {
            $u = mysqli_prepare($connection, "UPDATE food_donations SET delivery_by=?, status='assigned' WHERE Fid=? AND assigned_to=?");
            mysqli_stmt_bind_param($u, "iii", $delivery_id, $fid, $Rid);
            mysqli_stmt_execute($u);
            mysqli_stmt_close($u);
        }
        header("Location: receiver.php"); exit();
    }
}// --- assign delivery person (secure)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_delivery'], $_POST['fid'], $_POST['delivery_id'])) {
    $fid = (int) $_POST['fid'];
    $delivery_id = (int) $_POST['delivery_id'];
    $Rid = (int) ($_SESSION['Rid'] ?? 0);

    // verify donation belongs to this receiver
    $chk = mysqli_prepare($connection, "SELECT Fid FROM food_donations WHERE Fid=? AND assigned_to=? LIMIT 1");
    if ($chk) {
        mysqli_stmt_bind_param($chk, "ii", $fid, $Rid);
        mysqli_stmt_execute($chk);
        $resChk = mysqli_stmt_get_result($chk);
        $found = (bool) mysqli_fetch_assoc($resChk);
        mysqli_stmt_close($chk);
    } else {
        $found = false;
    }

    if ($found) {
        $stmt = mysqli_prepare($connection, "UPDATE food_donations SET delivery_by = ?, status = 'assigned' WHERE Fid = ? AND assigned_to = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iii", $delivery_id, $fid, $Rid);
            $exec = mysqli_stmt_execute($stmt);
            if ($exec) {
                // optional: you could set a success flash in session here
            } else {
                // debug: log DB error (temporarily)
                error_log("Assign delivery failed: " . mysqli_error($connection));
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Prepare failed assign update: " . mysqli_error($connection));
        }
    } else {
        error_log("Assign delivery: donation not found or not yours (fid={$fid}, Rid={$Rid})");
    }

    // redirect back to avoid form resubmission
    header("Location: receiver.php");
    exit();
}


// summary counts
function get_count($connection, $sql, $types = "", $params = []) {
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) return 0;
    if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($res);
    mysqli_stmt_close($stmt);
    return (int)($row[0] ?? 0);
}

$totAssigned = get_count($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=?", "i", [$Rid]);
$pendingPickup = get_count($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=? AND status='assigned'", "i", [$Rid]);
$pickedCount = get_count($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=? AND status='picked_up'", "i", [$Rid]);
$deliveredCount = get_count($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=? AND status='delivered'", "i", [$Rid]);
$expiringSoon = get_count($connection, "SELECT COUNT(*) FROM food_donations WHERE assigned_to=? AND best_before IS NOT NULL AND best_before <= DATE_ADD(NOW(), INTERVAL 6 HOUR) AND status <> 'delivered'", "i", [$Rid]);

// fetch assigned donations (for table and map)
$listStmt = mysqli_prepare($connection, "
    SELECT 
        d.Fid,
        d.name,
        d.food,
        d.category,
        d.quantity,
        d.address,
        d.location,
        d.phoneno,
        d.status,
        d.prepared_at,
        d.best_before,
        d.delivery_by,
        d.lat,
        d.lng,
        d.picked_at,
        d.delivered_at,

        -- Quality fields from food_verification table
        fv.quality_verified,
        fv.quality_score,
        fv.quality_proof,
        fv.quality_reason

    FROM food_donations d
    LEFT JOIN food_verification fv 
           ON d.Fid = fv.Fid

    WHERE d.assigned_to = ?
    ORDER BY d.Fid DESC
");

$donations = [];
if ($listStmt) {
    mysqli_stmt_bind_param($listStmt, "i", $Rid);
    mysqli_stmt_execute($listStmt);
    $res = mysqli_stmt_get_result($listStmt);
    $donations = mysqli_fetch_all($res, MYSQLI_ASSOC) ?: [];
    mysqli_stmt_close($listStmt);
}

// delivery persons (prefer same city)
$deliveryPersons = [];
$dpQ = mysqli_prepare($connection, "SELECT Did, name, city FROM delivery_persons WHERE city = ? ORDER BY name ASC");
if ($dpQ) {
    mysqli_stmt_bind_param($dpQ, "s", $receiverCity);
    mysqli_stmt_execute($dpQ);
    $resDp = mysqli_stmt_get_result($dpQ);
    while ($r = mysqli_fetch_assoc($resDp)) $deliveryPersons[] = $r;
    mysqli_stmt_close($dpQ);
}
if (empty($deliveryPersons)) {
    $dpQ2 = mysqli_query($connection, "SELECT Did, name, city FROM delivery_persons ORDER BY name ASC");
    if ($dpQ2) while ($r = mysqli_fetch_assoc($dpQ2)) $deliveryPersons[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receiver — Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
<style>
:root{--green:#06C167;--muted:#6b7280;--bg:#f7fff9;--card:#fff;--accent:rgba(6,193,103,0.12)}
*{box-sizing:border-box}
body{font-family:Inter,system-ui,Arial;background:var(--bg);margin:0;color:#0f172a}
.sidebar{width:260px;background:var(--card);height:100vh;position:fixed;left:0;top:0;border-right:1px solid #e6eee6;padding:22px;display:flex;flex-direction:column;gap:18px}
.brand{font-weight:800;color:var(--green);font-size:20px}
.menu{margin-top:10px;display:flex;flex-direction:column;gap:8px}
.menu a{display:flex;align-items:center;gap:12px;padding:10px;border-radius:10px;color:#0b1a13;text-decoration:none;font-weight:600}
.menu a.active,.menu a:hover{background:var(--accent);color:var(--green)}
.topbar{margin-left:260px;padding:18px 24px;background:linear-gradient(90deg,var(--card),var(--card));display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eef7ee}
.profile-btn{background:#fff;border:1px solid #e6f6ea;padding:8px 12px;border-radius:999px;color:var(--green);font-weight:700}
.container{margin-left:260px;padding:26px}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:18px}
.card{background:var(--card);border-radius:12px;padding:16px;box-shadow:0 6px 22px rgba(8,34,22,0.04);display:flex;flex-direction:column;gap:10px;min-height:84px}
.card h4{margin:0;font-size:14px;color:var(--muted)}
.card .val{font-size:20px;font-weight:800;color:#073b26}
.split{display:grid;grid-template-columns:1fr 520px;gap:18px}
.map-card{background:var(--card);border-radius:12px;padding:12px;min-height:420px}
#map{height:100%;min-height:380px;border-radius:8px}
.table-card{background:var(--card);border-radius:12px;padding:12px;overflow:auto}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px 12px;border-bottom:1px solid #f0f6f0;text-align:left}
.badge{padding:6px 8px;border-radius:8px;font-weight:700;font-size:12px}
.badge.pending{background:#f0f8f3;color:var(--green)}
.badge.assigned{background:#e6fff1;color:#027a3e}
.badge.picked_up{background:#eef5ff;color:#0f4aa1}
.badge.delivered{background:#ebfff0;color:#0b6d3a}
.row-actions{display:flex;gap:8px;align-items:center}
.modal{position:fixed;inset:0;background:rgba(5,10,8,0.48);display:none;align-items:center;justify-content:center;z-index:99999}
.modal .panel{background:#fff;padding:18px;border-radius:12px;min-width:320px;z-index:100000}
.btn{background:var(--green);color:#fff;padding:9px 12px;border-radius:8px;border:none;cursor:pointer;font-weight:700}
.btn.alt{background:#f3f7f3;color:#034f29;border:1px solid #e6efe6}
.small-muted{color:var(--muted);font-size:13px}
.expiry-warning{background:#fff6f5;border-left:4px solid #ff7a5a;padding:10px;border-radius:6px;margin-bottom:10px}
.leaflet-container { z-index: 1; } 
.leaflet-pane, .leaflet-map-pane { z-index: 1; }
@media(max-width:1100px){ .cards{grid-template-columns:repeat(2,1fr)} .split{grid-template-columns:1fr} .sidebar{display:none} .topbar{margin-left:0} .container{margin-left:0} }
</style>
</head>
<body>

<?php include "sidebar.php"; ?>

<!-- TOPBAR -->
<div class="topbar">
  <div style="font-weight:700">Receiver Dashboard</div>
  <div class="right">
    <div style="text-align:right">
      <div class="small-muted">Signed in as</div>
      <div style="font-weight:800"><?= $receiverName ?></div>
    </div>
    <button class="profile-btn" onclick="window.location.href='profile.php'">Profile</button>
  </div>
</div>

<!-- MAIN -->
<div class="container">
  <div class="cards">
    <div class="card"><h4>Total Assigned</h4><div class="val"><?= (int)$totAssigned ?></div><div class="small-muted">All items assigned to you</div></div>
    <div class="card"><h4>Pending Pickup</h4><div class="val"><?= (int)$pendingPickup ?></div><div class="small-muted">Awaiting pickup by delivery</div></div>
    <div class="card"><h4>Picked Up</h4><div class="val"><?= (int)$pickedCount ?></div><div class="small-muted">Currently en-route</div></div>
    <div class="card"><h4>Delivered</h4><div class="val"><?= (int)$deliveredCount ?></div><div class="small-muted">Successfully delivered</div></div>
  </div>

  <?php if ($expiringSoon > 0): ?>
    <div class="expiry-warning"><strong>⚠️ <?= (int)$expiringSoon ?> donation(s) near expiry.</strong><div class="small-muted">Please prioritize pick-up or reject if unsafe.</div></div>
  <?php endif; ?>

  <div class="split">
    <div class="map-card"><div id="map"></div></div>

    <div class="table-card">
      <h3 style="margin:0 0 12px 0">Assigned Donations</h3>
      <table class="table" id="donationsTable">
        <thead><tr><th>Donor</th><th>Food</th><th>Qty</th><th>Pickup</th><th>Best Before</th><th>Status</th><th>Quality</th><th>Delivery</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($donations as $d):
            $bb = $d['best_before'] ?? null;
            $status = $d['status'] ?? 'pending';
            $statusCls = ['pending'=>'badge pending','assigned'=>'badge assigned','picked_up'=>'badge picked_up','delivered'=>'badge delivered'][$status] ?? 'badge pending';
          ?>
            <tr data-fid="<?= (int)$d['Fid'] ?>" data-lat="<?= htmlspecialchars($d['lat'] ?? '') ?>" data-lng="<?= htmlspecialchars($d['lng'] ?? '') ?>" data-address="<?= htmlspecialchars($d['address']) ?>">
              <td><?= htmlspecialchars($d['name']) ?><br><small class="small-muted"><?= htmlspecialchars($d['phoneno']) ?></small></td>
              <td><?= htmlspecialchars($d['food']) ?><br><small class="small-muted"><?= htmlspecialchars($d['category']) ?></small></td>
              <td><?= htmlspecialchars($d['quantity']) ?></td>
              <td><?= htmlspecialchars($d['location']) ?> <br><small class="small-muted"><?= htmlspecialchars($d['address']) ?></small></td>
              <td><?= $bb ? htmlspecialchars($bb) : '<span class="small-muted">N/A</span>' ?></td>
              <td><span class="<?= $statusCls ?>"><?= strtoupper($status) ?></span></td>
              <td>
                <?php if (!empty($d['quality_verified']) && $d['quality_verified'] == 1): ?>
                  <span style="color:green;font-weight:bold;">Verified ✔</span><br>
                  ⭐ <?= $d['quality_score'] ?>/5
                <?php else: ?>
                  <span style="color:red;font-weight:bold;">Not Verified ✖</span>

                  <?php if (!empty($d['quality_reason'])): ?>
                    <br><small style="color:#b91c1c;">
                      Reason: <?= htmlspecialchars($d['quality_reason']) ?>
                    </small>
                  <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($d['quality_proof'])): ?>
                  <br><a href="../<?= htmlspecialchars($d['quality_proof']) ?>" target="_blank">
                    View Proof
                  </a>
                <?php endif; ?>
              </td>



              <td><?= !empty($d['delivery_by']) ? 'D#'.(int)$d['delivery_by'] : '<span class="small-muted">Not assigned</span>' ?></td>
              <td>
                <div class="row-actions">
                  <?php if (empty($d['delivery_by']) && $status !== 'delivered'): ?>
                    <button class="btn alt" onclick="showAssignModal(<?= (int)$d['Fid'] ?>)">Assign Delivery</button>
                  <?php elseif (!empty($d['delivery_by'])): ?>
                    <span class="small-muted">D#<?= (int)$d['delivery_by'] ?> assigned</span>
                  <?php endif; ?>

                  <?php if ($status !== 'delivered'): ?>
                    <form method="post" style="display:inline"><input type="hidden" name="fid" value="<?= (int)$d['Fid'] ?>"><button class="btn" name="mark_received" type="submit">Mark Received</button></form>
                  <?php else: ?>
                    <span class="small-muted">Delivered</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($donations)): ?>
            <tr><td colspan="8" style="text-align:center;color:#6b7280;padding:18px">No assigned donations yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ASSIGN MODAL -->
<div class="modal" id="assignModal" role="dialog" aria-modal="true" style="display:none">
  <div class="panel" role="document">
    <h3>Assign Delivery Person</h3>
    <p class="small-muted">Choose a delivery person to assign this donation.</p>

    <form id="assignForm" method="post">
      <input type="hidden" name="fid" id="assignFid" value="">

      <div style="margin:12px 0">
        <select name="delivery_id" id="deliverySelect" required style="width:100%;padding:10px;border-radius:8px;border:1px solid #e9f3ec">
          <option value="">Select delivery person</option>
          <?php foreach ($deliveryPersons as $p): ?>
            <option value="<?= (int)$p['Did'] ?>"><?= htmlspecialchars($p['name']) ?> — <?= htmlspecialchars($p['city']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn alt" onclick="closeAssignModal()">Cancel</button>
        <!-- IMPORTANT: this exact name is used by PHP -->
        <button type="submit" class="btn" name="assign_delivery">Assign</button>
      </div>
    </form>
  </div>
</div>



<script>
// Map setup
const map = L.map('map', { zoomControl: true }).setView([28.6139, 77.2090], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ maxZoom: 18 }).addTo(map);

let donationMarkers = {};
document.querySelectorAll('#donationsTable tbody tr').forEach(tr=>{
  const fid = tr.dataset.fid;
  const lat = tr.dataset.lat;
  const lng = tr.dataset.lng;
  const address = tr.dataset.address || '';
  const food = tr.children[1]?.innerText?.split("\n")[0] || "";
  if (lat && lng) {
    try {
      const marker = L.marker([parseFloat(lat), parseFloat(lng)]).addTo(map)
        .bindPopup(`<strong>${food}</strong><br>${address}`);
      donationMarkers[fid] = marker;
    } catch(e){}
  }
});
const keys = Object.keys(donationMarkers);
if (keys.length > 0) {
  const group = L.featureGroup(keys.map(k => donationMarkers[k]));
  map.fitBounds(group.getBounds().pad(0.3));
}

// modal
function showAssignModal(fid){
  document.getElementById('assignFid').value = fid;
  document.getElementById('assignModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeAssignModal(){
  document.getElementById('assignModal').style.display = 'none';
  document.body.style.overflow = '';
}
document.getElementById('assignModal').addEventListener('click', function(e){ if (e.target === this) closeAssignModal(); });

// progressive submit feedback
document.getElementById('assignForm').addEventListener('submit', function(){
  const btn = this.querySelector('button[name="assign_delivery"]');
  if (btn) { btn.disabled = true; btn.innerText = 'Assigning…'; }
});
</script>

</body>
</html>
<script>
function showAssignModal(fid){
  document.getElementById('assignFid').value = fid;
  document.getElementById('deliverySelect').value = ""; // reset selection
  document.getElementById('assignModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeAssignModal(){
  document.getElementById('assignModal').style.display = 'none';
  document.body.style.overflow = '';
}
document.getElementById('assignModal')?.addEventListener('click', function(e){
  if (e.target === this) closeAssignModal();
});
</script>
