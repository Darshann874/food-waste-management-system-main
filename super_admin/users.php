<?php
session_start(); include "../connection.php"; include "components/admin_helpers.php";
if(!isset($_SESSION['role'])||$_SESSION['role']!=='super_admin'){ header("Location: ../signin.php"); exit(); }
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['email'],$_POST['role'])) {
  $email = $_POST['email']; $role = $_POST['role'];
  if (in_array($role,['super_admin','receiver','donor','delivery'])) {
    log_admin_action($connection, "update_user_role", json_encode(['target'=>$email,'new_role'=>$role]));
    $stmt = mysqli_prepare($connection,"UPDATE login SET role=? WHERE email=?"); mysqli_stmt_bind_param($stmt,"ss",$role,$email); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
  }
  header("Location: users.php"); exit();
}
$users = mysqli_query($connection,"SELECT id,name,email,gender,role FROM login ORDER BY id DESC");
include "components/sidebar.php"; include "components/topbar.php";
?>
<link rel="stylesheet" href="assets/admin.css">
<h1 class="sa-title">Manage Users</h1>
<div class="sa-card"><table class="sa-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Gender</th><th>Role</th><th>Change</th></tr></thead><tbody>
<?php while($u=mysqli_fetch_assoc($users)): ?>
<tr><td><?= (int)$u['id'] ?></td><td><?= htmlspecialchars($u['name']) ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['gender']) ?></td><td><?= htmlspecialchars($u['role']) ?></td>
<td>
<form method="post"><input type="hidden" name="email" value="<?= htmlspecialchars($u['email']) ?>"><select name="role"><option value="donor" <?= $u['role']==='donor'?'selected':'' ?>>Donor</option><option value="receiver" <?= $u['role']==='receiver'?'selected':'' ?>>Receiver</option><option value="delivery" <?= $u['role']==='delivery'?'selected':'' ?>>Delivery</option><option value="super_admin" <?= $u['role']==='super_admin'?'selected':'' ?>>Super Admin</option></select><button class="sa-btn sm" type="submit">Update</button></form>
</td></tr>
<?php endwhile; ?>
</tbody></table></div>
</div>
<script src="assets/admin.js"></script>
