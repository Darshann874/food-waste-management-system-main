<?php
include 'connection.php';

$email = $_POST['email'];
$new_password = $_POST['new_password'];

$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$query = "UPDATE login SET password='$new_password_hash' WHERE email='$email'";

if(mysqli_query($connection, $query)){
    echo "<h3>Password Updated Successfully!</h3>";
    echo "<a href='signin.php'>Login Now</a>";
} else {
    echo "Error updating password.";
}
?>
