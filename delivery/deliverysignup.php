<?php
include '../connection.php';
$msg=0;
if(isset($_POST['sign']))
{
    $username=$_POST['username'];
    $email=$_POST['email'];
    $password=$_POST['password'];
    $location=$_POST['district'];

    $pass=password_hash($password,PASSWORD_DEFAULT);
    $sql="select * from delivery_persons where email='$email'";
    $result=mysqli_query($connection, $sql);
    $num=mysqli_num_rows($result);
    
    if($num==1){
        echo "<h1><center>Account already exists</center></h1>";
    } else {
        $query="insert into delivery_persons(name,email,password,city) values('$username','$email','$pass','$location')";
        $query_run=mysqli_query($connection, $query);
        if($query_run){
            header("location:delivery.php");
        } else {
            echo '<script type="text/javascript">alert("data not saved")</script>';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Register</title>
    <link rel="stylesheet" href="deliverycss.css">
</head>
<body>
    <div class="center">
        <h1>Register</h1>
        <form method="post" action="">
            <div class="txt_field">
                <input type="text" name="username" required/>
                <span></span>
                <label>Username</label>
            </div>
            <div class="txt_field">
                <input type="password" name="password" required/>
                <span></span>
                <label>Password</label>
            </div>
            <div class="txt_field">
                <input type="email" name="email" required/>
                <span></span>
                <label>Email</label>
            </div>

            <div>
                <select id="district" name="district" style="padding:10px; padding-left: 20px;">
                    <option value="Almora">Almora</option>
                    <option value="Bageshwar">Bageshwar</option>
                    <option value="Chamoli">Chamoli</option>
                    <option value="Champawat">Champawat</option>
                    <option value="Dehradun" selected>Dehradun</option>
                    <option value="Haridwar">Haridwar</option>
                    <option value="Nainital">Nainital</option>
                    <option value="Pauri Garhwal">Pauri Garhwal</option>
                    <option value="Pithoragarh">Pithoragarh</option>
                    <option value="Rudraprayag">Rudraprayag</option>
                    <option value="Tehri Garhwal">Tehri Garhwal</option>
                    <option value="Udham Singh Nagar">Udham Singh Nagar</option>
                    <option value="Uttarkashi">Uttarkashi</option>
                </select>
            </div>
            <br>

            <input type="submit" name="sign" value="Register">

            <div class="signup_link">
                Already a member? <a href="deliverylogin.php">Sign in</a>
            </div>
        </form>
    </div>
</body>
</html>
