<?php
include 'connection.php';

$email = $_POST['email'] ?? '';

$query = "SELECT security_question FROM login WHERE email='$email'";
$result = mysqli_query($connection, $query);

if(mysqli_num_rows($result) == 0){
    die("No account found with this email.");
}

$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Security Question</title>
</head>
<body>

<h2>Security Question</h2>

<form action="reset_password.php" method="POST">

    <input type="hidden" name="email" value="<?php echo $email; ?>">

    <p><b><?php echo $row['security_question']; ?></b></p>

    <label>Your Answer:</label><br>
    <input type="text" name="security_answer" required><br><br>

    <button type="submit">Verify Answer</button>
</form>

</body>
</html>
