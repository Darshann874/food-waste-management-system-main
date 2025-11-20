<?php
session_start();
include "connection.php";

if (!isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$msg = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donate'])) {

    // DONOR INFO
    $name     = htmlspecialchars($_SESSION['name']);
    $email    = htmlspecialchars($_SESSION['email']);

    // FORM DATA
    $food        = trim($_POST['food']);
    $type        = trim($_POST['type']);
    $category    = trim($_POST['category']);
    $quantity    = trim($_POST['quantity']);
    $address     = trim($_POST['address']);
    $location    = trim($_POST['location']);
    $phoneno     = trim($_POST['phoneno']);


    $perishability     = trim($_POST['perishability']);
    $expiry_date = $_POST['expiry_date'];

    $storage_condition = trim($_POST['storage_condition']);

    $status = 'pending';
    $quality = $_POST['quality_proof'];

    // NEW: Agreement
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    // ---- ADDRESS → LAT/LNG ----
    $fullAddress = $address . ", " . $location . ", India";
    $lat = NULL; $lng = NULL;

    $geoUrl = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($fullAddress);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $geoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    $geoResponse = curl_exec($ch);
    curl_close($ch);

    if ($geoResponse) {
        $geoData = json_decode($geoResponse, true);
        if (!empty($geoData[0])) {
            $lat = $geoData[0]['lat'];
            $lng = $geoData[0]['lon'];
        }
    }

    // ---- INSERT ----
    $stmt = mysqli_prepare($connection,
    "INSERT INTO food_donations
    (name, email, food, type, category, quantity, address, location, phoneno,
     perishability, storage_condition, status, 
     prepared_at, best_before, lat, lng, agreement_accepted)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            NOW(), DATE_ADD(NOW(), INTERVAL 6 HOUR), ?, ?, ?)"
);


  mysqli_stmt_bind_param($stmt,
    "ssssssssssssddi",
    $name, $email, $food, $type, $category, $quantity,
    $address, $location, $phoneno,
    $perishability, $storage_condition, $status,
    $lat, $lng, $agreement
);


    if (mysqli_stmt_execute($stmt)) {
        $msg = "Donation submitted successfully!";
        $success = true;
    } else {
        $msg = "DB Error: " . mysqli_error($connection);
    }

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Donate Food</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    font-family: 'Inter', sans-serif;
    background:#f7fff9;
    margin:0;
    padding:0;
}

.container {
    max-width:650px;
    margin:50px auto;
    background:#fff;
    border-radius:18px;
    padding:35px 35px 45px;
    border:1px solid #e2f5e9;
    box-shadow:0 8px 25px rgba(0,0,0,0.05);
    animation:fade .4s ease;
}

@keyframes fade {
    from{opacity:0; transform:translateY(12px);}
    to{opacity:1; transform:none;}
}

h2 {
    text-align:center;
    color:#06C167;
    margin-bottom:25px;
    font-size:30px;
    font-weight:800;
}

.input-group {
    position:relative;
    margin-bottom:20px;
}

input, select, textarea {
    width:100%;
    padding:14px;
    border-radius:12px;
    border:1px solid #b6e7c7;
    outline:none;
    font-size:16px;
    background:#fafffc;
    transition:.3s;
}

input:focus, select:focus, textarea:focus {
    border-color:#06C167;
    box-shadow:0 0 0 4px rgba(6,193,103,.15);
}

label {
    position:absolute;
    top:50%;
    left:14px;
    transform:translateY(-50%);
    background:#fafffc;
    padding:0 6px;
    color:#6b7280;
    transition:.3s;
    pointer-events:none;
}

input:focus + label,
input:not(:placeholder-shown) + label,
textarea:focus + label, 
textarea:not(:placeholder-shown) + label {
    top:-8px;
    font-size:13px;
    color:#06C167;
}

button {
    width:100%;
    padding:15px;
    border:none;
    border-radius:12px;
    background:#06C167;
    color:#fff;
    font-weight:700;
    cursor:pointer;
    font-size:17px;
    transition:.25s;
}

button:hover {
    transform:translateY(-3px);
    box-shadow:0 6px 16px rgba(6,193,103,.25);
}

.success-popup {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.4);
    display:flex;
    align-items:center;
    justify-content:center;
    backdrop-filter: blur(3px);
}

.success-card {
    background:#fff;
    padding:40px 35px;
    border-radius:18px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.15);
}

</style>
</head>

<body>

