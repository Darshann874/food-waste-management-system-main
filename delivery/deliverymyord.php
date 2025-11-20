<?php
// delivery/deliverymyord.php
session_start();
include("../connection.php");

// Only delivery users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: ../signin.php");
    exit();
}

$deliveryId = (int)($_SESSION['Did'] ?? 0);

// ----- Actions -----

// Mark Picked Up
if (isset($_POST['picked']) && isset($_POST['fid'])) {
    $fid = (int)$_POST['fid'];
    $sql = "UPDATE food_donations 
            SET status='picked_up', picked_at=NOW() 
            WHERE Fid=? AND delivery_by=?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $fid, $deliveryId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    // For AJAX form posts, return quick OK
    if (!empty($_POST['ajax'])) { echo "OK"; exit; }
    header("Location: deliverymyord.php"); exit;
}

// Mark Delivered
if (isset($_POST['delivered']) && isset($_POST['fid'])) {
    $fid = (int)$_POST['fid'];
    $sql = "UPDATE food_donations 
            SET status='delivered', delivered_at=NOW() 
            WHERE Fid=? AND delivery_by=?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $fid, $deliveryId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!empty($_POST['ajax'])) { echo "OK"; exit; }
    header("Location: deliverymyord.php"); exit;
}

// ----- JSON feed for auto-refresh -----
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $rows = [];
    $q = mysqli_query($connection, "
        SELECT Fid, name, phoneno, food, quantity, address, location, status, 
               COALESCE(DATE_FORMAT(picked_at,'%Y-%m-%d %H:%i'), '') AS picked_at,
               COALESCE(DATE_FORMAT(delivered_at,'%Y-%m-%d %H:%i'), '') AS delivered_at
        FROM food_donations
        WHERE delivery_by = $deliveryId
        ORDER BY Fid DESC
    ");
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
}

