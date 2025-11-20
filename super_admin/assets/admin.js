// ================================
// SUPER ADMIN — INTERACTIVE JS
// Animations + Dark mode
// ================================

// ----------------- DARK MODE -----------------
const toggleBtn = document.getElementById("themeToggle");

if (localStorage.getItem("admin-theme") === "dark") {
    document.body.classList.add("dark");
    toggleBtn.textContent = "☀️ Light Mode";
}

toggleBtn.addEventListener("click", () => {
    document.body.classList.toggle("dark");

    if (document.body.classList.contains("dark")) {
        toggleBtn.textContent = "☀️ Light Mode";
        localStorage.setItem("admin-theme", "dark");
    } else {
        toggleBtn.textContent = "🌙 Dark Mode";
        localStorage.setItem("admin-theme", "light");
    }
});

// ----------------- CARD ANIMATION DELAY -----------------
let delay = 0;
document.querySelectorAll(".sa-card, .sa-small-card").forEach(card => {
    card.style.animationDelay = delay + "s";
    delay += 0.08;
});

// ----------------- CHART SMOOTH ENTRANCE -----------------
document.addEventListener("DOMContentLoaded", () => {
    const chartBox = document.querySelector("canvas");
    chartBox.style.opacity = 0;
    setTimeout(() => {
        chartBox.style.transition = ".7s ease";
        chartBox.style.opacity = 1;
    }, 300);
});
