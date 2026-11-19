
document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll(".cart-quantity-form").forEach(form => {

        const input = form.querySelector('input[name="quantity"]');
        const updateButton = form.querySelector(".update-button");

        const wrapper = document.createElement("div");
        wrapper.className = "cart-quantity-control";

        const minus = document.createElement("button");
        minus.type = "button";
        minus.textContent = "−";

        const plus = document.createElement("button");
        plus.type = "button";
        plus.textContent = "+";

        input.before(wrapper);
        wrapper.append(minus, input, plus);

        minus.addEventListener("click", () => {
            input.value = Math.max(1, Number(input.value) - 1);
        });

        plus.addEventListener("click", () => {
            input.value = Math.min(
                Number(input.max),
                Number(input.value) + 1
            );
        });

        form.addEventListener("submit", event => {
            const quantity = Number(input.value);

            if (quantity < 1 || quantity > Number(input.max)) {
                event.preventDefault();
                alert("Please enter a valid quantity.");
                return;
            }

            updateButton.textContent = "Updating...";
            updateButton.disabled = true;
        });
    });

    // Remove item confirmation
    document.querySelectorAll(".remove-item-form").forEach(form => {
        form.addEventListener("submit", event => {
            if (!confirm("Are you sure you want to remove this item?")) {
                event.preventDefault();
            }
        });
    });

});
