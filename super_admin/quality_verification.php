<?php
session_start(); if(!isset($_SESSION['role'])||$_SESSION['role']!=='super_admin'){ header("Location: ../signin.php"); exit(); }
include "../connection.php"; include __DIR__ . "/components/admin_helpers.php";
$uploadDir = __DIR__ . "/../uploads/quality/"; if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
$allowedMime=['image/jpeg','image/png','image/webp','image/gif','application/pdf']; $maxSize=6*1024*1024;
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['fid'],$_POST['action_type'])) {
    $fid=(int)$_POST['fid']; $action=($_POST['action_type']==='approve')?'approve':'reject';
    $score = isset($_POST['score'])?(int)$_POST['score']:null; $reason = trim($_POST['reason'] ?? '');
    $proof_url = null; $err=null;
    if(!empty($_FILES['proof']) && $_FILES['proof']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f=$_FILES['proof'];
        if($f['error']===UPLOAD_ERR_OK){
            if($f['size'] <= $maxSize){
                $finfo=finfo_open(FILEINFO_MIME_TYPE); $mime=finfo_file($finfo,$f['tmp_name']); finfo_close($finfo);
                if(in_array($mime,$allowedMime)){
                    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                    $safe = bin2hex(random_bytes(8))."_".time().".".$ext; $dest=$uploadDir.$safe;
                    if(move_uploaded_file($f['tmp_name'],$dest)) $proof_url = "uploads/quality/".$safe; else $err="Upload failed.";
                } else $err="Unsupported file type.";
            } else $err="File too large.";
        } else $err="File upload error.";
    }
    if(!isset($err)){
        // upsert into food_verification
        $check = mysqli_prepare($connection,"SELECT vid FROM food_verification WHERE Fid=? LIMIT 1"); mysqli_stmt_bind_param($check,"i",$fid); mysqli_stmt_execute($check); $res = mysqli_stmt_get_result($check); $exists = (bool)mysqli_fetch_assoc($res); mysqli_stmt_close($check);
        if($exists){
            $stmt=mysqli_prepare($connection,"UPDATE food_verification SET quality_verified=?, quality_score=?, quality_proof = COALESCE(?, quality_proof), quality_reason = ? WHERE Fid=?");
            $verifiedVal = $action==='approve'?1:0; mysqli_stmt_bind_param($stmt,"iissi",$verifiedVal,$score,$proof_url,$reason,$fid);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        } else {
            $stmt=mysqli_prepare($connection,"INSERT INTO food_verification (Fid, quality_verified, quality_score, quality_proof, quality_reason, verification_time) VALUES (?, ?, ?, ?, ?, NOW())");
            $verifiedVal = $action==='approve'?1:0; mysqli_stmt_bind_param($stmt,"iiiss",$fid,$verifiedVal,$score,$proof_url,$reason); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        }
        log_admin_action($connection,'verify_quality', json_encode(['fid'=>$fid,'action'=>$action,'score'=>$score,'reason'=>$reason,'proof'=>$proof_url]));
        header("Location: quality_verification.php?updated=1"); exit();
    } else { $err = $err; }
}