// Initial load (SSR)
$orders = mysqli_query($connection, "
    SELECT Fid, name, phoneno, food, quantity, address, location, status, 
           COALESCE(DATE_FORMAT(picked_at,'%Y-%m-%d %H:%i'), '') AS picked_at,
           COALESCE(DATE_FORMAT(delivered_at,'%Y-%m-%d %H:%i'), '') AS delivered_at
    FROM food_donations
    WHERE delivery_by = $deliveryId
    ORDER BY Fid DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Deliveries</title>
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
<style>
:root { --green:#06C167; --bg:#f7fff9; --text:#0b1320; --muted:#6b7280; }
*{box-sizing:border-box}
body{margin:0;background:var(--bg);font-family:Inter,system-ui;color:var(--text)}
.topbar{background:var(--green);color:#fff;padding:14px 18px;font-weight:800;letter-spacing:.3px}
.wrap{max-width:1100px;margin:26px auto;padding:0 18px}
h1{margin:8px 0 18px;color:var(--green);font-size:24px}
.card{background:#fff;border:1px solid #d7f4e6;padding:14px;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.06)}
.table{width:100%;border-collapse:separate;border-spacing:0 10px}
th{font-size:12px;color:#64748b;text-align:left;padding:10px}
td{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px;vertical-align:top}
.badge{padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;display:inline-block}
.badge.assigned{background:#dcfce7;color:#065f46}
.badge.picked_up{background:#dbeafe;color:#1e40af}
.badge.delivered{background:#e2e8f0;color:#334155}
.btn{border:none;border-radius:10px;padding:8px 12px;cursor:pointer;font-weight:700}
.btn.pick{background:#1e90ff;color:#fff}
.btn.done{background:#0a8a41;color:#fff}
.btn[disabled]{opacity:.5;cursor:not-allowed}
.meta{color:var(--muted);font-size:12px}
.flex{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.toast{position:fixed;right:18px;bottom:18px;background:#111827;color:#e5e7eb;padding:12px 14px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.3);display:none}
</style>
</head>
<body>
<div class="topbar">🚚 My Delivery Orders</div>

<div class="wrap">
  <h1>Your Active & Completed Deliveries</h1>
  <div class="card">
    <table class="table" id="ordersTable">
      <thead>
        <tr>
          <th style="min-width:70px;">Fid</th>
          <th>Food</th>
          <th>Donor / Phone</th>
          <th>Pickup Address</th>
          <th>Status</th>
          <th>Times</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="ordersBody">
        <?php if (mysqli_num_rows($orders)>0): while($o=mysqli_fetch_assoc($orders)): ?>
        <tr data-fid="<?= (int)$o['Fid'] ?>">
          <td>#<?= (int)$o['Fid'] ?></td>
          <td><?= htmlspecialchars($o['food']) ?> <div class="meta"><?= htmlspecialchars($o['quantity']) ?></div></td>
          <td><?= htmlspecialchars($o['name']) ?><div class="meta"><?= htmlspecialchars($o['phoneno']) ?></div></td>
          <td><?= htmlspecialchars($o['address']) ?><div class="meta"><?= htmlspecialchars($o['location']) ?></div></td>
          <td>
            <span class="badge <?= $o['status'] ?>"><?= htmlspecialchars($o['status']) ?></span>
          </td>
          <td>
            <div class="meta">Picked: <b><?= $o['picked_at'] ?: '-' ?></b></div>
            <div class="meta">Delivered: <b><?= $o['delivered_at'] ?: '-' ?></b></div>
          </td>
          <td>
            <?php if ($o['status']==='assigned'): ?>
              <form method="post" class="act-form">
                <input type="hidden" name="fid" value="<?= (int)$o['Fid'] ?>">
                <input type="hidden" name="ajax" value="1">
                <button class="btn pick" name="picked">Mark Picked</button>
              </form>
            <?php elseif ($o['status']==='picked_up'): ?>
              <form method="post" class="act-form">
                <input type="hidden" name="fid" value="<?= (int)$o['Fid'] ?>">
                <input type="hidden" name="ajax" value="1">
                <button class="btn done" name="delivered">Mark Delivered</button>
              </form>
            <?php else: ?>
              ✅ Done
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="7">No orders yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="toast" id="toast">Updated</div>

<script>
// tiny toast
function toast(msg){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.display = 'block';
  setTimeout(()=> t.style.display='none', 1800);
}

// AJAX submit for action buttons (no page reload)
document.querySelectorAll('.act-form').forEach(f=>{
  f.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(f);
    const res = await fetch('deliverymyord.php', { method:'POST', body:fd });
    if ((await res.text()).trim()==='OK') {
      toast('Updated');
      refreshTable();
    } else {
      toast('Saved (reload)');
      setTimeout(()=>location.reload(),600);
    }
  });
});

// Build a row element from JSON
function buildRow(o){
  const tr = document.createElement('tr');
  tr.setAttribute('data-fid', o.Fid);
  tr.innerHTML = `
    <td>#${o.Fid}</td>
    <td>${escapeHtml(o.food)} <div class="meta">${escapeHtml(o.quantity||'')}</div></td>
    <td>${escapeHtml(o.name)}<div class="meta">${escapeHtml(o.phoneno||'')}</div></td>
    <td>${escapeHtml(o.address)}<div class="meta">${escapeHtml(o.location||'')}</div></td>
    <td><span class="badge ${o.status}">${o.status}</span></td>
    <td>
      <div class="meta">Picked: <b>${o.picked_at || '-'}</b></div>
      <div class="meta">Delivered: <b>${o.delivered_at || '-'}</b></div>
    </td>
    <td>${actionCell(o)}</td>
  `;
  // attach AJAX handler if forms exist
  tr.querySelectorAll('.act-form')?.forEach(f=>{
    f.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd = new FormData(f);
      const res = await fetch('deliverymyord.php', { method:'POST', body:fd });
      if ((await res.text()).trim()==='OK') {
        toast('Updated');
        refreshTable();
      } else {
        toast('Saved (reload)');
        setTimeout(()=>location.reload(),600);
      }
    });
  });
  return tr;
}

function actionCell(o){
  if (o.status==='assigned') {
    return `
      <form method="post" class="act-form">
        <input type="hidden" name="fid" value="${o.Fid}">
        <input type="hidden" name="ajax" value="1">
        <button class="btn pick" name="picked">Mark Picked</button>
      </form>
    `;
  } else if (o.status==='picked_up') {
    return `
      <form method="post" class="act-form">
        <input type="hidden" name="fid" value="${o.Fid}">
        <input type="hidden" name="ajax" value="1">
        <button class="btn done" name="delivered">Mark Delivered</button>
      </form>
    `;
  }
  return '✅ Done';
}

// HTML escape
function escapeHtml(s){
  return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
}

// Periodic refresh (every 10s)
async function refreshTable(){
  try{
    const res = await fetch('deliverymyord.php?ajax=1');
    const data = await res.json();
    const body = document.getElementById('ordersBody');
    body.innerHTML = '';
    if (!data.length) {
      body.innerHTML = '<tr><td colspan="7">No orders yet.</td></tr>';
      return;
    }
    data.forEach(o => body.appendChild(buildRow(o)));
  }catch(e){
    // silent
  }
}
setInterval(refreshTable, 10000);
</script>
</body>
</html>
