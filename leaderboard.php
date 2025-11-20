<?php
include "connection.php";

/* ---------------------------------------------------
   BADGE SYSTEM
-----------------------------------------------------*/
function badgeForPoints($pts) {
    if ($pts >= 300) return ['Legend', '#FF4D4F', '🔥'];
    if ($pts >= 200) return ['Hunger Warrior', '#8A2BE2', '🏆'];
    if ($pts >= 100) return ['Community Star', '#1E90FF', '💎'];
    if ($pts >= 50)  return ['Green Helper', '#06C167', '🌟'];
    return ['New Contributor', '#9AA7A3', '✨'];
}

/* ---------------------------------------------------
   MONTHLY LEADERBOARD
-----------------------------------------------------*/
$monthly_sql = "
    SELECT l.id AS donor_id, l.name, l.email,
           COALESCE(SUM(f.points),0) AS points
    FROM feedback f
    JOIN login l ON f.donor_id = l.id
    WHERE MONTH(f.created_at)=MONTH(CURDATE())
      AND YEAR(f.created_at)=YEAR(CURDATE())
    GROUP BY donor_id
    ORDER BY points DESC
";
$monthly_res = mysqli_query($connection, $monthly_sql);

$monthly = [];
while ($row = mysqli_fetch_assoc($monthly_res)) {
    $monthly[] = $row;
}

/* ---------------------------------------------------
   ALL-TIME LEADERBOARD  (From leaderboard table)
-----------------------------------------------------*/
$all_sql = "
    SELECT lb.donor_id, lb.total_points AS points, lb.badge,
           l.name, l.email
    FROM leaderboard lb
    JOIN login l ON l.id = lb.donor_id
    ORDER BY lb.total_points DESC
";
$all_res = mysqli_query($connection, $all_sql);

