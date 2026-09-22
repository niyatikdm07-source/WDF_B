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


    /* =================================================
       5. IMAGE / CONTENT SLIDER
       ================================================= */

    const slider =
        document.querySelector(
            ".student-slider"
        );


    if (slider) {

        const slides =
            slider.querySelectorAll(
                ".slide"
            );


        const nextButton =
            slider.querySelector(
                ".slider-next"
            );


        const previousButton =
            slider.querySelector(
                ".slider-prev"
            );


        let currentSlide = 0;


        // Show selected slide
        function showSlide(index) {

            // Remove active class
            slides.forEach(function (slide) {

                slide.classList.remove(
                    "active-slide"
                );

            });


            // If last slide → first slide
            if (index >= slides.length) {
                currentSlide = 0;
            }


            // If first slide → last slide
            if (index < 0) {
                currentSlide =
                    slides.length - 1;
            }


            // Show current slide
            slides[currentSlide].classList.add(
                "active-slide"
            );

        }


        // Start slider
        if (slides.length > 0) {

            showSlide(currentSlide);


            /* NEXT BUTTON */

            if (nextButton) {

                nextButton.addEventListener(
                    "click",
                    function () {

                        currentSlide++;

                        showSlide(currentSlide);

                    }
                );

            }


            /* PREVIOUS BUTTON */

            if (previousButton) {

                previousButton.addEventListener(
                    "click",
                    function () {

                        currentSlide--;

                        showSlide(currentSlide);

                    }
                );

            }


            /* AUTOMATIC SLIDER */

            setInterval(function () {

                currentSlide++;

                showSlide(currentSlide);

            }, 5000);

        }

    }

});