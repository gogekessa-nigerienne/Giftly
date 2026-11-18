
<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController
{
    private $cart;

    public function __construct()
    {
        $this->cart = new Cart();
    }

    // Get the current user's ID
    private function getUserId()
    {
        if (isset($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    // Get the current PHP session ID
    private function getSessionId()
    {
        return session_id();
    }

    // Validate CSRF token for cart changes
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

    // Add a product to the cart
    public function addToCart($product_id, $quantity, $csrf_token)
    {
        if (!$this->validateCsrfToken($csrf_token)) {
            return "Invalid request. Please refresh the page.";
        }

        if (
            !is_numeric($product_id) ||
            (int) $product_id <= 0 ||
            !is_numeric($quantity) ||
            (int) $quantity <= 0
        ) {
            return "Invalid product or quantity.";
        }

        return $this->cart->addItem(
            (int) $product_id,
            (int) $quantity,
            $this->getUserId(),
            $this->getSessionId()
        );
    }

    // Get all products in the cart
    public function getCart()
    {
        return $this->cart->getCartItems(
            $this->getUserId(),
            $this->getSessionId()
        );
    }

    // Get cart quantity and subtotal
    public function getCartSummary()
    {
        return $this->cart->getCartSummary(
            $this->getUserId(),
            $this->getSessionId()
        );
    }

    // Update the quantity of a cart item
    public function updateCartItem(
        $cart_item_id,
        $quantity,
        $csrf_token
    ) {
        if (!$this->validateCsrfToken($csrf_token)) {
            return "Invalid request. Please refresh the page.";
        }

        if (
            !is_numeric($cart_item_id) ||
            (int) $cart_item_id <= 0 ||
            !is_numeric($quantity) ||
            (int) $quantity <= 0
        ) {
            return "Invalid cart item or quantity.";
        }

        return $this->cart->updateItem(
            (int) $cart_item_id,
            (int) $quantity,
            $this->getUserId(),
            $this->getSessionId()
        );
    }

    // Remove an item from the cart
    public function removeCartItem($cart_item_id, $csrf_token)
    {
        if (!$this->validateCsrfToken($csrf_token)) {
            return "Invalid request. Please refresh the page.";
        }

        if (
            !is_numeric($cart_item_id) ||
            (int) $cart_item_id <= 0
        ) {
            return "Invalid cart item.";
        }

        return $this->cart->removeItem(
            (int) $cart_item_id,
            $this->getUserId(),
            $this->getSessionId()
        );
    }

    // Get all cart information together
    public function getCartDetails()
    {
        return [
            'items' => $this->getCart(),
            'summary' => $this->getCartSummary()
        ];
    }
}

?>
