
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("giftFinderForm");
    const button = document.getElementById("findGiftButton");

    if (!form || !button) return;

    form.addEventListener("submit", function (event) {
        if (!form.checkValidity()) {
            return;
        }

        button.disabled = true;
        button.querySelector("span:first-child").textContent =
            "Finding your gift...";

        button.querySelector(".button-arrow").textContent = "✦";
    });
});