$filter = $_GET['filter'] ?? 'pending'; $filter_sql = $filter==='pending' ? "WHERE (fv.quality_verified IS NULL OR fv.quality_verified=0)" : "";
$sql = "SELECT d.Fid,d.name AS donor_name,d.email AS donor_email,d.food,d.quantity,d.location,d.address,d.status,fv.quality_verified,fv.quality_score,fv.quality_proof,fv.quality_reason FROM food_donations d LEFT JOIN food_verification fv ON fv.Fid=d.Fid {$filter_sql} ORDER BY d.Fid DESC LIMIT 200";
$res = mysqli_query($connection,$sql); $list = mysqli_fetch_all($res,MYSQLI_ASSOC);
include __DIR__ . "/components/sidebar.php"; include __DIR__ . "/components/topbar.php";
?>
<link rel="stylesheet" href="assets/admin.css">
<div class="sa-card">
  <h2>Quality Verification</h2>
  <?php if(!empty($err)): ?><div style="color:#b91c1c;background:#fff6f6;padding:8px;border-radius:6px"><?= htmlspecialchars($err) ?></div><?php endif;?>
  <?php if(isset($_GET['updated'])): ?><div style="color:#064e3b;background:#ecfdf5;padding:8px;border-radius:6px">Verification updated.</div><?php endif;?>
  <div style="display:flex;gap:8px;margin-bottom:12px;align-items:center">
    <form method="get"><label style="font-weight:600;margin-right:8px">Show</label><select name="filter" onchange="this.form.submit()" style="padding:8px;border-radius:6px;border:1px solid #e6f3ea"><option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending / Not Verified</option><option value="all" <?= $filter==='all'?'selected':'' ?>>All Donations</option></select></form>
    <a href="donations.php" style="margin-left:auto;color:#06C167">Go to Donations List</a>
  </div>
  <table class="sa-table"><thead><tr><th>#</th><th>Donor</th><th>Food</th><th>Location</th><th>Status</th><th>Quality</th><th>Proof</th><th>Actions</th></tr></thead><tbody>
  <?php if(empty($list)): ?><tr><td colspan="8" style="padding:18px;text-align:center;color:#6b7280">No donations found.</td></tr><?php else: foreach($list as $row): ?>
    <tr>
      <td><?= (int)$row['Fid'] ?></td>
      <td><?= htmlspecialchars($row['donor_name']) ?><br><small class="small-muted"><?= htmlspecialchars($row['donor_email']) ?></small></td>
      <td><?= htmlspecialchars($row['food']) ?> <br><small class="small-muted"><?= htmlspecialchars($row['quantity']) ?></small></td>
      <td><?= htmlspecialchars($row['location']) ?> <br><small class="small-muted"><?= htmlspecialchars($row['address']) ?></small></td>
      <td><?= htmlspecialchars($row['status']) ?></td>
      <td><?php if($row['quality_verified']==1){ ?><span class="badge green">Verified</span><br><?php if($row['quality_score']) echo "Score: ".(int)$row['quality_score']." ⭐"; } else { ?><span class="badge red">Not Verified</span><?php if(!empty($row['quality_reason'])) echo "<div style='color:#92400e;font-size:13px;margin-top:4px'>".htmlspecialchars($row['quality_reason'])."</div>"; } ?></td>
      <td><?php if(!empty($row['quality_proof'])): ?><a href="../<?= htmlspecialchars($row['quality_proof']) ?>" target="_blank">View Proof</a><?php else: ?><span class="small-muted">—</span><?php endif;?></td>
      <td>
        <a class="sa-btn sm" href="donation_details.php?fid=<?= (int)$row['Fid'] ?>">Details</a>
        <form method="post" enctype="multipart/form-data" style="display:inline-block;margin-left:6px">
          <input type="hidden" name="fid" value="<?= (int)$row['Fid'] ?>">
          <input type="hidden" name="action_type" value="approve">
          <input type="number" name="score" min="1" max="5" placeholder="Score" style="width:70px;padding:6px;margin-left:6px">
          <input type="file" name="proof" style="margin-left:6px">
          <input type="text" name="reason" placeholder="Optional note" style="padding:6px;margin-left:6px">
          <button type="submit" class="sa-btn green sm" style="margin-left:6px">Approve</button>
        </form>
        <form method="post" style="display:inline-block;margin-left:6px">
          <input type="hidden" name="fid" value="<?= (int)$row['Fid'] ?>">
          <input type="hidden" name="action_type" value="reject">
          <input type="text" name="reason" placeholder="Reason" style="padding:6px;margin-left:6px">
          <button type="submit" class="sa-btn red sm" style="margin-left:6px">Reject</button>
        </form>
      </td>
    </tr>
  <?php endforeach; endif;?>
  </tbody></table>
</div>
</div>
<script src="assets/admin.js"></script>