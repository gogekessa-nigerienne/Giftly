<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/CartController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$cartController = new CartController();

$action = $_POST['action'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

switch ($action) {

    case 'add':
        $result = $cartController->addToCart(
            $_POST['product_id'] ?? 0,
            $_POST['quantity'] ?? 1,
            $csrf_token
        );
        break;

    case 'update':
        $result = $cartController->updateCartItem(
            $_POST['cart_item_id'] ?? 0,
            $_POST['quantity'] ?? 1,
            $csrf_token
        );
        break;

    case 'remove':
        $result = $cartController->removeCartItem(
            $_POST['cart_item_id'] ?? 0,
            $csrf_token
        );
        break;

    case 'set_packaging':
        $result = $cartController->setPackaging(
            $_POST['cart_item_id'] ?? 0,
            $_POST['packaging_id'] ?? null,
            $csrf_token
        );
        break;

    default:
        $result = 'Invalid cart action.';
}

if ($result === true) {

    if ($action === 'set_packaging') {
        $_SESSION['cart_success'] = 'Gift packaging updated successfully.';
    } else {
        $_SESSION['cart_success'] = 'Your cart has been updated successfully.';
    }

} else {

    $_SESSION['cart_error'] = is_string($result)
        ? $result
        : 'Something went wrong. Please try again.';
}

header('Location: cart.php');
exit;
