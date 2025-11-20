<?php
// login.php - safe login handling
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";
    } else {
        // prepared SELECT
        $stmt = mysqli_prepare($connection, "SELECT password, name FROM login WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = $row['name'];
                    mysqli_stmt_close($stmt);
                    switch ($_SESSION['role']) {
                        case 'super_admin':
                            header("Location: super_admin/dashboard.php");
                            break;

                        case 'receiver':
                            header("Location: receiver/receiver.php");
                            break;

                        case 'donor':
                            header("Location: home.php");
                            break;

                        case 'delivery':
                            header("Location: delivery/delivery_dashboard.php");
                            break;
                    }
                    exit();

                } else {
                    $error = "Incorrect credentials.";
                }
            } else {
                $error = "Account does not exist.";
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Prepare failed: ".mysqli_error($connection));
            $error = "Server error.";
        }
    }
}
?>
<!-- show error if any, then login form -->
<?php if (!empty($error)) echo "<p style='color:red;'>".htmlspecialchars($error)."</p>"; ?>
<form method="post" action="login.php">
  <input name="email" type="email" required placeholder="Email" />
  <input name="password" type="password" required placeholder="Password" />
  <button name="sign" type="submit">Login</button>
</form>
