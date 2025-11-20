<?php
include 'connection.php';

$email = $_POST['email'];
$answer = $_POST['security_answer'];

$query = "SELECT security_answer FROM login WHERE email='$email' AND security_answer='$answer'";
$result = mysqli_query($connection, $query);

if(mysqli_num_rows($result) == 0){
    die("Incorrect answer! Try again.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

<h2>Reset Your Password</h2>

<form action="update_password.php" method="POST">

    <input type="hidden" name="email" value="<?php echo $email; ?>">

    <label>New Password</label><br>
    <input type="password" name="new_password" required minlength="6"><br><br>

    <button type="submit">Update Password</button>

</form>

</body>
</html>
