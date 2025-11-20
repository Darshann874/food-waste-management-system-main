<?php
// delivery/topbar_delivery.php
?>
<div class="topbar">
    <div>
        <h2 style="margin:0"><?= $pageTitle ?></h2>
        <div class="small">Signed in as <strong><?= $deliverName ?></strong> — <?= $deliverCity ?></div>
    </div>

    <div class="right">
        <button class="btn map" onclick="window.location.href='profile.php'">Profile</button>
    </div>
</div>
