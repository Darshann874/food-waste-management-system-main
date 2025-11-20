<?php
// receiver/sidebar.php
// Reusable sidebar include for receiver pages.
//
// Note: this file expects to be included from a page under receiver/
// it starts session if not started and enforces receiver auth.

if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') {
    header("Location: ../signin.php");
    exit();
}

$name = htmlspecialchars($_SESSION['name'] ?? 'Receiver');
$city = htmlspecialchars($_SESSION['city'] ?? '');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" aria-hidden="false">
  <div>
    <div class="brand">Food <b style="color:#06C167">Donate</b></div>
    <div class="small">Receiver Panel</div>
  </div>

  <div style="margin-top:12px">
    <div class="small-muted">Welcome</div>
    <div style="font-weight:800"><?= $name ?></div>
    <div class="small-muted" style="margin-top:6px">Location: <?= $city ?: '—' ?></div>
  </div>

  <nav class="menu" aria-label="Main">
    <a href="receiver.php" class="<?= $currentPage=='receiver.php' ? 'active' : '' ?>"><i class="uil uil-estate"></i> Dashboard</a>
    <a href="donations.php" class="<?= $currentPage=='donations.php' ? 'active' : '' ?>"><i class="uil uil-heart"></i> All Donations</a>
    <a href="manage_donations.php" class="<?= $currentPage=='manage_donations.php' ? 'active' : '' ?>"><i class="uil uil-truck"></i> Manage Donations</a>
    <a href="analytics.php" class="<?= $currentPage=='analytics.php' ? 'active' : '' ?>"><i class="uil uil-chart"></i> Analytics</a>
    <a href="feedback.php" class="<?= $currentPage=='feedback.php' ? 'active' : '' ?>"><i class="uil uil-comments"></i> Feedback</a>
    <a href="profile.php" class="<?= $currentPage=='profile.php' ? 'active' : '' ?>"><i class="uil uil-user"></i> Profile</a>
  </nav>

  <div style="margin-top:auto">
    <a href="../logout.php" class="menu" style="padding:0"><div style="padding:50px;color:#b91c1c"><i class="uil uil-signout"></i> Logout</div></a>
  </div>
</div>
