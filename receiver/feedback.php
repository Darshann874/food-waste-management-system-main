<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') { 
    header("Location: ../signin.php"); 
    exit(); 
}

if (!isset($_SESSION['Rid']) || empty($_SESSION['Rid'])) { 
    header("Location: ../signin.php"); 
    exit(); 
}

include "../connection.php";
include "../sentiment.php";

$receiver_id = $_SESSION['Rid'];
$message = "";

// fetch delivered donations not yet reviewed
$orders = mysqli_query($connection, "
    SELECT fd.Fid, fd.name AS donor_name, fd.email AS donor_email
    FROM food_donations fd
    WHERE fd.assigned_to = $receiver_id 
      AND fd.status = 'delivered'
      AND fd.Fid NOT IN (SELECT donation_id FROM feedback)
");

if (isset($_POST['submit_feedback'])) {

    $donation_id = $_POST['donation_id'];
    $donor_email = $_POST['donor_email'];
    $feedback_text = trim($_POST['feedback']);

    if ($feedback_text === "") {
        $message = "Feedback cannot be empty.";
    } else {

        $sentiment = getSentiment($feedback_text);
        $points = ($sentiment == 'positive') ? 5 : (($sentiment == 'neutral') ? 2 : 0);

        $insertFeedback = "
            INSERT INTO feedback (ngo_id, donor_id, donation_id, feedback_text, sentiment, points, created_at)
            VALUES ($receiver_id,
            (SELECT id FROM login WHERE email='$donor_email'),
            $donation_id,
            '$feedback_text',
            '$sentiment',
            $points,
            NOW()
        )";
        mysqli_query($connection, $insertFeedback);
        mysqli_query($connection, "
            INSERT INTO notifications(donor_email, message)
            VALUES ('$donor_email', 'You earned $points points for your food donation!')
        ");

        /* LEADERBOARD UPDATE (Improved Badge System) */
        mysqli_query($connection, "
            INSERT INTO leaderboard(donor_id, total_points, badge)
            VALUES (
                (SELECT id FROM login WHERE email='$donor_email'),
                $points,
                'New Contributor'
            )
            ON DUPLICATE KEY UPDATE 
                total_points = total_points + $points,
                badge = CASE
                    WHEN total_points + $points >= 300 THEN 'Legend'
                    WHEN total_points + $points >= 200 THEN 'Hunger Warrior'
                    WHEN total_points + $points >= 100 THEN 'Community Star'
                    WHEN total_points + $points >= 50  THEN 'Green Helper'
                    ELSE 'New Contributor'
                END
        ");


        $message = "Feedback submitted successfully!";
        header("Refresh: 1");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Feedback</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

<style>

/* GLOBAL */
body{
    font-family: Inter, system-ui;
    background:#f6fff9;
    margin:0;
}

/* SIDEBAR */
.sidebar{
    width:220px;                         /* REDUCED WIDTH */
    background:white;
    height:100vh;
    position:fixed;
    left:0;top:0;
    border-right:1px solid #e6eee6;
    padding:20px;
    display:flex;
    flex-direction:column;
    gap:18px;
}
.sidebar a{
    padding:10px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    color:#222;
    display:flex;align-items:center;gap:10px;
}
.sidebar a.active,.sidebar a:hover{
    background:#e9f9ee;
    color:#06C167;
}

/* TOPBAR */
.topbar{
    margin-left:260px;
    padding:20px;
    background:white;
    border-bottom:1px solid #e6eee6;
    font-size:22px;
    font-weight:600;
}

/* CONTENT */
.container{
    margin-left:220px;
    padding:35px;
}

/* CARD */
.card{
    max-width:900px;
    margin:auto;
    background:white;
    padding:35px;
    border-radius:18px;
    box-shadow:0 6px 24px rgba(0,0,0,0.06);
    animation:fade .4s ease;
}

@keyframes fade{
    from{opacity:0;transform:translateY(12px);}
    to{opacity:1;transform:none;}
}

/* FORM ELEMENTS */
select, textarea{
    width:100%;
    padding:14px;
    border-radius:10px;
    border:1px solid #dcefe2;
    outline:none;
    background:#fafffc;
    margin-bottom:18px;
    font-size:15px;
    transition:.25s;
}
select:focus, textarea:focus{
    border-color:#06C167;
    box-shadow:0 0 0 3px rgba(6,193,103,.25);
}

button{
    width:100%;
    padding:14px;
    background:#06C167;
    color:white;
    border:none;
    border-radius:12px;
    font-size:17px;
    font-weight:700;
    cursor:pointer;
    transition:.25s;
}
button:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 24px rgba(6,193,103,.25);
}

.msg{
    padding:12px;
    border-radius:10px;
    text-align:center;
    background:#e8ffe8;
    color:#0a7a0a;
    margin-bottom:15px;
}

/* LOADING SPINNER */
.loader{
    display:none;
    border:4px solid #e0e0e0;
    border-top:4px solid #06C167;
    border-radius:50%;
    width:38px;height:38px;
    animation:spin 1s linear infinite;
    margin:auto;
}
@keyframes spin{100%{transform:rotate(360deg);}}

/* SUCCESS CHECKMARK */
.success-box{
    display:none;
    text-align:center;
    margin-bottom:20px;
}
.success-check{
    font-size:48px;
    color:#06C167;
    animation:pop .5s ease-out;
}
@keyframes pop{
    0%{transform:scale(0);}
    80%{transform:scale(1.2);}
    100%{transform:scale(1);}
}

</style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="topbar">Feedback</div>

<div class="container">
  <div class="card">

    <!-- SUCCESS ANIMATION -->
    <div class="success-box" id="successBox">
      <i class="uil uil-check-circle success-check"></i>
      <p style="color:#06C167;font-weight:600;margin-top:-5px">Feedback Submitted!</p>
    </div>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2 style="margin-top:0;color:#06C167">Submit Feedback</h2>

    <form method="post" id="feedbackForm">

      <label><b>Select Delivered Donation</b></label>
      <select name="donation_id" required>
        <option value="">Select delivered order</option>
        <?php while ($row = mysqli_fetch_assoc($orders)): ?>
            <option value="<?= $row['Fid'] ?>" 
                    data-email="<?= $row['donor_email'] ?>">
                <?= $row['donor_name'] ?> — Order #<?= $row['Fid'] ?>
            </option>
        <?php endwhile; ?>
      </select>

      <input type="hidden" id="donor_email" name="donor_email">

      <label><b>Your Feedback</b></label>
      <textarea name="feedback" rows="6" placeholder="Write your feedback..." required></textarea>

      <!-- LOADING INDICATOR -->
      <div class="loader" id="loader"></div>

      <button name="submit_feedback" type="submit" id="submitBtn">
        Submit Feedback
      </button>

    </form>

  </div>
</div>

<script>
document.querySelector("select[name='donation_id']").addEventListener("change", function(){
    let selected = this.options[this.selectedIndex];
    document.getElementById("donor_email").value = selected.getAttribute("data-email");
});

const form     = document.getElementById("feedbackForm");
const loader   = document.getElementById("loader");
const btn      = document.getElementById("submitBtn");
const successB = document.getElementById("successBox");

form.addEventListener("submit", function(){
    loader.style.display = "block";       // Show loader
    btn.style.opacity = ".5";
    btn.style.pointerEvents = "none";
    
    setTimeout(() => {
        successB.style.display = "block"; // Show success animation
    }, 800);
});
</script>

</body>
</html>

