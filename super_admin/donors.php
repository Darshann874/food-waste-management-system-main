<?php
session_start(); if(!isset($_SESSION['role'])||$_SESSION['role']!=='super_admin'){ header("Location: ../signin.php"); exit();}
include "../connection.php";
$res = mysqli_query($connection,"SELECT id,name,email FROM login WHERE role='donor' ORDER BY id DESC");
include "components/sidebar.php"; include "components/topbar.php";
?>
<link rel="stylesheet" href="assets/admin.css">
<h1 class="sa-title">Donors</h1>
<div class="sa-card"><table class="sa-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th></tr></thead><tbody>
<?php while($r=mysqli_fetch_assoc($res)): ?>
<tr><td><?= (int)$r['id'] ?></td><td><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['email']) ?></td></tr>
<?php endwhile; ?>
</tbody></table></div>
</div>
<script src="assets/admin.js"></script>
