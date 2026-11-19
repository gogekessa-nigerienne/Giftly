<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../customer/Login.html");
    exit();
}

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    
    // Call the controller to handle the Paystack API check and DB saving
    $controller = new CheckoutController();
    $result = $controller->verifyPayment($reference);

    if (is_array($result) && isset($result['success'])) {
        header("Location: ../pages/checkout/success.php?order=" . $result['order_number']);
        exit();
    } else {
        $error = is_string($result) ? $result : "Unknown error";
        header("Location: ../pages/checkout/failed.php?error=" . urlencode($error));
        exit();
    }
} else {
    header("Location: ../pages/checkout/failed.php?error=No reference provided");
    exit();
}
?>