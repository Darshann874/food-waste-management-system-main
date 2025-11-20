<?php
session_start();
include 'connection.php';

$err = "";
$ok  = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $gender   = $_POST['gender'] ?? 'N/A';
    $role     = $_POST['role'] ?? 'donor';
    $city = trim($_POST['city'] ?? '');

    // NEW FIELDS
    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer   = trim($_POST['security_answer'] ?? '');

    // email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email.";
    }
    elseif (strlen($password) < 6) {
        $err = "Password must be at least 6 characters.";
    }
    elseif (empty($security_question) || empty($security_answer)) {
        $err = "Security question and answer are required.";
    }
    else {
        // Check duplicate
        $chk = mysqli_prepare($connection, "SELECT 1 FROM login WHERE email=?");
        mysqli_stmt_bind_param($chk, "s", $email);
        mysqli_stmt_execute($chk);
        $dup = mysqli_stmt_get_result($chk);

        if (mysqli_fetch_row($dup)) {
            $err = "An account with this email already exists.";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            // INSERT into login
            $ins = mysqli_prepare($connection,
                "INSERT INTO login(name,email,password,gender,role,city,security_question,security_answer)
                VALUES(?,?,?,?,?,?,?,?)"
            );

            mysqli_stmt_bind_param($ins,
                "ssssssss",
                $name, $email, $hash, $gender, $role, $city, $security_question, $security_answer
            );


            $okLogin = mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            if ($okLogin) {

                // Receiver extra data
                if ($role === 'receiver') {
                    $location = trim($_POST['location'] ?? '');
                    $address  = trim($_POST['address'] ?? '');

                    $ia = mysqli_prepare(
                        $connection,
                        "INSERT INTO receivers(name,email,password,location,address)
                        VALUES(?,?,?,?,?)"
                    );
                    mysqli_stmt_bind_param($ia, "sssss", $name, $email, $hash, $location, $address);
                    mysqli_stmt_execute($ia);
                    mysqli_stmt_close($ia);
                }

                // Delivery extra data
                elseif ($role === 'delivery') {
                    $city = trim($_POST['city'] ?? '');

                    $upd = mysqli_prepare($connection, "UPDATE login SET city=? WHERE email=?");
                    mysqli_stmt_bind_param($upd, "ss", $city, $email);
                    mysqli_stmt_execute($upd);
                    mysqli_stmt_close($upd);

                    $idp = mysqli_prepare(
                        $connection,
                        "INSERT INTO delivery_persons(name,email,password,city)
                        VALUES(?,?,?,?)"
                    );
                    mysqli_stmt_bind_param($idp, "ssss", $name, $email, $hash, $city);
                    mysqli_stmt_execute($idp);
                    mysqli_stmt_close($idp);
                }

                $ok = "Account created! You can sign in now.";
            } else {
                $err = "Server error creating account.";
            }
        }

        mysqli_stmt_close($chk);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Register – Food Donate</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
body {
    background:#f6fff9;
    font-family:Inter,Arial;
    margin:0; padding:0;
}

.card {
    max-width:650px;
    margin:50px auto;
    background:#fff;
    padding:35px;
    border-radius:22px;
    box-shadow:0 8px 30px rgba(0,0,0,.06);
    animation:fade .5s ease;
}

@keyframes fade { from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:none;} }

h2 {
    color:#06C167;
    text-align:center;
    margin-bottom:15px;
}

