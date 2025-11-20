<?php
// super_admin/components/sidebar.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../signin.php"); exit();
}
$active = basename($_SERVER['PHP_SELF']);
?>
<style>
.sa-sidebar{width:240px;background:#0f3b28;color:#fff;position:fixed;left:0;top:0;bottom:0;padding:18px;display:flex;flex-direction:column;gap:10px;z-index:50}
.sa-brand{font-weight:800;font-size:20px;color:#eafff1;margin-bottom:6px}
.sa-menu{display:flex;flex-direction:column;gap:6px;margin-top:8px}
.sa-menu a{padding:10px;border-radius:8px;color:#e6f7ef;text-decoration:none;font-weight:600}
.sa-menu a.active, .sa-menu a:hover{background:rgba(255,255,255,0.06);color:#fff}
.sa-footer{margin-top:auto;font-size:13px;color:rgba(255,255,255,0.75)}
</style>

<aside class="sa-sidebar" aria-label="Admin sidebar">
  <div class="sa-brand">Food <span style="color:#9ff3c1">Donate</span></div>
  <nav class="sa-menu" role="navigation">
    <a href="dashboard.php" class="<?= $active==='dashboard.php'?'active':'' ?>">Dashboard</a>
    <a href="donations.php" class="<?= $active==='donations.php'?'active':'' ?>">Donations</a>
    <a href="quality_verification.php" class="<?= $active==='quality_verification.php'?'active':'' ?>">Quality Verification</a>
    <a href="receivers.php" class="<?= $active==='receivers.php'?'active':'' ?>">Receivers</a>
    <a href="delivery_persons.php" class="<?= $active==='delivery_persons.php'?'active':'' ?>">Delivery Persons</a>
    <a href="donors.php" class="<?= $active==='donors.php'?'active':'' ?>">Donors</a>
    <a href="users.php" class="<?= $active==='users.php'?'active':'' ?>">Users</a>
    <a href="tracking.php" class="<?= $active==='tracking.php'?'active':'' ?>">Live Tracking</a>
    <a href="analytics.php" class="<?= $active==='analytics.php'?'active':'' ?>">Analytics</a>
    <a href="logs.php" class="<?= $active==='logs.php'?'active':'' ?>">Logs</a>
  </nav>
  <div class="sa-footer">
    <div style="margin-bottom:6px;"><strong><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></strong></div>
    <div style="font-size:12px">Signed in as <em><?= htmlspecialchars($_SESSION['email'] ?? '') ?></em></div>
  </div>
</aside>
