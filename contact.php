<?php 
session_start();
$userName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact - Food Donate</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ===== BASE ===== */
body {
  margin: 0;
  font-family: "Inter", system-ui, sans-serif;
  color: #fff;
  background: url('img/p3.jpeg') no-repeat center center/cover;
  min-height: 100vh;
  position: relative;
}
body::after {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.6); /* dark overlay for readability */
  z-index: 0;
}

/* ===== NAVBAR ===== */
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 40px;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,0.2);
  position: sticky;
  top: 0;
  z-index: 10;
}
.logo { font-size: 26px; font-weight: 800; color: #06C167; }
nav a {
  text-decoration: none;
  color: #fff;
  margin: 0 14px;
  font-weight: 500;
  transition: .3s;
}
nav a:hover { color: #06C167; }

/* ===== CONTACT SECTION ===== */
main {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 100px);
  text-align: center;
  padding: 60px 20px;
}

h1 {
  color: #06C167;
  font-size: 42px;
  margin-bottom: 10px;
  font-weight: 800;
}
p.subtitle {
  color: #e5e5e5;
  font-size: 18px;
  margin-bottom: 40px;
}

/* ===== CONTACT CARD ===== */
.contact-wrapper {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  gap: 30px;
  width: 90%;
  max-width: 1000px;
  margin: 0 auto;
}
.card {
  background: rgba(255,255,255,0.12);
  backdrop-filter: blur(10px);
  border-radius: 18px;
  padding: 30px 28px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.2);
  text-align: left;
  color: #fff;
}
.card h2 {
  color: #06C167;
  margin-bottom: 18px;
}
.card p {
  font-size: 16px;
  color: #f0f0f0;
  line-height: 1.7;
}
.card i {
  color: #06C167;
  margin-right: 10px;
}

/* ===== FORM ===== */
form {
  display: grid;
  gap: 18px;
}
input, textarea {
  padding: 14px 16px;
  border: none;
  border-radius: 10px;
  background: rgba(255,255,255,0.15);
  color: #fff;
  font-size: 15px;
  outline: none;
  transition: .3s;
}
input::placeholder, textarea::placeholder { color: #ccc; }
input:focus, textarea:focus {
  background: rgba(255,255,255,0.25);
  box-shadow: 0 0 0 3px rgba(6,193,103,.3);
}
button {
  background: #06C167;
  color: white;
  padding: 12px 20px;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: .3s;
}
button:hover {
  background: #049e55;
  transform: translateY(-2px);
}

/* ===== FOOTER ===== */
footer {
  text-align: center;
  padding: 30px;
  color: #ccc;
  font-size: 14px;
  position: relative;
  z-index: 1;
}
footer b { color: #06C167; }

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
  header { flex-direction: column; gap: 10px; padding: 10px 20px; }
}
</style>
</head>

<body>
<header>
  <div class="logo">Food <b>Donate</b></div>
  <nav>
    <a href="home.php">Home</a>
    <a href="about.php">About</a>
    <a href="feedback.php">Feedback</a>
  </nav>
</header>

<main>
  <h1>Contact Us</h1>
  <p class="subtitle">We’d love to hear from you — let’s make a difference together 🌿</p>

  <div class="contact-wrapper">
    <!-- Contact Info -->
    <div class="card">
      <h2>Get In Touch</h2>
      <p><i class="fa-solid fa-envelope"></i> fooddonate@gmail.com</p>
      <p><i class="fa-solid fa-phone"></i> (+91) 9999 888 669</p>
      <p><i class="fa-solid fa-location-dot"></i> Graphic Era Hill University, Bhimtal Campus</p>
      <div style="margin-top:25px;">
        <a href="https://www.facebook.com/TheAkshayaPatraFoundation/" target="_blank" style="color:#06C167;margin-right:14px;"><i class="fab fa-facebook fa-lg"></i></a>
        <a href="https://twitter.com/globalgiving" target="_blank" style="color:#06C167;margin-right:14px;"><i class="fab fa-twitter fa-lg"></i></a>
        <a href="https://www.instagram.com/charitism/" target="_blank" style="color:#06C167;"><i class="fab fa-instagram fa-lg"></i></a>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="card">
      <h2>Send a Message</h2>
      <form method="POST" action="#">
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Your Email" required>
        <textarea name="message" rows="4" placeholder="Your Message" required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>
  </div>
</main>

<footer>
  <p>© 2025 <b>Food Donate</b> | Together Against Food Waste 🌿</p>
</footer>
</body>
</html>