$alltime = [];
while ($row = mysqli_fetch_assoc($all_res)) {
    $alltime[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Leaderboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body{
    background:#F7FFF9;
    margin:0;
    font-family: Inter;
}

.wrap{
    max-width:1100px;
    margin:auto;
    padding:35px 20px;
}

h1{
    font-size:35px;
    font-weight:700;
    color:#0e3021;
}

/* ------------------ Toggle ------------------ */
.toggle-box{
    display:flex;
    gap:12px;
    margin-bottom:25px;
}

.toggleBtn{
    padding:10px 22px;
    border-radius:30px;
    background:#e8fdf2;
    border:none;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    color:#06C167;
    transition:.25s;
}

.toggleBtn.active{
    background:#06C167;
    color:white;
    box-shadow:0 4px 18px rgba(6,193,103,0.3);
    transform:translateY(-2px);
}

/* ------------------ Spotlight top 3 ------------------ */
.top3-container{
    margin-bottom:35px;
}

.spot-card{
    background:white;
    padding:16px;
    margin-bottom:12px;
    border-radius:16px;
    box-shadow:0 6px 25px rgba(0,0,0,0.05);
    display:flex;
    align-items:center;
    justify-content:space-between;
    border-left:6px solid #06C167;
}

.rank{
    font-size:26px;
    font-weight:800;
    color:#06C167;
}

.name{
    font-weight:700;
    font-size:17px;
}
.email{
    color:#6b8f7b;
    font-size:13px;
}

/* ------------------ Search ------------------ */
.search{
    width:100%;
    padding:15px;
    border-radius:12px;
    border:1px solid #dceee3;
    margin-bottom:22px;
}

/* ------------------ Table ------------------ */
.table-box{
    background:white;
    border-radius:16px;
    padding:8px;
    box-shadow:0 6px 25px rgba(0,0,0,0.05);
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    text-align:left;
    padding:14px;
    color:#0e3021;
    font-size:15px;
    border-bottom:1px solid #eef7f0;
}

td{
    padding:14px;
    border-bottom:1px solid #eef7f0;
}

/* Progress */
.progress{
    width:100%;
    height:10px;
    background:#e6f5ed;
    border-radius:10px;
    overflow:hidden;
}
.progress i{
    height:100%;
    display:block;
    background:linear-gradient(90deg,#06C167,#05a455);
    width:0%;
    transition:1s ease-in-out;
}

/* Badge */
.badge{
    padding:6px 12px;
    border-radius:12px;
    font-weight:700;
    font-size:13px;
}
</style>
</head>

<body>

<div class="wrap">

<h1>Leaderboard</h1>

<div class="toggle-box">
    <button id="btnMonthly" class="toggleBtn active">Monthly</button>
    <button id="btnAll" class="toggleBtn">All-Time</button>
</div>

<!-- ------------------ TOP 3 ------------------ -->
<div id="top3" class="top3-container">
<?php
$show = $monthly;
$rank = 1;
foreach(array_slice($show,0,3) as $d):
    $b = badgeForPoints($d['points']);
?>
<div class="spot-card">
    <div class="rank"><?= $rank ?></div>

    <div>
        <div class="name"><?= $d['name'] ?></div>
        <div class="email"><?= $d['email'] ?></div>
    </div>

    <div style="text-align:right;">
        <div style="font-weight:700;color:#06C167"><?= $d['points'] ?> pts</div>
        <div class="badge" style="background:<?= $b[1] ?>22;color:<?= $b[1] ?>;">
            <?= $b[2] ?> <?= $b[0] ?>
        </div>
    </div>
</div>
<?php $rank++; endforeach; ?>
</div>

<!-- ------------------ Search ------------------ -->
<input id="searchBox" class="search" placeholder="Search donor...">

<!-- ------------------ Monthly Table ------------------ -->
<div id="monthlyTable" class="table-box">
<table>
<thead>
<tr>
    <th>#</th><th>Donor</th><th>Email</th><th>Badge</th><th>Progress</th><th>Points</th>
</tr>
</thead>
<tbody>
<?php
$rank=1;
$max = count($monthly)>0 ? $monthly[0]['points'] : 1;

foreach($monthly as $r):
$badge = badgeForPoints($r['points']);
$percent = ($r['points'] / $max) * 100;
?>
<tr data-name="<?= strtolower($r['name']) ?>" data-email="<?= strtolower($r['email']) ?>">
    <td><?= $rank ?></td>
    <td><b><?= $r['name'] ?></b></td>
    <td><?= $r['email'] ?></td>
    <td><div class="badge" style="background:<?= $badge[1] ?>22;color:<?= $badge[1] ?>"><?= $badge[2] ?> <?= $badge[0] ?></div></td>
    <td><div class="progress"><i style="width:<?= $percent ?>%"></i></div></td>
    <td style="color:#06C167;font-weight:700;"><?= $r['points'] ?></td>
</tr>
<?php $rank++; endforeach; ?>
</tbody>
</table>
</div>

<!-- ------------------ All-Time Table ------------------ -->
<div id="allTable" class="table-box" style="display:none;">
<table>
<thead>
<tr>
    <th>#</th><th>Donor</th><th>Email</th><th>Badge</th><th>Progress</th><th>Points</th>
</tr>
</thead>
<tbody>
<?php
$rank=1;
$max = count($alltime)>0 ? $alltime[0]['points'] : 1;

foreach($alltime as $r):
$badge = badgeForPoints($r['points']);
$percent = ($r['points'] / $max) * 100;
?>
<tr data-name="<?= strtolower($r['name']) ?>" data-email="<?= strtolower($r['email']) ?>">
    <td><?= $rank ?></td>
    <td><b><?= $r['name'] ?></b></td>
    <td><?= $r['email'] ?></td>
    <td><div class="badge" style="background:<?= $badge[1] ?>22;color:<?= $badge[1] ?>"><?= $badge[2] ?> <?= $badge[0] ?></div></td>
    <td><div class="progress"><i style="width:<?= $percent ?>%"></i></div></td>
    <td style="color:#06C167;font-weight:700;"><?= $r['points'] ?></td>
</tr>
<?php $rank++; endforeach; ?>
</tbody>
</table>
</div>

</div>

<script>
// SEARCH
document.getElementById("searchBox").addEventListener("input", function(){
    let q = this.value.toLowerCase();
    document.querySelectorAll("tbody tr").forEach(r => {
        r.style.display = (r.dataset.name.includes(q) || r.dataset.email.includes(q)) ? "" : "none";
    });
});

// TOGGLE MONTHLY / ALL-TIME
let btnM = document.getElementById("btnMonthly");
let btnA = document.getElementById("btnAll");

btnM.onclick = () => {
    btnM.classList.add("active");
    btnA.classList.remove("active");

    document.getElementById("monthlyTable").style.display="block";
    document.getElementById("allTable").style.display="none";
};

btnA.onclick = () => {
    btnA.classList.add("active");
    btnM.classList.remove("active");

    document.getElementById("monthlyTable").style.display="none";
    document.getElementById("allTable").style.display="block";
};

// Animate bars
setTimeout(()=>{
    document.querySelectorAll(".progress i").forEach(i=>{
        i.style.width = i.style.width;
    });
},300);
</script>

</body>
</html>
