
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../controllers/CheckoutController.php';

// Require customer login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

$checkout = new CheckoutController();

// Validate the order ID from the URL
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

// Default values
$order = null;
$order_items = [];
$error = '';

// Check whether the order ID is valid
if (!$order_id || $order_id <= 0) {

    $error = "Invalid order ID.";
    http_response_code(400);

} else {

    // Retrieve the main order information
    $order = $checkout->getOrderById($order_id);

    if (!$order) {

        $error = "Order not found or you do not have permission to view it.";
        http_response_code(404);

    } else {

        // Retrieve the purchased products
        $order_items = $checkout->getOrderDetails($order_id);

        if (!is_array($order_items)) {
            $order_items = [];
        }
    }
}

?>
