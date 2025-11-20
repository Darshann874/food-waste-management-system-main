<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') { header("Location: ../signin.php"); exit(); }
if (!isset($_SESSION['Rid']) || empty($_SESSION['Rid'])) { header("Location: ../signin.php"); exit(); }

include "../connection.php";
$Rid = (int) $_SESSION['Rid'];
$receiverName = htmlspecialchars($_SESSION['name']);
$receiverCity = trim($_SESSION['city'] ?? '');

// mark received
if (isset($_POST['received']) && isset($_POST['fid'])) {
    $fid = (int) $_POST['fid'];
    $stmt = mysqli_prepare($connection, "UPDATE food_donations SET status='delivered', delivered_at=NOW() WHERE Fid=? AND assigned_to=?");
    mysqli_stmt_bind_param($stmt, "ii", $fid, $Rid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: manage_donations.php"); exit();
}

// assign delivery
if (isset($_POST['assign_delivery'], $_POST['fid'], $_POST['delivery_id'])) {
    $fid = (int) $_POST['fid'];
    $delivery_id = (int) $_POST['delivery_id'];

    $chk = mysqli_prepare($connection, "SELECT Fid FROM food_donations WHERE Fid=? AND assigned_to=?");
    mysqli_stmt_bind_param($chk, "ii", $fid, $Rid);
    mysqli_stmt_execute($chk);
    $valid = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
    mysqli_stmt_close($chk);

    if ($valid) {
        $u = mysqli_prepare($connection, "UPDATE food_donations SET delivery_by=?, status='assigned' WHERE Fid=? AND assigned_to=?");
        mysqli_stmt_bind_param($u, "iii", $delivery_id, $fid, $Rid);
        mysqli_stmt_execute($u);
        mysqli_stmt_close($u);
    }
    header("Location: manage_donations.php"); exit();
}

// fetch assigned
$stmt = mysqli_prepare($connection, "SELECT * FROM food_donations WHERE assigned_to=? ORDER BY Fid DESC");
mysqli_stmt_bind_param($stmt, "i", $Rid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$assigned = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// fetch delivery persons
$deliveryPersons = [];
$q = mysqli_prepare($connection, "SELECT Did, name, city FROM delivery_persons WHERE city=? ORDER BY name ASC");
mysqli_stmt_bind_param($q, "s", $receiverCity);
mysqli_stmt_execute($q);
$rs = mysqli_stmt_get_result($q);
while ($r = mysqli_fetch_assoc($rs)) $deliveryPersons[] = $r;
mysqli_stmt_close($q);

if (empty($deliveryPersons)) {
    $all = mysqli_query($connection, "SELECT Did, name, city FROM delivery_persons ORDER BY name ASC");
    while ($r = mysqli_fetch_assoc($all)) $deliveryPersons[] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Manage Donations</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

<style>

body {
    background:#f6fff9;
    margin:0;
    font-family:Inter,system-ui;
}

/* ---- SIDEBAR ---- */
.sidebar{
    width:240px;
    background:white;
    height:100vh;
    position:fixed;
    left:0; top:0;
    border-right:1px solid #e6eee6;
    padding:22px;
    display:flex;
    flex-direction:column;
    gap:20px;
}

.sidebar a{
    padding:10px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    color:#222;
    display:flex;
    align-items:center;
    gap:10px;
    transition:0.25s;
}

.sidebar a.active,
.sidebar a:hover{
    background:#e9f9ee;
    color:#06C167;
}

/* ---- TOPBAR ---- */
.topbar{
    margin-left:280px;
    padding:20px;
    background:white;
    border-bottom:1px solid #e6eee6;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.topbar .btn-profile{
    background:#06C167;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
}

/* ---- MAIN CONTAINER ---- */
.container{
    margin-left:240px;
    padding:28px;
}

.card{
    background:white;
    padding:22px;
    border-radius:14px;
    box-shadow:0 6px 22px rgba(0,0,0,0.06);
}

h3{ margin:0 0 18px 0; color:#06C167; }

/* ---- TABLE ---- */
table{
    width:100%;
    border-collapse:collapse;
}

th, td{
    padding:14px;
    text-align:left;
}

th{
    background:#f1fdf5;
    color:#06C167;
    font-size:15px;
}

tr{
    transition:.25s;
}

tr:hover{
    background:#f8fffb;
}

/* ---- BUTTONS ---- */
.btn{
    background:#06C167;
    color:white;
    padding:8px 14px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
}

.btn.alt{
    background:#ddd;
    color:#333;
}

/* ---- STATUS BADGE ---- */
.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:700;
}

.badge.assigned{ background:#e5f8ff; color:#0284c7; }
.badge.delivered{ background:#e9ffe9; color:#16a34a; }
.badge.pending{ background:#fff7e6; color:#d97706; }

/* ---- MODAL ---- */
.modal{
    position:fixed;
    top:0; left:0; right:0; bottom:0;
    background:rgba(0,0,0,.4);
    display:none;
    align-items:center;
    justify-content:center;
}

.panel{
    background:white;
    padding:25px;
    border-radius:14px;
    min-width:330px;
    box-shadow:0 6px 30px rgba(0,0,0,0.15);
    animation:fadeIn .3s ease;
}

@keyframes fadeIn {
    from { opacity:0; transform:scale(.94); }
    to { opacity:1; transform:scale(1); }
}

select{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #ddd;
}
</style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="topbar">
    <h3>Manage Donations</h3>
    <button onclick="location.href='profile.php'" class="btn-profile">Profile</button>
</div>

<div class="container">

  <div class="card">
    <h3>Your Assigned Donations</h3>

    <table>
      <tr><th>Food</th><th>Donor</th><th>Status</th><th>Delivery</th><th>Action</th></tr>

      <?php if (empty($assigned)): ?>
        <tr><td colspan="5" style="text-align:center;color:#888;padding:20px">No assigned donations</td></tr>
      <?php endif; ?>

      <?php foreach ($assigned as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['food']) ?> (<?= htmlspecialchars($a['quantity']) ?>)</td>

          <td>
            <?= htmlspecialchars($a['name']) ?>
            <br><small><?= htmlspecialchars($a['phoneno']) ?></small>
          </td>

          <td>
            <?php if ($a['status']=='delivered'): ?>
              <span class="badge delivered">Delivered</span>
            <?php elseif ($a['delivery_by']): ?>
              <span class="badge assigned">On the Way</span>
            <?php else: ?>
              <span class="badge pending">Pending</span>
            <?php endif; ?>
          </td>

          <td>
            <?= $a['delivery_by'] ? "DP#" . (int)$a['delivery_by'] : "<span style='color:#777'>Not assigned</span>" ?>
          </td>

          <td>
            <?php if (empty($a['delivery_by']) && $a['status'] !== 'delivered'): ?>
              <button class="btn alt" onclick="openAssign(<?= (int)$a['Fid'] ?>)">Assign Delivery</button>
            <?php endif; ?>

            <?php if ($a['status'] !== 'delivered'): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="fid" value="<?= (int)$a['Fid'] ?>">
                <button class="btn" name="received">Mark Received</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>

  </div>
</div>

<!-- ASSIGN DELIVERY MODAL -->
<div class="modal" id="assignModal">
  <div class="panel">
    <h3>Assign Delivery Person</h3>
    <form method="post">
      <input type="hidden" name="fid" id="assignFid">

      <select name="delivery_id" required>
        <option value="">Select Delivery Person</option>
        <?php foreach ($deliveryPersons as $p): ?>
          <option value="<?= (int)$p['Did'] ?>">
              <?= htmlspecialchars($p['name']) ?> — <?= htmlspecialchars($p['city']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <br><br>

      <button class="btn" name="assign_delivery">Assign</button>
      <button type="button" class="btn alt" onclick="closeAssign()">Cancel</button>
    </form>
  </div>
</div>

<script>
function openAssign(fid){
    document.getElementById('assignFid').value = fid;
    document.getElementById('assignModal').style.display = 'flex';
}
function closeAssign(){
    document.getElementById('assignModal').style.display = 'none';
}
</script>

</body>
</html>
