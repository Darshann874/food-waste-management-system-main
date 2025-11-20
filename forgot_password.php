<?php
include 'connection.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>

<h2>Forgot Password</h2>

<form action="verify_security_answer.php" method="POST">
    <label>Enter your Email</label><br>
    <input type="email" name="email" required><br><br>

    <button type="submit">Next</button>
</form>

</body>
</html>
