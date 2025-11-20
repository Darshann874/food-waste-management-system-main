<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../signin.php");
    exit();
}

include "../connection.php";

// Filters
$filter_action = $_GET['action'] ?? '';
$filter_email  = $_GET['email'] ?? '';

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];
$types = '';

if ($filter_action !== '') {
    $where[] = "action LIKE ?";
    $params[] = '%' . $filter_action . '%';
    $types .= 's';
}

if ($filter_email !== '') {
    $where[] = "admin_email LIKE ?";
    $params[] = '%' . $filter_email . '%';
    $types .= 's';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* Count total logs */
$countSql = "SELECT COUNT(*) FROM admin_logs $where_sql";
$stmt = mysqli_prepare($connection, $countSql);
if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$total = mysqli_fetch_row($res)[0];
mysqli_stmt_close($stmt);

/* Fetch logs */
$sql = "
    SELECT id, admin_email, action, context, ip, created_at
    FROM admin_logs
    $where_sql
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($connection, $sql);

if ($types) {
    $bind_types = $types . "ii";
    $bind_params = array_merge($params, [$per_page, $offset]);
    mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $per_page, $offset);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

include "components/sidebar.php";
include "components/topbar.php";
?>
<link rel="stylesheet" href="assets/admin.css">

<div class="sa-card">
    <h2>Admin Activity Logs</h2>

    <form method="get" style="display:flex;gap:10px;margin-bottom:15px">
        <input name="action" placeholder="Action contains..." 
               value="<?= htmlspecialchars($filter_action) ?>" 
               class="sa-input">

        <input name="email" placeholder="Admin email contains..." 
               value="<?= htmlspecialchars($filter_email) ?>" 
               class="sa-input">

        <button class="sa-btn" type="submit">Filter</button>
        <a href="logs.php" class="sa-btn orange">Reset</a>
    </form>

    <table class="sa-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Context</th>
                <th>IP</th>
                <th>When</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['admin_email']) ?></td>
                    <td><?= htmlspecialchars($r['action']) ?></td>
                    <td style="max-width:400px;word-wrap:break-word"><?= htmlspecialchars($r['context']) ?></td>
                    <td><?= htmlspecialchars($r['ip']) ?></td>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php 
    $pages = ceil($total / $per_page);
    if ($pages > 1): ?>
        <div style="margin-top:12px;display:flex;gap:8px;">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <a href="?page=<?= $p ?>&action=<?= urlencode($filter_action) ?>&email=<?= urlencode($filter_email) ?>"
                   class="sa-btn sm <?= ($p==$page?'green':'') ?>">
                   <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script src="assets/admin.js"></script>
