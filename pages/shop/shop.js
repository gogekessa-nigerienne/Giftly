
document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.querySelector('[name="search"]');
    const cards = document.querySelectorAll(".product-card");
    const count = document.querySelector(".product-count");
    const grid = document.querySelector(".products-grid");

    if (!searchInput || !grid) return;

    function searchProducts() {
        const term = searchInput.value.toLowerCase().trim();
        let visible = 0;

        cards.forEach(card => {
            const matches = card.textContent.toLowerCase().includes(term);

            card.style.display = matches ? "" : "none";

            if (matches) visible++;
        });

        count.textContent = `${visible} gifts available`;
    }

    searchInput.addEventListener("input", searchProducts);

    document.querySelectorAll(".add-to-cart-form").forEach(form => {
        form.addEventListener("submit", () => {
            const button = form.querySelector(".add-to-cart-button");
            button.textContent = "Adding...";
            button.disabled = true;
        });
    });

});
