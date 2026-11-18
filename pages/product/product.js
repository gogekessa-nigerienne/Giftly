document.addEventListener("DOMContentLoaded", () => {

    // PRODUCT QUANTITY CONTROL


    const quantityInput = document.getElementById("quantity");
    const addToCartForm = document.querySelector(".add-cart-form");
    const addToCartButton = document.querySelector(".add-to-cart-button");

    if (!quantityInput) return;

    const minQuantity = parseInt(quantityInput.min) || 1;
    const maxQuantity = parseInt(quantityInput.max) || 1;

    // Create quantity controls
    const quantityWrapper = document.createElement("div");
    quantityWrapper.className = "quantity-control";

    const decreaseButton = document.createElement("button");
    decreaseButton.type = "button";
    decreaseButton.className = "quantity-btn decrease";
    decreaseButton.textContent = "−";
    decreaseButton.setAttribute("aria-label", "Decrease quantity");

    const increaseButton = document.createElement("button");
    increaseButton.type = "button";
    increaseButton.className = "quantity-btn increase";
    increaseButton.textContent = "+";
    increaseButton.setAttribute("aria-label", "Increase quantity");

    // Place controls around the quantity input
    quantityInput.parentNode.insertBefore(quantityWrapper, quantityInput);

    quantityWrapper.appendChild(decreaseButton);
    quantityWrapper.appendChild(quantityInput);
    quantityWrapper.appendChild(increaseButton);

    // Update button states
    function updateQuantityButtons() {
        const currentQuantity = parseInt(quantityInput.value) || minQuantity;

        decreaseButton.disabled = currentQuantity <= minQuantity;
        increaseButton.disabled = currentQuantity >= maxQuantity;
    }

    // Decrease quantity
    decreaseButton.addEventListener("click", () => {
        let quantity = parseInt(quantityInput.value) || minQuantity;

        if (quantity > minQuantity) {
            quantityInput.value = quantity - 1;
        }

        updateQuantityButtons();
    });

    // Increase quantity
    increaseButton.addEventListener("click", () => {
        let quantity = parseInt(quantityInput.value) || minQuantity;

        if (quantity < maxQuantity) {
            quantityInput.value = quantity + 1;
        }

        updateQuantityButtons();
    });

    // Validate manually typed quantity
    quantityInput.addEventListener("input", () => {
        let quantity = parseInt(quantityInput.value);

        if (quantityInput.value === "") {
            updateQuantityButtons();
            return;
        }

        if (quantity < minQuantity) {
            quantityInput.value = minQuantity;
        }

        if (quantity > maxQuantity) {
            quantityInput.value = maxQuantity;
        }

        updateQuantityButtons();
    });

    // Prevent invalid quantity when leaving the input
    quantityInput.addEventListener("blur", () => {
        let quantity = parseInt(quantityInput.value);

        if (isNaN(quantity) || quantity < minQuantity) {
            quantityInput.value = minQuantity;
        }

        if (quantity > maxQuantity) {
            quantityInput.value = maxQuantity;
        }

        updateQuantityButtons();
    });

    // Initial button state
    updateQuantityButtons();


    // ADD TO CART FEEDBACK



    if (addToCartForm && addToCartButton) {

        addToCartForm.addEventListener("submit", (event) => {
            const quantity = parseInt(quantityInput.value);

            // Validate quantity before submitting
            if (
                isNaN(quantity) ||
                quantity < minQuantity ||
                quantity > maxQuantity
            ) {
                event.preventDefault();

                alert("Please select a valid quantity.");

                quantityInput.focus();
                return;
            }

            // Give visual feedback while submitting
            addToCartButton.textContent = "Adding to Cart...";
            addToCartButton.disabled = true;
        });
    }
});