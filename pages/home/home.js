document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const navigation = document.getElementById("mainNavigation");

    // Mobile navigation
    if (menuToggle && navigation) {

        menuToggle.addEventListener("click", function () {

            const isExpanded =
                menuToggle.getAttribute("aria-expanded") === "true";

            menuToggle.setAttribute(
                "aria-expanded",
                String(!isExpanded)
            );

            navigation.classList.toggle("is-open");

        });

    }

});
