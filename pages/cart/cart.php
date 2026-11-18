
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/CartController.php';

// Safe output
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Currency format
function formatMoney($amount) {
    return 'GH₵ ' . number_format((float) $amount, 2);
}

$cartController = new CartController();
$cartDetails = $cartController->getCartDetails();

$cartItems = $cartDetails['items'] ?? [];
$cartSummary = $cartDetails['summary'] ?? [
    'total_items' => 0,
    'subtotal' => 0
];

$cartCount = (int) ($cartSummary['total_items'] ?? 0);
$subtotal = (float) ($cartSummary['subtotal'] ?? 0);

// Messages
$successMessage = $_SESSION['cart_success'] ?? '';
$errorMessage = $_SESSION['cart_error'] ?? '';

unset($_SESSION['cart_success'], $_SESSION['cart_error']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Cart | Giftly</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<!-- HEADER -->
<header class="cart-header">
    <div class="cart-header-inner">

        <a href="../home/home.php" class="cart-logo">
            Giftly
            <span>Where Every Gift Tells a Story.</span>
        </a>

        <nav class="cart-navigation">
            <a href="../home/home.php">Home</a>
            <a href="../shop/shop.php">Shop</a>
            <a href="../shop/shop.php">Browse Gifts</a>
        </nav>

        <div class="cart-header-actions">
            <a href="../dashboard/CustomerDashboard.php">My Account</a>
            <a href="cart.php" class="active-cart-link">
                🛒 Cart (<?php echo $cartCount; ?>)
            </a>
        </div>

    </div>
</header>

<!-- PAGE INTRO -->
<section class="cart-page-intro">
    <p class="cart-eyebrow">YOUR THOUGHTFUL SELECTION</p>
    <h1>My Shopping Cart</h1>
    <p>Every gift you've chosen is one step closer to making someone's day.</p>
</section>

<main class="cart-container">

    <!-- NOTIFICATIONS -->
    <?php if ($successMessage !== ''): ?>
        <div class="cart-alert success-alert">
            <?php echo e($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="cart-alert error-alert">
            <?php echo e($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($cartItems)): ?>

        <div class="cart-layout">

            <!-- CART ITEMS -->
            <section class="cart-items-section">

                <div class="cart-section-heading">
                    <h2>Your Gifts</h2>
                    <span><?php echo $cartCount; ?> item(s)</span>
                </div>

                <?php foreach ($cartItems as $item): ?>

                    <article class="cart-item">

                        <!-- IMAGE -->
                        <div class="cart-item-image">

                            <?php if (!empty($item['image_path'])): ?>

                                <img
                                    src="../../<?php echo e($item['image_path']); ?>"
                                    alt="<?php echo e($item['product_name']); ?>"
                                >

                            <?php else: ?>

                                <div class="cart-image-placeholder">
                                    Giftly
                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- ITEM INFORMATION -->
                        <div class="cart-item-info">

                            <h3><?php echo e($item['product_name']); ?></h3>

                            <p class="cart-item-unit-price">
                                <?php echo formatMoney($item['price']); ?> each
                            </p>

                            <?php if ((int) $item['stock_quantity'] > 0): ?>
                                <p class="cart-stock">
                                    <?php echo (int) $item['stock_quantity']; ?> available
                                </p>
                            <?php else: ?>
                                <p class="cart-stock unavailable">
                                    Currently unavailable
                                </p>
                            <?php endif; ?>

                            <!-- UPDATE QUANTITY -->
                            <form
                                action="cart_action.php"
                                method="POST"
                                class="cart-quantity-form"
                            >

                                <input type="hidden" name="action" value="update">

                                <input
                                    type="hidden"
                                    name="cart_item_id"
                                    value="<?php echo (int) $item['cart_item_id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo e($_SESSION['csrf_token']); ?>"
                                >

                                <label for="quantity-<?php echo (int) $item['cart_item_id']; ?>">
                                    Quantity
                                </label>

                                <input
                                    type="number"
                                    id="quantity-<?php echo (int) $item['cart_item_id']; ?>"
                                    name="quantity"
                                    min="1"
                                    max="<?php echo (int) $item['stock_quantity']; ?>"
                                    value="<?php echo (int) $item['quantity']; ?>"
                                    required
                                >

                                <button type="submit" class="update-button">
                                    Update
                                </button>

                            </form>

                            <!-- REMOVE ITEM -->
                            <form
                                action="cart_action.php"
                                method="POST"
                                class="remove-item-form"
                            >

                                <input type="hidden" name="action" value="remove">

                                <input
                                    type="hidden"
                                    name="cart_item_id"
                                    value="<?php echo (int) $item['cart_item_id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo e($_SESSION['csrf_token']); ?>"
                                >

                                <button type="submit" class="remove-button">
                                    Remove
                                </button>

                            </form>

                        </div>

                        <!-- LINE TOTAL -->
                        <div class="cart-item-total">

                            <span>Item Total</span>

                            <strong>
                                <?php echo formatMoney($item['line_total']); ?>
                            </strong>

                        </div>

                    </article>

                <?php endforeach; ?>

                <a href="../shop/shop.php" class="continue-shopping">
                    ← Continue Shopping
                </a>

            </section>

            <!-- ORDER SUMMARY -->
            <aside class="cart-summary-section">

                <div class="cart-summary-card">

                    <h2>Order Summary</h2>

                    <div class="summary-row">
                        <span>Items</span>
                        <span><?php echo $cartCount; ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatMoney($subtotal); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Delivery</span>
                        <span class="delivery-note">Calculated at checkout</span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-total-row">
                        <span>Total</span>
                        <strong><?php echo formatMoney($subtotal); ?></strong>
                    </div>

                    <p class="summary-note">
                        Delivery fees, if applicable, will be included at checkout.
                    </p>

                    <a href="../checkout/checkout.php" class="checkout-button">
                        Proceed to Checkout →
                    </a>

                    <div class="secure-shopping-note">
                        ♡ Thoughtful gifts. Secure shopping.
                    </div>

                </div>

            </aside>

        </div>

    <?php else: ?>

        <!-- EMPTY CART -->
        <section class="empty-cart">

            <div class="empty-cart-icon">♡</div>

            <h2>Your cart is waiting for something special.</h2>

            <p>
                You haven't added any gifts yet.
                Explore Giftly and find something meaningful for someone you love.
            </p>

            <a href="../shop/shop.php" class="empty-cart-button">
                Discover Gifts
            </a>

        </section>

    <?php endif; ?>

</main>

<footer class="cart-footer">
    <p>© <?php echo date('Y'); ?> Giftly. Where Every Gift Tells a Story.</p>
</footer>
<script src="cart.js"></script>
</body>
</html>
