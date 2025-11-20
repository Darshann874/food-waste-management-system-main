<?php
// super_admin/donation_details.php (REDESIGNED UI)
// Requires: session started, ../connection.php present, components/sidebar.php & components/topbar.php present

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../signin.php");
    exit();
}

include "../connection.php";

// Ensure fid provided
if (!isset($_GET['fid']) || empty($_GET['fid'])) {
    die("Donation ID missing.");
}
$Fid = (int) $_GET['fid'];

// Load donation + joins
$sql = "SELECT d.*, r.name AS receiver_name, dp.name AS delivery_name, fv.quality_verified, fv.quality_score, fv.quality_proof, fv.quality_reason
        FROM food_donations d
        LEFT JOIN receivers r ON r.Rid = d.assigned_to
        LEFT JOIN delivery_persons dp ON dp.Did = d.delivery_by
        LEFT JOIN food_verification fv ON fv.Fid = d.Fid
        WHERE d.Fid = $Fid
        LIMIT 1"; 
$res = mysqli_query($connection, $sql);
$donation = mysqli_fetch_assoc($res);
if (!$donation) die("Donation not found.");

// Fetch receivers & delivery persons (for select lists)
$receivers = mysqli_query($connection, "SELECT Rid, name, location FROM receivers ORDER BY name ASC");
$deliveryPersons = mysqli_query($connection, "SELECT Did, name, city FROM delivery_persons ORDER BY name ASC");

// Possible statuses
$statusOptions = ['pending','assigned','picked_up','delivered'];

