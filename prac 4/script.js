// FAQ collapsible
document.querySelectorAll("details").forEach((item) => {
  item.addEventListener("toggle", () => {
    if (item.open) {
      document.querySelectorAll("details").forEach((other) => {
        if (other !== item) other.open = false;
      });
    }
  });
});

// Notification banner
const banner = document.createElement("div");
banner.innerHTML = `
  <span>Welcome to StudentHub!</span>
  <button id="closeBanner">×</button>
`;
banner.style.cssText =
  "background:#004080;color:white;padding:12px;text-align:center;position:relative;";
document.body.prepend(banner);

document.getElementById("closeBanner").onclick = () => {
  banner.style.display = "none";
};

// Theme switcher
const themeButton = document.createElement("button");
themeButton.textContent = "🌙 Dark Mode";
themeButton.style.cssText =
  "position:fixed;right:20px;bottom:20px;padding:10px;z-index:1000;";
document.body.appendChild(themeButton);

themeButton.onclick = () => {
  document.body.classList.toggle("dark-mode");

  if (document.body.classList.contains("dark-mode")) {
    document.body.style.backgroundColor = "#222";
   // document.body.style.color = "white";
    themeButton.textContent = "☀ Light Mode";
  } else {
    document.body.style.backgroundColor = "#f4f7fc";
    document.body.style.color = "black";
    themeButton.textContent = "🌙 Dark Mode";
  }
};

// Hamburger menu for mobile
const nav = document.querySelector("nav");
const menuButton = document.createElement("button");
menuButton.textContent = "☰ Menu";
menuButton.style.margin = "10px";

if (nav) {
  nav.parentNode.insertBefore(menuButton, nav);

  menuButton.onclick = () => {
    nav.style.display = nav.style.display === "block" ? "none" : "block";
  };
}

// Modal popup
const modalButton = document.createElement("button");
modalButton.textContent = "Open Welcome Popup";
modalButton.style.margin = "15px";

const modal = document.createElement("div");
modal.innerHTML = `
  <div style="background:white;padding:25px;border-radius:10px;max-width:350px;margin:15% auto;text-align:center;">
    <h2>Welcome!</h2>
    <p>Thank you for visiting StudentHub.</p>
    <button id="closeModal">Close</button>
  </div>
`;
modal.style.cssText =
  "display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);";

document.body.appendChild(modalButton);
document.body.appendChild(modal);

modalButton.onclick = () => {
  modal.style.display = "block";
};

document.getElementById("closeModal").onclick = () => {
  modal.style.display = "none";
};

// Simple content slider
const slides = [
  "Register for classes easily.",
  "Check upcoming college events.",
  "View attendance and results."
];

let currentSlide = 0;

const slider = document.createElement("div");
slider.style.cssText =
  "margin:20px;padding:20px;background:#e6f0ff;text-align:center;border-radius:8px;";

slider.innerHTML = `
  <h2 id="slideText">${slides[currentSlide]}</h2>
  <button id="prevSlide">Previous</button>
  <button id="nextSlide">Next</button>
`;

document.body.appendChild(slider);

document.getElementById("nextSlide").onclick = () => {
  currentSlide = (currentSlide + 1) % slides.length;
  document.getElementById("slideText").textContent = slides[currentSlide];
};

document.getElementById("prevSlide").onclick = () => {
  currentSlide = (currentSlide - 1 + slides.length) % slides.length;
  document.getElementById("slideText").textContent = slides[currentSlide];
};