
<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/Order.php';

class CheckoutController
{
    private $order;

    public function __construct()
    {
        $this->order = new Order();
    }

    // Get the logged-in customer's ID
    private function getUserId()
    {
        if (isset($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    // Validate the CSRF token
    private function validateCsrfToken($token)
    {
        if (
            !is_string($token) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            return false;
        }

        return true;
    }

    // Place a customer's order
    public function placeOrder($data, $csrf_token)
    {
        // Check whether the customer is logged in
        $user_id = $this->getUserId();

        if (!$user_id) {
            return "Please log in before placing an order.";
        }

        // Check the CSRF token
        if (!$this->validateCsrfToken($csrf_token)) {
            return "Invalid request. Please refresh the page.";
        }

        // Delivery fee is temporarily zero until the team decides the fee
        $delivery_fee = 0;

        return $this->order->createOrder(
            $user_id,
            $data,
            $delivery_fee
        );
    }


    // Confirm an order and reduce its product stock
    public function confirmOrder($order_id)
    {
        // Only an administrator should confirm orders
        if (
            !isset($_SESSION['role_id']) ||
            (int) $_SESSION['role_id'] !== 2
        ) {
            return "You are not authorized to confirm orders.";
        }

        if (!is_numeric($order_id) || (int) $order_id <= 0) {
            return "Invalid order.";
        }

        return $this->order->confirmOrder((int) $order_id);
    }


    // Retrieve the logged-in customer's order history
    public function getOrderHistory()
    {
        $user_id = $this->getUserId();

        if (!$user_id) {
            return "Please log in to view your orders.";
        }

        return $this->order->getOrderHistory($user_id);
    }

    // Retrieve the details of one order
    public function getOrderDetails($order_id)
    {
        $user_id = $this->getUserId();

        if (!$user_id) {
            return "Please log in to view your order.";
        }

        if (!is_numeric($order_id) || (int) $order_id <= 0) {
            return "Invalid order.";
        }

        return $this->order->getOrderDetails(
            (int) $order_id,
            $user_id
        );
    }
}

?>
