
document.addEventListener("DOMContentLoaded", () => {

    const methods = document.querySelectorAll('[name="delivery_method"]');
    const fields = document.getElementById("delivery-fields");
    const address = document.getElementById("delivery_address");
    const city = document.getElementById("city");

    function updateDeliveryFields() {
        const delivery = document.querySelector(
            '[name="delivery_method"]:checked'
        ).value === "delivery";

        fields.style.display = delivery ? "block" : "none";

        address.required = delivery;
        city.required = delivery;
    }

    methods.forEach(method =>
        method.addEventListener("change", updateDeliveryFields)
    );

    updateDeliveryFields();

});
