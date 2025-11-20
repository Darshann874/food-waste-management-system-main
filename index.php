<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Food Donate — Ending Hunger Together</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* GLOBAL */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{
    overflow-x:hidden;
    background:#0b0f0d;
    color:#fff;
}

/* BACKGROUND ANIMATION */
.bg{
    position:fixed;
    inset:0;
    background:url('https://images.unsplash.com/photo-1520201163981-8cc95007dd2f') center/cover no-repeat;
    filter:brightness(70%);
}
.bg::after{
    content:"";
    position:absolute;
    inset:0;
    background:rgba(0,0,0,0.55);
    backdrop-filter:blur(6px);
}

/* FLOATING SHAPES */
.shape{
    position:absolute;
    width:240px;
    height:240px;
    background:rgba(6,193,103,0.28);
    filter:blur(80px);
    border-radius:50%;
    animation:float 8s ease-in-out infinite alternate;
}
.shape2{background:rgba(255,255,255,0.18);top:40%;left:70%;animation-duration:10s;}
@keyframes float{
    from{transform:translateY(-40px);}
    to{transform:translateY(40px);}
}

/* NAVBAR */
nav{
    width:100%;
    padding:20px 50px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:fixed;
    top:0;
    z-index:20;
    background:rgba(0,0,0,0.35);
    backdrop-filter:blur(10px);
    border-bottom:1px solid rgba(255,255,255,0.1);
}
nav h2{
    font-size:26px;
    font-weight:700;
}
nav h2 span{color:#06C167;}
nav .links a{
    margin-left:24px;
    color:#fff;
    text-decoration:none;
    font-size:16px;
    font-weight:500;
}
nav .links a:hover{color:#06C167;}

/* HERO */
.hero{
    text-align:center;
    padding-top:170px;
    max-width:900px;
    margin:auto;
    position:relative;
    z-index:10;
}
.hero h1{
    font-size:55px;
    font-weight:700;
    line-height:1.1;
}
.hero h1 span{color:#06C167;}
.hero p{
    margin-top:12px;
    font-size:18px;
    opacity:0.9;
}

/* BUTTONS */
.btns{
    margin-top:40px;
}
.btn{
    display:inline-block;
    padding:14px 40px;
    font-size:18px;
    background:#06C167;
    border-radius:35px;
    text-decoration:none;
    font-weight:600;
    color:#fff;
    box-shadow:0 10px 25px rgba(6,193,103,0.25);
    margin:10px;
}
.btn:hover{
    background:#049a4f;
    transform:translateY(-5px);
}
.btn-outline{
    background:transparent;
    border:2px solid #fff;
}
.btn-outline:hover{
    border-color:#06C167;
    color:#06C167;
}

/* ABOUT SECTION */
.about{
    margin-top:140px;
    padding:60px 30px;
    max-width:1100px;
    margin-inline:auto;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(10px);
    border-radius:20px;
    z-index:10;
    position:relative;
}
.about h2{
    color:#06C167;
    text-align:center;
    font-size:32px;
    margin-bottom:10px;
}
.about p{
    text-align:center;
    font-size:17px;
    line-height:1.7;
    opacity:0.9;
}

/* FOOTER */
footer{
    margin-top:60px;
    text-align:center;
    padding:25px 20px;
    font-size:15px;
    background:rgba(0,0,0,0.35);
    backdrop-filter:blur(8px);
    border-top:1px solid rgba(255,255,255,0.15);
}
footer a{
    color:#06C167;
    text-decoration:none;
}
footer a:hover{text-decoration:underline;}

@media(max-width:768px){
    nav{padding:14px 22px;}
    .hero h1{font-size:38px;}
    .btn{font-size:16px;padding:12px 30px;}
}
</style>
</head>
<body>

<div class="bg"></div>
<div class="shape"></div>
<div class="shape shape2"></div>

<nav>
  <h2>Food <span>Donate</span></h2>
  <div class="links">
    <a href="#">Home</a>
    <a href="#about">About</a>
    <a href="leaderboard.php">Leaderboard</a>
    <a href="signin.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
  </div>
</nav>

<div class="hero">
    <h1>Reduce Waste. <span>Share Food.</span><br>Help Someone Eat Today.</h1>
    <p>A smart platform connecting Donors, NGOs, Delivery Volunteers & Super Admins.</p>

    <div class="btns">
        <a href="signup.php" class="btn">Create Account</a>
        <a href="signin.php" class="btn btn-outline">Login</a>
        <a href="leaderboard.php" class="btn btn-outline">View Leaderboard</a>
    </div>
</div>

<div class="about" id="about">
    <h2>About Food Donate</h2>
    <p>
        Food Donate is a community-driven initiative designed to minimize food waste
        and ensure surplus food reaches those in need. Through seamless collaboration
        between donors, NGOs, delivery volunteers, and administrators, we build a hunger-free future.
    </p>
</div>

<footer>
    © 2025 Food Donate • Made with ❤️  
    &nbsp;|&nbsp; <a href="#">Privacy Policy</a>
</footer>


</body>
</html>
