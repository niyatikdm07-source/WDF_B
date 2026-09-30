/* =====================================================
   STUDENTHUB - JAVASCRIPT
   ===================================================== */


/* =====================================================
   WAIT UNTIL HTML IS LOADED
   ===================================================== */

document.addEventListener("DOMContentLoaded", function () {





  

    /* =================================================
       1. HAMBURGER MENU
       ================================================= */

    const nav = document.querySelector("nav");
    const navList = document.querySelector("nav ul");

    if (nav && navList) {

        // Create hamburger button
        const hamburger = document.createElement("button");

        hamburger.className = "hamburger";
        hamburger.innerHTML = "☰";
        hamburger.setAttribute("aria-label", "Open menu");

        // Add hamburger before navigation list
        nav.insertBefore(hamburger, navList);


        // Open / close menu
        hamburger.addEventListener("click", function () {

            navList.classList.toggle("show-menu");

            if (navList.classList.contains("show-menu")) {
                hamburger.innerHTML = "✕";
            } else {
                hamburger.innerHTML = "☰";
            }

        });

    }


    /* =================================================
       2. LIGHT / DARK THEME SWITCHER
       ================================================= */

    const themeButton = document.createElement("button");

    themeButton.className = "theme-button";
    themeButton.innerHTML = "🌙";
    themeButton.title = "Dark Mode";
    themeButton.setAttribute(
        "aria-label",
        "Toggle dark mode"
    );


    // Add button to navigation
    if (nav) {
        nav.appendChild(themeButton);
    }


    // Check previously saved theme
    const savedTheme =
        localStorage.getItem("studenthub-theme");


    if (savedTheme === "dark") {

        document.body.classList.add("dark-mode");

        themeButton.innerHTML = "☀️";

        themeButton.title = "Light Mode";

    }


    // Theme button click
    themeButton.addEventListener("click", function () {

        document.body.classList.toggle("dark-mode");


        if (
            document.body.classList.contains("dark-mode")
        ) {

            // Save dark mode
            localStorage.setItem(
                "studenthub-theme",
                "dark"
            );

            themeButton.innerHTML = "☀️";

            themeButton.title = "Light Mode";

        } else {

            // Save light mode
            localStorage.setItem(
                "studenthub-theme",
                "light"
            );

            themeButton.innerHTML = "🌙";

            themeButton.title = "Dark Mode";

        }

    });


    /* =================================================
       3. NOTIFICATION BANNER
       ================================================= */

    const notificationBanner =
        document.createElement("div");

    notificationBanner.className =
        "notification-banner";


    notificationBanner.innerHTML = `

        <span>
            📢 Welcome to StudentHub!
            Check our Events page for upcoming college activities.
        </span>

        <button class="notification-close"
                aria-label="Close notification">
            ✕
        </button>

    `;


    // Put notification at top of page
    document.body.prepend(notificationBanner);


    // Close notification
    const notificationClose =
        notificationBanner.querySelector(
            ".notification-close"
        );


    notificationClose.addEventListener(
        "click",
        function () {

            notificationBanner.classList.add(
                "hide-notification"
            );

        }
    );


    /* =================================================
       4. MODAL POPUP
       ================================================= */

    const modal =
        document.createElement("div");

    modal.className =
        "studenthub-modal";


    modal.innerHTML = `

        <div class="modal-content">

            <button
                class="modal-close"
                aria-label="Close popup">
                ✕
            </button>

            <div class="modal-icon">
                🎓
            </div>

            <h2>
                Welcome to StudentHub!
            </h2>

            <p>
                Manage your college activities,
                attendance, marks and events
                from one convenient portal.
            </p>

            <button class="modal-ok">
                Get Started
            </button>

        </div>

    `;


    // Add modal to page
    document.body.appendChild(modal);


    const modalClose =
        modal.querySelector(".modal-close");

    const modalOk =
        modal.querySelector(".modal-ok");


    // Function to close modal
    function closeModal() {

        modal.classList.remove(
            "show-modal"
        );

    }


    // Close button
    modalClose.addEventListener(
        "click",
        closeModal
    );


    // Get Started button
    modalOk.addEventListener(
        "click",
        closeModal
    );


    // Close when clicking outside modal
    modal.addEventListener(
        "click",
        function (event) {

            if (event.target === modal) {
                closeModal();
            }

        }
    );


    // Close with Escape key
    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {
                closeModal();
            }

        }
    );


    // Show popup after 1.5 seconds
    setTimeout(function () {

        modal.classList.add(
            "show-modal"
        );

    }, 1500);
    /* =====================================================
   PAGINATION
   ===================================================== */

