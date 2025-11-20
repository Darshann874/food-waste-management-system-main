<?php
session_start(); if(!isset($_SESSION['role'])||$_SESSION['role']!=='super_admin'){ header("Location: ../signin.php"); exit();}
include "../connection.php";
$res = mysqli_query($connection,"SELECT Did,name,city,email,phoneno FROM delivery_persons ORDER BY Did DESC");
include "components/sidebar.php"; include "components/topbar.php";
?>
<link rel="stylesheet" href="assets/admin.css">
<h1 class="sa-title">Delivery Persons</h1>
<div class="sa-card"><table class="sa-table"><thead><tr><th>ID</th><th>Name</th><th>City</th><th>Contact</th></tr></thead><tbody>
<?php while($r=mysqli_fetch_assoc($res)): ?>
<tr><td><?= (int)$r['Did'] ?></td><td><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['city']) ?></td><td><?= htmlspecialchars($r['phoneno']) ?><br><small class="small-muted"><?= htmlspecialchars($r['email']) ?></small></td></tr>
<?php endwhile; ?>
</tbody></table></div>
</div>
<script src="assets/admin.js"></script>
