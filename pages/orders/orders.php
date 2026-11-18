
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/CheckoutController.php';

// Require customer login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

$checkout = new CheckoutController();

// Retrieve the customer's orders
$orders = $checkout->getOrderHistory();

$orders_error = '';
if (is_string($orders)) {
    $orders_error = $orders;
    $orders = [];
}

?>