function createPagination(
    containerSelector = ".paginated-content",
    itemsPerPage = 3
) {

    const containers =
        document.querySelectorAll(containerSelector);

    containers.forEach(function (container) {

        const items =
            Array.from(
                container.querySelectorAll(".pagination-item")
            );

        if (items.length === 0) {
            return;
        }

        // Create pagination controls
        const pagination =
            document.createElement("div");

        pagination.className =
            "pagination";

        container.after(pagination);

        let currentPage = 1;

        const totalPages =
            Math.ceil(items.length / itemsPerPage);


        function showPage(page) {

            currentPage = page;

            const start =
                (page - 1) * itemsPerPage;

            const end =
                start + itemsPerPage;


            items.forEach(function (item, index) {

                if (
                    index >= start &&
                    index < end
                ) {

                    item.style.display = "";

                } else {

                    item.style.display = "none";

                }

            });


            renderButtons();

        }


        function renderButtons() {

            pagination.innerHTML = "";


            // Previous button

            const previousButton =
                document.createElement("button");

            previousButton.innerHTML =
                "← Previous";

            previousButton.disabled =
                currentPage === 1;

            previousButton.addEventListener(
                "click",
                function () {

                    if (currentPage > 1) {

                        showPage(
                            currentPage - 1
                        );

                    }

                }
            );

            pagination.appendChild(
                previousButton
            );


            // Page numbers

            for (
                let page = 1;
                page <= totalPages;
                page++
            ) {

                const pageButton =
                    document.createElement("button");

                pageButton.innerText =
                    page;

                if (page === currentPage) {

                    pageButton.classList.add(
                        "active"
                    );

                }

                pageButton.addEventListener(
                    "click",
                    function () {

                        showPage(page);

                    }
                );

                pagination.appendChild(
                    pageButton
                );

            }


            // Next button

            const nextButton =
                document.createElement("button");

            nextButton.innerHTML =
                "Next →";

            nextButton.disabled =
                currentPage === totalPages;

            nextButton.addEventListener(
                "click",
                function () {

                    if (
                        currentPage <
                        totalPages
                    ) {

                        showPage(
                            currentPage + 1
                        );

                    }

                }
            );

            pagination.appendChild(
                nextButton
            );
        }


        // Display first page

        showPage(1);

    });
}
    /* =====================================================
   STUDENTHUB - WEATHER API
   ===================================================== */

const WEATHER_API_KEY = "83af4982d000471b6aba7695bfa211a3";

// Change this to your college/city
const WEATHER_CITY = "jamnagar";

async function loadWeather() {

    const weatherBox = document.createElement("section");

    weatherBox.className = "weather-widget";

    weatherBox.innerHTML = `
        <div class="weather-loading">
            🌤️ Loading weather...
        </div>
    `;

    // Add weather box near the top of main content
    const main = document.querySelector("main");

    if (main) {
        main.prepend(weatherBox);
    } else {
        document.body.prepend(weatherBox);
    }

    try {

        const url =
            `https://api.openweathermap.org/data/2.5/weather` +
            `?q=${encodeURIComponent(WEATHER_CITY)}` +
            `&appid=${WEATHER_API_KEY}` +
            `&units=metric`;

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error("Weather API request failed");
        }

        const data = await response.json();

        const temperature =
            Math.round(data.main.temp);

        const feelsLike =
            Math.round(data.main.feels_like);

        const humidity =
            data.main.humidity;

        const windSpeed =
            data.wind.speed;

        const description =
            data.weather[0].description;

        const icon =
            data.weather[0].icon;

        const weatherIcon =
            `https://openweathermap.org/img/wn/${icon}@2x.png`;

        weatherBox.innerHTML = `

            <div class="weather-header">
                <h2>🌤️ Current Weather</h2>
                <span>📍 ${data.name}</span>
            </div>

            <div class="weather-content">

                <img
                    src="${weatherIcon}"
                    alt="${description}"
                    class="weather-icon"
                >

                <div class="weather-temperature">
                    ${temperature}°C
                </div>

                <div class="weather-description">
                    ${description}
                </div>

            </div>

            <div class="weather-details">

                <div>
                    <strong>Feels Like</strong>
                    <span>${feelsLike}°C</span>
                </div>

                <div>
                    <strong>Humidity</strong>
                    <span>${humidity}%</span>
                </div>

                <div>
                    <strong>Wind</strong>
                    <span>${windSpeed} m/s</span>
                </div>

            </div>
        `;

    } catch (error) {

        console.error("Weather Error:", error);

        weatherBox.innerHTML = `
            <div class="weather-error">
                ❌ Unable to load weather information.
                <br>
                Please try again later.
            </div>
        `;
    }
}
  loadWeather();

    createPagination(
        ".paginated-content",
        3
    );

});