<?php 
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$userName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Food Donate - Donor Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
  margin: 0;
  background: #fff;
  font-family: "Inter", system-ui, sans-serif;
  color: #222;
}

/* NAVBAR */
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 40px;
  background: rgba(255,255,255,0.95);
  border-bottom: 1px solid #e5e5e5;
  position: sticky;
  top: 0;
  z-index: 1000;
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.logo { font-size: 26px; font-weight: 800; color: #06C167; }

.nav-bar { display: flex; align-items: center; gap: 22px; }
.nav-bar a {
  text-decoration: none; color: #333; font-weight: 500;
  padding: 8px 14px; border-radius: 8px; transition: .3s;
}
.nav-bar a:hover, .nav-bar a.active { background: #06C167; color: #fff; }

/* Profile */
.profile-menu { position: relative; }
.profile-btn {
  background: #06C167; color: white; padding: 10px 16px;
  border-radius: 30px; border: none; cursor: pointer; font-weight: 600;
}
.profile-btn:hover { background: #049e55; }
.profile-dropdown {
  position: absolute; right: 0; top: 45px; background: white;
  border-radius: 10px; width: 180px; display: none;
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.profile-dropdown a {
  display: block; padding: 10px 16px; color: #333;
  text-decoration: none; font-weight: 500;
}
.profile-dropdown a:hover { background: #eafff2; color: #06C167; }

/* HERO */
.hero {
  position: relative;
  background: url('img/coverimage.jpeg') no-repeat center center/cover;
  height: 100vh; color: white;
  display: flex; justify-content: center; flex-direction: column; align-items: center;
  text-align: center; overflow: hidden;
}
.hero::after {
  content: ""; position: absolute; inset: 0;
  background: rgba(0,0,0,0.45);
}
.hero-content { position: relative; z-index: 1; max-width: 700px; padding: 0 20px; }
.hero h1 { font-size: 48px; font-weight: 800; }
.hero p { font-size: 18px; opacity: .95; }
.hero .hero-btn {
  display: inline-block; background: #06C167; padding: 14px 36px;
  color: white; border-radius: 10px; text-decoration: none; font-weight: 700;
  transition: .3s;
}
.hero .hero-btn:hover { background: #049e55; transform: translateY(-3px); }

/* DASHBOARD CARDS */
.container { max-width: 1100px; margin: 60px auto; padding: 0 20px; }
.card-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 28px;
}
.card {
  background: #f9fffc; border: 1px solid #e5e5e5;
  border-radius: 16px; padding: 28px; text-align: center;
  box-shadow: 0 4px 10px rgba(0,0,0,.04); transition: .3s;
}
.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 28px rgba(0,0,0,.08);
}
.card h3 { color: #06C167; margin-bottom: 10px; }
.card p { color: #555; font-size: 15px; margin-bottom: 18px; }
.card a {
  text-decoration: none; background: #06C167; color: white;
  padding: 10px 20px; border-radius: 10px; font-weight: 600;
}
.card a:hover { background: #049e55; }

/* FOOTER */
footer {
  margin-top: 60px; padding: 40px; text-align: center;
  background: #fafafa; border-top: 1px solid #eee; color: #444;
}
</style>
</head>

<body>

<header>
  <div class="logo">Food <b>Donate</b></div>

  <nav class="nav-bar">
    <a href="home.php" class="active">Home</a>
    <a href="leaderboard.php">Leaderboard</a>   <!-- ✅ ADDED -->
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
    <a href="donor_history.php">My Donations</a>
    <a href="feedback.php">Feedback</a>

    <div class="profile-menu">
      <button class="profile-btn" id="profileBtn">
        <i class="fa-solid fa-user"></i> <?= $userName ?>
      </button>

      <div class="profile-dropdown" id="profileDropdown">
        <a href="profile.php"><i class="fa fa-user-circle"></i> Profile</a>
        <a href="logout.php" style="color:red;"><i class="fa fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </nav>
</header>

<script>
const profileBtn=document.getElementById('profileBtn');
const dropdown=document.getElementById('profileDropdown');
profileBtn.addEventListener('click',()=>{dropdown.style.display=dropdown.style.display==='block'?'none':'block';});
document.addEventListener('click',e=>{if(!profileBtn.contains(e.target)&&!dropdown.contains(e.target))dropdown.style.display='none';});
</script>

<section class="hero">
  <div class="hero-content">
    <h1>Share Food, Spread Happiness 🍽️</h1>
    <p>Turn your extra food into hope — every meal shared creates a smile.</p>
    <a href="fooddonateform.php" class="hero-btn">Donate Now</a>
  </div>
</section>

<div class="container">
  <div class="card-grid">

    <div class="card">
      <h3><i class="fa fa-ranking-star"></i> Donor Leaderboard</h3>
      <p>Check your donation rank and compete with other donors.</p>
      <a href="leaderboard.php">View Leaderboard</a>
    </div>

    <div class="card">
      <h3><i class="fa fa-hand-holding-heart"></i> Active Donations</h3>
      <p>Track current donations and delivery progress.</p>
      <a href="donor_history.php">View Donations</a>
    </div>

    <div class="card">
      <h3><i class="fa fa-history"></i> Past Contributions</h3>
      <p>View all your past donations.</p>
      <a href="donor_history.php">View History</a>
    </div>

    <div class="card">
      <h3><i class="fa fa-comment-dots"></i> Share Feedback</h3>
      <p>Your feedback helps improve the platform.</p>
      <a href="feedback.php">Give Feedback</a>
    </div>

  </div>
</div>

<footer>
  <p>© 2025 <b>Food Donate</b> | Every Meal Matters 🌿</p>
</footer>

</body>
</html>
