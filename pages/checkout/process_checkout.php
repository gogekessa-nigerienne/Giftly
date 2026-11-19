<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../customer/Login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save the shipping info to session so the Order model can use it later
    $_SESSION['checkout_data'] = [
        'recipient_name'   => $_POST['recipient_name'] ?? 'Customer',
        'contact_phone'    => $_POST['contact_phone'] ?? '0000000000',
        'delivery_method'  => $_POST['delivery_method'] ?? 'delivery',
        'delivery_address' => $_POST['delivery_address'] ?? '',
        'city'             => $_POST['city'] ?? '',
        'delivery_notes'   => $_POST['delivery_notes'] ?? '',
        'gift_message'     => $_POST['gift_message'] ?? ''
    ];

    // Redirect to the payment page
    header("Location: payment.php");
    exit();
} else {
    header("Location: ../../index.php");
    exit();
}
?>