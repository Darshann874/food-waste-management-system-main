<?php
// super_admin/components/topbar.php
if (session_status() === PHP_SESSION_NONE) session_start();
$name = htmlspecialchars($_SESSION['name'] ?? 'Admin');
?>
<style>
.sa-topbar{margin-left:280px;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 20px;background:var(--topbar-bg);border-bottom:1px solid rgba(0,0,0,0.06);position:fixed;left:260px;right:0;top:0;z-index:40}
.sa-topbar .title{font-weight:700;color:var(--text)}
.sa-topbar .actions{display:flex;gap:12px;align-items:center}
.sa-topbar .btn{background:var(--accent);color:#fff;padding:8px 12px;border-radius:8px;border:none;cursor:pointer}
.sa-container{margin-left:280px;padding-top:100px;padding:22px}
</style>

<header class="sa-topbar" role="banner">
  <div class="title">Super Admin Panel</div>
  <div class="actions">
    <button id="themeToggle" class="theme-toggle">
      🌙 Dark Mode
    </button>
    <div style="font-weight:600;color:var(--text)"><?= $name ?></div>
    <a href="../logout.php" class="sa-logout sa-btn" style="text-decoration:none">Logout</a>
  </div>
</header>

<!-- main container open -->
<div class="sa-container">
    