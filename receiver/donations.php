<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') { header("Location: ../signin.php"); exit(); }
if (empty($_SESSION['Rid'])) { header("Location: ../signin.php"); exit(); }

include "../connection.php";

$Rid = (int) $_SESSION['Rid'];
$receiverName = htmlspecialchars($_SESSION['name']);
$receiverCity = trim($_SESSION['city'] ?? '');

// FILTER
$filter_category = $_GET['category'] ?? "";

// fetch pending donations in same district + filter
$sql = "
    SELECT * FROM food_donations 
    WHERE location=? 
      AND (assigned_to IS NULL OR assigned_to=0) 
      AND status='pending'
";

$params = [$receiverCity];
$types  = "s";

if ($filter_category !== "") {
    $sql .= " AND category = ?";
    $params[] = $filter_category;
    $types .= "s";
}

$sql .= " ORDER BY Fid DESC";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pending = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ACCEPT donation
if (isset($_POST['accept']) && isset($_POST['fid'])) {
    $fid = (int) $_POST['fid'];
    $u = mysqli_prepare($connection,
        "UPDATE food_donations SET assigned_to=?, status='assigned' 
         WHERE Fid=? AND (assigned_to IS NULL OR assigned_to=0)"
    );
    mysqli_stmt_bind_param($u, "ii", $Rid, $fid);
    mysqli_stmt_execute($u);
    mysqli_stmt_close($u);
    header("Location: donations.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>All Donations</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

<style>
/* --- SIDEBAR FIX (smaller width) --- */
.sidebar{
    width:200px;
    background:white;
    height:100vh;
    position:fixed;
    left:0; top:0;
    border-right:1px solid #e6eee6;
    padding:20px;
    display:flex;
    flex-direction:column;
    gap:18px;
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
}
.sidebar a.active,.sidebar a:hover{background:#e9f9ee;color:#06C167}

/* TOPBAR */
.topbar{
    margin-left:260px;
    padding:18px 25px;
    background:white;
    border-bottom:1px solid #e6eee6;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

/* PAGE CONTAINER */
.container{
    margin-left:210px;
    padding:25px;
}

/* UI Cards */
.card{
    background:white;
    padding:22px;
    border-radius:14px;
    box-shadow:0 6px 22px rgba(0,0,0,0.05);
}

/* Buttons */
.btn{
    background:#06C167;
    color:white;
    padding:8px 12px;
    border:none;
    border-radius:8px;
    font-weight:600;
    cursor:pointer;
}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}
th,td{
    padding:12px;
    border-bottom:1px solid #f1f1f1;
    text-align:left;
}
tr:hover{background:#f9fffb}
.no-data{
    text-align:center;
    padding:20px;
    color:#777;
}
.filter-box{
    margin-bottom:15px;
    display:flex;
    align-items:center;
    gap:10px;
}
.filter-box select{
    padding:10px;
    border:1px solid #d9e8df;
    border-radius:8px;
}
</style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="topbar">
    <h3>  All Donations in <?= htmlspecialchars($receiverCity) ?></h3>
    <button onclick="location.href='profile.php'" class="btn">Profile</button>
</div>

<div class="container">

    <div class="card">

        <h3 style="margin-top:0;">Pending Donations</h3>

        <!-- FILTER BY CATEGORY -->
        <form class="filter-box" method="GET">
            <strong>Filter by Category:</strong>
            <select name="category">
                <option value="">All</option>
                <?php
                $c = mysqli_query($connection,"SELECT DISTINCT category FROM food_donations ORDER BY category ASC");
                while($row = mysqli_fetch_assoc($c)){
                    $sel = ($filter_category === $row['category']) ? "selected" : "";
                    echo "<option $sel>{$row['category']}</option>";
                }
                ?>
            </select>
            <button class="btn">Apply</button>
        </form>

        <table>
            <tr>
                <th>Donor</th>
                <th>Food</th>
                <th>Quantity</th>
                <th>Address</th>
                <th>Action</th>
            </tr>

            <?php if (empty($pending)): ?>
                <tr><td colspan="5" class="no-data">No pending donations available</td></tr>
            <?php endif; ?>

            <?php foreach ($pending as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?><br><small><?= htmlspecialchars($p['phoneno']) ?></small></td>
                    <td><?= htmlspecialchars($p['food']) ?><br><small><?= htmlspecialchars($p['category']) ?></small></td>
                    <td><?= htmlspecialchars($p['quantity']) ?></td>
                    <td><?= htmlspecialchars($p['address']) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="fid" value="<?= (int)$p['Fid'] ?>">
                            <button class="btn" name="accept">Accept</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>

    </div>
</div>

</body>
</html>
