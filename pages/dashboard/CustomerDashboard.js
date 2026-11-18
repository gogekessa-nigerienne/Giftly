
/*
   GIFTLY CUSTOMER DASHBOARD
   dashboard.js */

document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const dashboardSidebar = document.getElementById("dashboardSidebar");



    if (menuToggle && dashboardSidebar) {

        menuToggle.addEventListener("click", function () {

            dashboardSidebar.classList.toggle("open");

        });

    }



    if (dashboardSidebar) {

        const navigationLinks = dashboardSidebar.querySelectorAll(".nav-link");

        navigationLinks.forEach(function (link) {

            link.addEventListener("click", function () {

                dashboardSidebar.classList.remove("open");

            });

        });

    }


    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape" && dashboardSidebar) {

            dashboardSidebar.classList.remove("open");

        }

    });

});
