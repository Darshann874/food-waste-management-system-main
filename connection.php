<?php
$connection = mysqli_connect("localhost", "root", "", "demo", 3306);

if (!$connection) {
    die("❌ Connection failed: " . mysqli_connect_error());
} 
?>