<div class="container">
  <h2>Donate Food</h2>

  <?php if(!$success && $msg): ?>
    <div style="text-align:center; color:red; margin-bottom:10px;"><?= $msg ?></div>
  <?php endif; ?>

  <form method="POST">

    <div class="input-group">
      <input type="text" name="food" placeholder=" " required>
      <label>Food Name</label>
    </div>

    <!-- QUALITY PROOF (URL or File) -->
    <div class="input-group">
      <input type="text" name="quality_proof" placeholder=" " required>
      <label>Quality Proof (Image URL)</label>
    </div>

    <div class="input-group">
      <select name="type" required>
        <option value="" disabled selected>Select Type</option>
        <option>Veg</option>
        <option>Non-Veg</option>
      </select>
    </div>

    <div class="input-group">
      <input type="text" name="category" placeholder=" " required>
      <label>Category</label>
    </div>

    <div class="input-group">
      <input type="text" name="quantity" placeholder=" " required>
      <label>Quantity</label>
    </div>

    <div class="input-group">
      <select name="perishability" required>
        <option value="" disabled selected>Perishability</option>
        <option>Hot</option>
        <option>Cold</option>
        <option>Dry</option>
      </select>
    </div>

    <div class="input-group">
      <select name="storage_condition" required>
        <option value="" disabled selected>Storage Condition</option>
        <option>Room Temperature</option>
        <option>Refrigerated</option>
        <option>Frozen</option>
      </select>
    </div>

    <div class="input-group">
      <textarea name="address" placeholder=" " rows="3" required></textarea>
      <label>Pickup Address</label>
    </div>

    <div class="input-group">
      <select name="location" required>
        <option value="" disabled selected>Select District</option>
        <option>Dehradun</option><option>Haridwar</option><option>Nainital</option>
        <option>Pauri Garhwal</option><option>Tehri Garhwal</option>
        <option>Chamoli</option><option>Pithoragarh</option><option>Almora</option>
        <option>Bageshwar</option><option>Champawat</option><option>Rudraprayag</option>
        <option>Uttarkashi</option><option>Udham Singh Nagar</option>
      </select>
    </div>

    <div class="input-group">
      <input type="text" name="phoneno" placeholder=" " required>
      <label>Contact Number</label>
    </div>
    <div class="input-group">
    <input type="datetime-local" name="expiry_date" placeholder=" " required>
    <label>Expected Expiry Date & Time</label>
</div>

    <!-- ====================== AGREEMENT START ====================== -->

    

    <div style="font-size: 13px; background: #f2f2f2; padding: 12px; border-radius: 12px; border: 1px solid #ccc; margin-top:8px;">
        <p>
            I hereby declare that the food I am donating is fresh, hygienic, properly handled, 
            and safe for human consumption. I confirm that:
        </p>
        <ul style="margin-left: 18px;">
            <li>The food has not expired and is not spoiled, stale, or contaminated.</li>
            <li>The food was stored under safe and hygienic conditions before donation.</li>
            <li>No harmful or hazardous items are mixed with the food.</li>
            <li>All ingredients used are safe and fit for consumption.</li>
        </ul>

        <p>
            I fully understand that providing unsafe, stale, expired, or hazardous food may lead to:
        </p>
        <ul style="margin-left: 18px;">
            <li>Legal action against me or my restaurant/organization.</li>
            <li>Fines and penalties under food safety regulations.</li>
            <li>Temporary or permanent suspension from this platform.</li>
            <li>Possible cancellation of my food license by authorities.</li>
        </ul>

        <p>
            By checking the box below, I accept full responsibility for the safety of the food 
            being donated. I agree that if any health issues arise due to this food, 
            I will be legally and financially liable.
        </p>
    </div>

    <br>
    <input type="checkbox" name="agreement" required>
    <label><strong>I accept all the above Terms & Conditions</strong></label>

    <!-- ====================== AGREEMENT END ====================== -->

    <br><br>
    <button name="donate" type="submit">Submit Donation</button>

  </form>
</div>


<?php if($success): ?>
<div class="success-popup" id="popup">
  <div class="success-card">
    <h3 style="color:#06C167;">Thank You!</h3>
    <p><?= $msg ?></p>
  </div>
</div>

<script>
setTimeout(()=>{
    document.getElementById('popup').style.opacity="0";
    setTimeout(()=> window.location.href="home.php", 600);
},1800);
</script>
<?php endif; ?>

</body>
</html>
