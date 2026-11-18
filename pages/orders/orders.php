
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

// Retrieve the customer's orders
$orders = $checkout->getOrderHistory();

// Handle errors
$orders_error = '';

if (is_string($orders)) {
    $orders_error = $orders;
    $orders = [];
}

// Ensure orders is an array
if (!is_array($orders)) {
    $orders = [];
}

// Count customer's orders
$total_orders = count($orders);

?>