small.muted { color:#607b70; }

.alert {
    padding:12px; border-radius:12px; margin-bottom:20px; text-align:center;
}
.alert.ok { background:#ecfdf5; border:1px solid #bbf7d0; color:#065f46; }
.alert.err { background:#fff1f0; border:1px solid #fbb4af; color:#7f1d1d; }

.input-group { position:relative; margin-bottom:22px; }
input,select {
    width:100%; padding:13px;
    border-radius:10px; border:1px solid #cfeedc;
    background:#f8fffb; outline:none;
    transition:.25s;
}
input:focus,select:focus {
    border-color:#06C167;
    box-shadow:0 0 0 3px rgba(6,193,103,.22);
}

label.floating {
    position:absolute; left:13px; top:50%;
    transform:translateY(-50%);
    background:#f8fffb;
    padding:0 6px;
    pointer-events:none;
    color:#6b7280;
    transition:.25s;
}
input:focus + label,
input:not(:placeholder-shown) + label {
    top:-9px; font-size:13px; color:#06C167;
}

.role-grid {
    display:grid; grid-template-columns:repeat(3,1fr); gap:12px;
}
.role-tile {
    padding:16px; text-align:center; cursor:pointer;
    border-radius:12px; border:1px solid #d6f5e2;
    background:#fafffc; transition:.25s;
}
.role-tile.active {
    border-color:#06C167;
    background:#e5fff0;
    box-shadow:0 0 0 3px rgba(6,193,103,.25);
}

button {
    width:100%; padding:14px;
    background:#06C167; color:white;
    font-weight:700; border:none;
    border-radius:12px;
    cursor:pointer; transition:.3s;
}
button:hover {
    transform:translateY(-2px);
    box-shadow:0 6px 18px rgba(6,193,103,.25);
}

.hide { display:none; }
</style>
</head>

<body>

<div class="card">
    <h2>Create Your Account</h2>
    <p style="text-align:center;margin-top:-10px;"><small class="muted">Join FoodDonate & help reduce food waste</small></p>

    <?php if($ok): ?><div class="alert ok"><?= $ok ?></div><?php endif; ?>
    <?php if($err): ?><div class="alert err"><?= $err ?></div><?php endif; ?>

    <form method="post">

        <div class="input-group">
            <input name="name" placeholder=" " required>
            <label class="floating">Full Name</label>
        </div>

        <div class="input-group">
            <input name="email" type="email" placeholder=" " required>
            <label class="floating">Email Address</label>
        </div>

        <div class="input-group">
            <input name="password" type="password" placeholder=" " minlength="6" required>
            <label class="floating">Password</label>
        </div>

        <div class="input-group">
            <select name="gender">
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
                <option selected>N/A</option>
            </select>
        </div>

        <p><b>Select Your Role</b></p>

        <div class="role-grid">
            <label class="role-tile active" data-role="donor">
                <input class="hide" type="radio" name="role" value="donor" checked>
                Donor<br><small class="muted">Give food</small>
            </label>

            <label class="role-tile" data-role="receiver">
                <input class="hide" type="radio" name="role" value="receiver">
                Receiver<br><small class="muted">NGO / Organization</small>
            </label>

            <label class="role-tile" data-role="delivery">
                <input class="hide" type="radio" name="role" value="delivery">
                Delivery<br><small class="muted">Pickup & deliver</small>
            </label>
        </div>

        <!-- Receiver fields -->
        <div id="receiverFields" class="hide">
            <div class="input-group">
                <input name="location" placeholder=" ">
                <label class="floating">City / District</label>
            </div>

            <div class="input-group">
                <input name="address" placeholder=" ">
                <label class="floating">Organization Address</label>
            </div>
        </div>

        <!-- Delivery fields -->
        <div id="deliveryFields" class="hide">
            <div class="input-group">
                <input name="city" placeholder=" ">
                <label class="floating">Delivery Person City</label>
            </div>
        </div>

        <!-- SECURITY QUESTION (NEW) -->
        <p><b>Security Question (for password reset)</b></p>

        <div class="input-group">
            <select name="security_question" required>
                <option value="What is your pet's name?">What is your pet's name?</option>
                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                <option value="What is your favorite food?">What is your favorite food?</option>
            </select>
        </div>

        <div class="input-group">
            <input name="security_answer" placeholder=" " required>
            <label class="floating">Your Answer</label>
        </div>

        <button name="sign" type="submit">Create Account</button>

        <p style="text-align:center;margin-top:12px;">
            <small class="muted">Already have an account?
                <a href="signin.php" style="color:#06C167;font-weight:600;">Sign in</a>
            </small>
        </p>
    </form>
</div>

<script>
const tiles = document.querySelectorAll('.role-tile');
const receiverFields = document.getElementById('receiverFields');
const deliveryFields = document.getElementById('deliveryFields');

function activate(role){
    tiles.forEach(t => t.classList.toggle('active', t.dataset.role===role));
    receiverFields.classList.toggle('hide', role!=='receiver');
    deliveryFields.classList.toggle('hide', role!=='delivery');
}

tiles.forEach(t => t.addEventListener('click', () => {
    t.querySelector("input[type=radio]").checked = true;
    activate(t.dataset.role);
}));

activate('donor');
</script>

</body>
</html>
