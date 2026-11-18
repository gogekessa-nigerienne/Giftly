
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/CheckoutController.php';
require_once __DIR__ . '/../../controllers/CartController.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatMoney($amount) {
    return 'GH₵ ' . number_format((float) $amount, 2);
}

// Get cart details
$cartController = new CartController();
$cartDetails = $cartController->getCartDetails();

$cartItems = $cartDetails['items'] ?? [];
$cartSummary = $cartDetails['summary'] ?? [];

$subtotal = (float) ($cartSummary['subtotal'] ?? 0);
$cartCount = (int) ($cartSummary['total_items'] ?? 0);

$errorMessage = '';

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $checkoutController = new CheckoutController();

    $result = $checkoutController->placeOrder(
        $_POST,
        $_POST['csrf_token'] ?? ''
    );

    if (is_array($result) && !empty($result['success'])) {

        header(
            "Location: order_confirmation.php?order_id=" .
            (int) $result['order_id']
        );
        exit;

    } else {
        $errorMessage = is_string($result)
            ? $result
            : 'Unable to place your order. Please try again.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | Giftly</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<header class="checkout-header">
    <a href="../home/home.php" class="checkout-logo">
        Giftly
        <span>Where Every Gift Tells a Story.</span>
    </a>

    <nav>
        <a href="../shop/shop.php">Continue Shopping</a>
        <a href="../cart/cart.php">My Cart</a>
    </nav>
</header>

<main class="checkout-container">

    <div class="checkout-heading">
        <p>ALMOST THERE</p>
        <h1>Complete Your Order</h1>
        <span>Provide your details to complete your purchase.</span>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="checkout-error">
            <?php echo e($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['user_id'])): ?>

        <section class="empty-checkout">
            <h2>Sign in to complete your order</h2>
            <p>Your cart is ready. Sign in or create an account to continue to checkout.</p>
            <a href="../login/login.php">Sign In</a>
            <a href="../register/register.php">Create Account</a>
            <a href="../cart/cart.php">Return to Cart</a>
        </section>

    <?php elseif (empty($cartItems)): ?>

        <section class="empty-checkout">
            <h2>Your cart is empty</h2>
            <p>Add some thoughtful gifts before checking out.</p>
            <a href="../shop/shop.php">Explore Gifts</a>
        </section>

    <?php else: ?>

        <form method="POST" action="checkout.php" class="checkout-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e($_SESSION['csrf_token']); ?>"
            >

            <div class="checkout-layout">

                <!-- CUSTOMER AND DELIVERY DETAILS -->
                <section class="checkout-details">

                    <div class="checkout-card">
                        <h2>Recipient Information</h2>

                        <label for="recipient_name">Recipient's Full Name</label>
                        <input
                            type="text"
                            id="recipient_name"
                            name="recipient_name"
                            required
                            value="<?php echo e($_POST['recipient_name'] ?? ''); ?>"
                        >

                        <label for="contact_phone">Contact Phone</label>
                        <input
                            type="tel"
                            id="contact_phone"
                            name="contact_phone"
                            required
                            value="<?php echo e($_POST['contact_phone'] ?? ''); ?>"
                        >
                    </div>

                    <div class="checkout-card">
                        <h2>Delivery Method</h2>

                        <label class="option">
                            <input
                                type="radio"
                                name="delivery_method"
                                value="delivery"
                                checked
                            >
                            Home Delivery
                        </label>

                        <label class="option">
                            <input
                                type="radio"
                                name="delivery_method"
                                value="pickup"
                            >
                            Store Pickup
                        </label>

                        <div id="delivery-fields">

                            <label for="delivery_address">Delivery Address</label>
                            <textarea
                                id="delivery_address"
                                name="delivery_address"
                                rows="3"
                            ><?php echo e($_POST['delivery_address'] ?? ''); ?></textarea>

                            <label for="city">City</label>
                            <input
                                type="text"
                                id="city"
                                name="city"
                                value="<?php echo e($_POST['city'] ?? ''); ?>"
                            >

                        </div>

                        <label for="delivery_notes">Delivery Instructions (Optional)</label>
                        <textarea
                            id="delivery_notes"
                            name="delivery_notes"
                            rows="2"
                        ><?php echo e($_POST['delivery_notes'] ?? ''); ?></textarea>

                        <label for="gift_message">Gift Message (Optional)</label>
                        <textarea
                            id="gift_message"
                            name="gift_message"
                            rows="3"
                            placeholder="Write a special message..."
                        ><?php echo e($_POST['gift_message'] ?? ''); ?></textarea>
                    </div>

                    <div class="checkout-card">
                        <h2>Payment Method</h2>

                        <label class="option">
                            <input
                                type="radio"
                                name="payment_method"
                                value="mobile_money"
                                required
                                checked
                            >
                            Mobile Money
                        </label>

                        <label class="option">
                            <input
                                type="radio"
                                name="payment_method"
                                value="card"
                                required
                            >
                            Debit / Credit Card
                        </label>

                        <label class="option">
                            <input
                                type="radio"
                                name="payment_method"
                                value="cash_on_delivery"
                                required
                            >
                            Cash on Delivery
                        </label>
                    </div>

                </section>

                <!-- ORDER SUMMARY -->
                <aside class="checkout-summary">

                    <h2>Your Order</h2>

                    <p><?php echo $cartCount; ?> item(s) in your cart</p>

                    <?php foreach ($cartItems as $item): ?>

                        <div class="checkout-item">
                            <div>
                                <strong><?php echo e($item['product_name']); ?></strong>
                                <span>Quantity: <?php echo (int) $item['quantity']; ?></span>
                            </div>

                            <strong>
                                <?php echo formatMoney($item['line_total']); ?>
                            </strong>
                        </div>

                    <?php endforeach; ?>

                    <div class="checkout-total">
                        <span>Subtotal</span>
                        <strong><?php echo formatMoney($subtotal); ?></strong>
                    </div>

                    <div class="checkout-total">
                        <span>Delivery Fee</span>
                        <strong>GH₵ 0.00</strong>
                    </div>

                    <div class="checkout-grand-total">
                        <span>Total</span>
                        <strong><?php echo formatMoney($subtotal); ?></strong>
                    </div>

                    <button type="submit" class="place-order-button">
                        Place Order
                    </button>

                    <a href="../cart/cart.php" class="back-to-cart">
                        ← Back to Cart
                    </a>

                </aside>

            </div>

        </form>

    <?php endif; ?>

</main>

<footer class="checkout-footer">
    <p>© <?php echo date('Y'); ?> Giftly. Where Every Gift Tells a Story.</p>
</footer>

<script src="checkout.js"></script>

</body>
</html>
