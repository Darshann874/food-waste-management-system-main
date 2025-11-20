<?php
session_start();
$isAdminMode = isset($_GET['admin']);

include 'connection.php';

$msg = 0;

if (isset($_POST['sign'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepared statement
    $stmt = mysqli_prepare($connection, "SELECT * FROM login WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        // Verify password (HASHED)
        if (password_verify($password, $row['password'])) {

            // Store common user data
            $_SESSION['email']  = $row['email'];
            $_SESSION['name']   = $row['name'];
            $_SESSION['gender'] = $row['gender'];
            $_SESSION['role']   = $row['role'];

            /* ----------------------------
              SUPER ADMIN
            -----------------------------*/
            if ($row['role'] === 'super_admin') {
                die("Access Denied. Use the private Admin Login page.");
            }


            /* ----------------------------
              RECEIVER
            -----------------------------*/
            if ($row['role'] === 'receiver') {

                $ridQuery = mysqli_prepare($connection,
                    "SELECT Rid, location AS city, address 
                     FROM receivers 
                     WHERE email = ? LIMIT 1"
                );
                mysqli_stmt_bind_param($ridQuery, "s", $email);
                mysqli_stmt_execute($ridQuery);
                $res = mysqli_stmt_get_result($ridQuery);

                if ($r = mysqli_fetch_assoc($res)) {
                    $_SESSION['Rid']      = $r['Rid'];
                    $_SESSION['city']     = $r['city'];
                    $_SESSION['location'] = $r['city']; 
                    $_SESSION['address']  = $r['address'];
                }

                mysqli_stmt_close($ridQuery);

                header("Location: receiver/receiver.php");
                exit();
            }

            /* ----------------------------
              DELIVERY
            -----------------------------*/
            if ($row['role'] === 'delivery') {

                $dQuery = mysqli_prepare($connection,
                    "SELECT Did, city FROM delivery_persons WHERE email=? LIMIT 1"
                );
                mysqli_stmt_bind_param($dQuery, "s", $email);
                mysqli_stmt_execute($dQuery);
                $dRes = mysqli_stmt_get_result($dQuery);

                if ($d = mysqli_fetch_assoc($dRes)) {
                    $_SESSION['Did']  = $d['Did'];
                    $_SESSION['city'] = $d['city'];
                }

                mysqli_stmt_close($dQuery);

                header("Location: delivery/delivery.php");
                exit();
            }

            /* ----------------------------
              DONOR (DEFAULT)
            -----------------------------*/
            header("Location: home.php");
            exit();
        } 
        else {
            $msg = 1; // Wrong password
        }
    } 
    else {
        $msg = 2; // Email not found
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signin</title>
    <link rel="stylesheet" href="loginstyle.css">
</head>
<body>

<div class="container">
    <div class="regform">
        <form action="" method="post">
            <p class="logo">Food <b style="color:#06C167;">Donate</b></p>
            <p id="heading">
                <?= $isAdminMode ? "Super Admin Login" : "Welcome back!" ?>
            </p>

            <div class="input">
                <input type="email" placeholder="Email address" name="email" required/>
            </div>

            <div class="password">
                <input type="password" placeholder="Password" name="password" required/>
                <i class="uil uil-eye-slash showHidePw"></i>

                <?php
                if($msg == 1){
                    echo '<p class="error">Incorrect Password.</p>';
                }
                if($msg == 2){
                    echo '<p class="error">Account Not Found.</p>';
                }
                ?>
            </div>

            <!-- ⭐ Added Forgot Password Link -->
            <p style="text-align:right; margin-top:5px;">
                <a href="forgot_password.php" style="color:#06C167; font-weight:600; text-decoration:none;">
                    Forgot Password?
                </a>
            </p>

            <div class="btn">
                <button type="submit" name="sign">Sign in</button>
            </div>

            <div class="signin-up">
                <p>Don't have an account? <a href="signup.php">Register</a></p>
            </div>
        </form>
    </div>
</div>

</body>
</html>
