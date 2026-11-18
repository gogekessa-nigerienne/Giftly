
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/CheckoutController.php';

// Require customer login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

$checkout = new CheckoutController();

// Get the order ID from the URL
$order_id = $_GET['order_id'] ?? 0;

// Retrieve the main order information
$order = $checkout->getOrderById($order_id);

// Retrieve the purchased products
$order_items = [];

if ($order) {
    $order_items = $checkout->getOrderDetails($order_id);
} else {
    http_response_code(404);
}

?>