include "components/sidebar.php";
include "components/topbar.php";
?>
<link rel="stylesheet" href="assets/admin.css">
<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
<style>
/* Additional styles specific to donation_details view */
.sa-container {
      margin-left: 170px;
      padding-top: 30px !important;  /* moves the title down */
}
.sa-page {
  display: grid;
  grid-template-columns: 1fr 420px;
  gap: 20px;
  align-items: start;
}
.sa-title {
      margin-top: 20px !important;
      display: block;
}
/* Card header */
.detail-card {
  background: var(--card, #fff);
  border-radius: 12px;
  padding: 22px;
  box-shadow: 0 10px 30px rgba(8,34,22,0.04);
  position: relative;
  overflow: hidden;
  transition: transform .28s ease, box-shadow .28s ease;
}
.detail-card:hover { transform: translateY(-6px); box-shadow: 0 20px 50px rgba(8,34,22,0.07); }

.detail-title { font-size:22px; margin:0 0 14px; color:var(--text,#07221a); }

/* Info list */
.info-row { display:flex; gap:10px; margin:10px 0; align-items:flex-start; }
.info-row b { width:140px; color:var(--text,#062e20); }
.small-muted { color: #6b7280; }

/* Forms */
.sa-input, select, textarea {
  width:100%; padding:10px; border-radius:8px; border:1px solid #e6f3ea; background:#fff;
  font-size:14px; box-sizing:border-box;
}
.form-actions { display:flex; gap:10px; margin-top:12px; }

/* Map & quality */
.side-column { display:flex; flex-direction:column; gap:18px; }
.map-card { height:300px; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(8,34,22,0.04); }
.quality-card {
  padding:18px; border-radius:12px; background:linear-gradient(180deg, rgba(255,255,255,0.7), rgba(255,255,255,0.9));
  box-shadow: 0 10px 30px rgba(6,193,103,0.06); display:flex; flex-direction:column; gap:10px;
}
.quality-status { font-weight:700; color:#d23a4a; padding:6px 10px; border-radius:8px; display:inline-block; background:#fff1f2; color:#9f1239; }

/* responsive */
@media (max-width:1000px) {
  .sa-page { grid-template-columns: 1fr; }
  .side-column .map-card { height:220px; }
}
</style>

<div class="sa-container">
  <h1 class="sa-title">Donation #<?= htmlspecialchars($donation['Fid']) ?></h1>

  <div class="sa-page">

    <!-- LEFT: Details & forms -->
    <div>
      <div class="detail-card">
        <h2 class="detail-title">Donation Information</h2>

        <div class="info-row"><b>Food:</b> <div><?= htmlspecialchars($donation['food']) ?></div></div>
        <div class="info-row"><b>Category:</b> <div><?= htmlspecialchars($donation['category']) ?></div></div>
        <div class="info-row"><b>Quantity:</b> <div><?= htmlspecialchars($donation['quantity']) ?></div></div>
        <div class="info-row"><b>Location:</b> <div><?= htmlspecialchars($donation['location']) ?></div></div>
        <div class="info-row"><b>Address:</b> <div><?= htmlspecialchars($donation['address']) ?></div></div>
        <div class="info-row"><b>Status:</b> <div><span class="badge <?= ($donation['status']=='delivered'?'green':'orange') ?>"><?= strtoupper(htmlspecialchars($donation['status'])) ?></span></div></div>
        <div class="info-row"><b>Created At:</b> <div class="small-muted"><?= htmlspecialchars($donation['prepared_at'] ?? $donation['date'] ?? '') ?></div></div>

        <hr style="margin:18px 0;border:none;border-top:1px solid #eef7f0">

        <h3 style="margin:0 0 10px">Donor Information</h3>
        <div class="info-row"><b>Name:</b> <div><?= htmlspecialchars($donation['name']) ?></div></div>
        <div class="info-row"><b>Email:</b> <div><?= htmlspecialchars($donation['email']) ?></div></div>
        <div class="info-row"><b>Phone:</b> <div><?= htmlspecialchars($donation['phoneno']) ?></div></div>

      </div>

      <!-- Receiver form -->
      <div class="detail-card" style="margin-top:18px">
        <h3 class="detail-title">Assign / Update Receiver</h3>
        <form method="post" action="update_receiver.php">
          <input type="hidden" name="fid" value="<?= (int)$donation['Fid'] ?>">
          <select name="receiver_id" class="sa-input">
            <option value="">— Select Receiver —</option>
            <?php mysqli_data_seek($receivers, 0); while($r = mysqli_fetch_assoc($receivers)): ?>
              <option value="<?= (int)$r['Rid'] ?>" <?= ($donation['assigned_to'] == $r['Rid']) ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?> — <?= htmlspecialchars($r['location']) ?></option>
            <?php endwhile; ?>
          </select>
          <div class="form-actions">
            <button class="sa-btn green sa-btn sm" type="submit">Update Receiver</button>
            <a class="sa-btn sm" href="donations.php">Back to donations</a>
          </div>
        </form>
      </div>

      <!-- Delivery form -->
      <div class="detail-card" style="margin-top:18px">
        <h3 class="detail-title">Assign Delivery Person</h3>
        <form method="post" action="update_delivery.php">
          <input type="hidden" name="fid" value="<?= (int)$donation['Fid'] ?>">
          <select name="delivery_id" class="sa-input">
            <option value="">— Select Delivery Person —</option>
            <?php mysqli_data_seek($deliveryPersons, 0); while($dp = mysqli_fetch_assoc($deliveryPersons)): ?>
              <option value="<?= (int)$dp['Did'] ?>" <?= ($donation['delivery_by'] == $dp['Did']) ? 'selected' : '' ?>><?= htmlspecialchars($dp['name']) ?> — <?= htmlspecialchars($dp['city']) ?></option>
            <?php endwhile; ?>
          </select>
          <div class="form-actions">
            <button class="sa-btn blue sa-btn sm" type="submit">Update Delivery</button>
            <a class="sa-btn orange sa-btn sm" href="manage_donations.php">Manage</a>
          </div>
        </form>
      </div>

      <!-- Status change -->
      <div class="detail-card" style="margin-top:18px">
        <h3 class="detail-title">Change Donation Status</h3>
        <form method="post" action="update_status.php">
          <input type="hidden" name="fid" value="<?= (int)$donation['Fid'] ?>">
          <select name="status" class="sa-input">
            <?php foreach($statusOptions as $s): ?>
              <option value="<?= htmlspecialchars($s) ?>" <?= ($donation['status'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-actions">
            <button class="sa-btn orange sa-btn sm" type="submit">Update Status</button>
            <?php if($donation['status'] !== 'delivered'): ?>
              <button formaction="mark_delivered.php" formmethod="post" class="sa-btn green sa-btn sm" type="submit">Mark Delivered</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- RIGHT: Map + Quality -->
    <aside class="side-column">
      <div class="map-card" id="map"></div>

      <div class="quality-card">
        <h3 style="margin:0">Quality Information</h3>
        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <div style="font-size:13px;color:#6b7280">Status</div>
              <?php if ($donation['quality_verified'] == 1): ?>
                <div class="quality-status" style="background:#ecfdf5;color:#065f46;">Verified</div>
              <?php else: ?>
                <div class="quality-status">Not Verified</div>
              <?php endif; ?>
            </div>
            <div style="text-align:right">
              <?php if (!empty($donation['quality_score'])): ?>
                <div style="font-weight:800;color:#064e3b; font-size:18px;"><?= (int)$donation['quality_score'] ?> ⭐</div>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($donation['quality_reason'])): ?>
            <div style="margin-top:10px;color:#243a2f"><strong>Reason</strong><div class="small-muted"><?= htmlspecialchars($donation['quality_reason']) ?></div></div>
          <?php endif; ?>

          <?php if (!empty($donation['quality_proof'])): ?>
            <div style="margin-top:10px"><a class="sa-btn sm" target="_blank" href="../<?= htmlspecialchars($donation['quality_proof']) ?>">View Proof</a></div>
          <?php endif; ?>
        </div>

        <div style="margin-top:12px">
          <a class="sa-btn green" href="quality_verification.php?fid=<?= (int)$donation['Fid'] ?>">Verify / Update Quality</a>
        </div>
      </div>
    </aside>

  </div> <!-- .sa-page -->
</div> <!-- .sa-container -->

<!-- Leaflet + init -->
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
(function(){
  // prevent multiple initializations if included earlier
  const lat = <?= json_encode($donation['lat'] ?? null) ?>;
  const lng = <?= json_encode($donation['lng'] ?? null) ?>;
  const hasCoords = lat && lng && !isNaN(parseFloat(lat)) && !isNaN(parseFloat(lng));
  const mapEl = document.getElementById('map');

  if (!hasCoords) {
    mapEl.innerHTML = '<div style="padding:18px;color:#6b7280">No location data available for this donation.</div>';
    return;
  }

  // initialize map
  const map = L.map('map', { zoomControl:true, attributionControl:false }).setView([lat,lng], 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    tileSize: 512,
    zoomOffset: -1
  }).addTo(map);

  // marker with popup (donor name)
  L.marker([lat,lng]).addTo(map).bindPopup("Pickup: <?= htmlspecialchars(addslashes($donation['location'] ?: $donation['address'] ?: 'Pickup location')) ?>");

  // ensure map invalidation once displayed (fixes small-size rendering issues)
  setTimeout(()=>{ map.invalidateSize(); }, 300);
})();
</script>
