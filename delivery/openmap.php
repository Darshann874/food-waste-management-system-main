<?php
session_start();
include '../connection.php';

// ✅ Role Check
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'delivery') {
    header("Location: ../signin.php");
    exit();
}

$deliveryCity = $_SESSION['city'];

// ✅ Fetch donation pickup points in this city
$query = "SELECT name, address, location FROM food_donations 
          WHERE assigned_to IS NOT NULL AND delivery_by IS NULL AND location = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "s", $deliveryCity);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$donations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $donations[] = $row;
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delivery Map</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

<link rel="stylesheet" href="../home.css">

<style>
body {
    font-family: "Poppins", sans-serif;
}

#map-container {
    height: 420px;
    width: 90%;
    margin: 20px auto;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    animation: fadeIn 0.8s ease-in-out;
}

@keyframes fadeIn {
    from {opacity:0; transform:scale(0.98);}
    to {opacity:1; transform:scale(1);}
}

.marker-label {
    font-size: 14px;
    font-weight: 600;
    color: #111;
    text-align: center;
    margin-top: 5px;
}
</style>

</head>
<body>

<header>
    <div class="logo">Food <b style="color:#06C167;">Donate</b></div>
    <div class="hamburger">
        <div class="line"></div><div class="line"></div><div class="line"></div>
    </div>
    <nav class="nav-bar">
        <ul>
            <li><a href="delivery.php">Home</a></li>
            <li><a class="active" href="openmap.php">Map</a></li>
            <li><a href="deliverymyord.php">My Orders</a></li>
            <li><a href="../logout.php" style="color:red;font-weight:bold;">Logout</a></li>
        </ul>
    </nav>
</header>

<script>
hamburger=document.querySelector(".hamburger");
hamburger.onclick=function(){
    navBar=document.querySelector(".nav-bar");
    navBar.classList.toggle("active");
}
</script>

<h2 style="text-align:center;margin-top:10px;">📍 Delivery Location Map</h2>

<div id="map-container"></div>

<script>
let map;

// Initialize map after geolocation
navigator.geolocation.getCurrentPosition(function(position) {

    let userLat = position.coords.latitude;
    let userLng = position.coords.longitude;

    map = L.map('map-container').setView([userLat, userLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    // ✅ Delivery Boy Marker
    let userMarker = L.marker([userLat, userLng]).addTo(map);
    userMarker.bindPopup("<b>You Are Here 🚴‍♂️</b>").openPopup();

    // ✅ Donation Locations from PHP
    let donations = <?php echo json_encode($donations); ?>;

    donations.forEach(d => {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(d.address + " " + d.location)}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                let lat = data[0].lat;
                let lon = data[0].lon;

                // Green donation marker
                let marker = L.marker([lat, lon], {icon: L.icon({
                    iconUrl: "https://cdn-icons-png.flaticon.com/512/684/684908.png",
                    iconSize: [35, 35]
                })}).addTo(map);

                marker.bindPopup(`
                    <b>${d.name}</b><br>
                    📍 ${d.address}<br><br>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lon}" 
                       target="_blank" style="color:#06C167;font-weight:bold;">
                       🚗 Start Delivery Route
                    </a>
                `);
            }
        });
    });

}, function() {
    alert("⚠️ Unable to get your current location.");
});
</script>

</body>
</html>
