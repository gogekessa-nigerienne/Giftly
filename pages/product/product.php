<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

// Escape output
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Currency format
function formatMoney($amount) {
    return 'GH₵ ' . number_format((float) $amount, 2);
}

// Get product ID from URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id || $product_id <= 0) {
    http_response_code(404);
    exit('Invalid product.');
}

// Fetch product
$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.image_path,
        p.stock_quantity,
        c.category_name
    FROM products p
    INNER JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = ?
      AND p.is_active = 1
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

$inStock = (int) $product['stock_quantity'] > 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($product['product_name']); ?> | Giftly</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<!-- HEADER -->
<header class="product-header">
    <div class="product-header-inner">

        <a href="../home/home.php" class="product-logo">
            Giftly
            <span>Where Every Gift Tells a Story.</span>
        </a>

        <nav>
            <a href="../home/home.php">Home</a>
            <a href="../shop/shop.php">Shop</a>
        </nav>

        <div class="header-actions">
            <a href="../dashboard/CustomerDashboard.php">My Account</a>
            <a href="../cart/cart.php" class="cart-link">🛒 My Cart</a>
        </div>

    </div>
</header>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <a href="../home/home.php">Home</a>
    <span>/</span>
    <a href="../shop/shop.php">Shop</a>
    <span>/</span>
    <span><?php echo e($product['product_name']); ?></span>
</div>

<!-- PRODUCT DETAILS -->
<main class="product-container">

    <section class="product-details">

        <!-- IMAGE -->
        <div class="product-image-section">

            <?php if (!empty($product['image_path'])): ?>

                <img
                    src="../../<?php echo e($product['image_path']); ?>"
                    alt="<?php echo e($product['product_name']); ?>"
                    class="product-image"
                >

            <?php else: ?>

                <div class="product-image-placeholder">
                    <span>Giftly</span>
                    <p>A gift waiting to be discovered.</p>
                </div>

            <?php endif; ?>

        </div>

        <!-- INFORMATION -->
        <div class="product-info">

            <p class="product-category">
                <?php echo e($product['category_name']); ?>
            </p>

            <h1><?php echo e($product['product_name']); ?></h1>

            <p class="product-price">
                <?php echo formatMoney($product['price']); ?>
            </p>

            <div class="stock-status <?php echo $inStock ? 'available' : 'unavailable'; ?>">
                <?php if ($inStock): ?>
                    ✓ In Stock
                <?php else: ?>
                    ✕ Currently Out of Stock
                <?php endif; ?>
            </div>

            <div class="product-description">
                <h3>About this gift</h3>

                <p>
                    <?php
                    echo nl2br(e(
                        $product['description'] ??
                        'A thoughtful gift to make someone feel special.'
                    ));
                    ?>
                </p>
            </div>

            <?php if ($inStock): ?>

                <form action="../cart/cart_action.php" method="POST" class="add-cart-form">

                    <input type="hidden" name="action" value="add">

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php echo (int) $product['product_id']; ?>"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo e($_SESSION['csrf_token']); ?>"
                    >

                    <label for="quantity">Quantity</label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                min="1"
                max="<?php echo (int) $product['stock_quantity']; ?>"
                value="1"
                required
            >

                    <button type="submit" class="add-to-cart-button">
                        Add to Cart
                    </button>

                </form>

            <?php else: ?>

                <button class="add-to-cart-button unavailable-button" disabled>
                    Out of Stock
                </button>

            <?php endif; ?>

            <a href="../shop/shop.php" class="continue-shopping">
                ← Continue Shopping
            </a>

        </div>

    </section>

    <!-- EXTRA INFORMATION -->
    <section class="product-benefits">

        <div class="benefit">
            <span>♡</span>
            <div>
                <h3>Thoughtfully Selected</h3>
                <p>Gifts chosen to create meaningful moments.</p>
            </div>
        </div>

        <div class="benefit">
            <span>✧</span>
            <div>
                <h3>Make Someone's Day</h3>
                <p>Every gift is an opportunity to show you care.</p>
            </div>
        </div>

        <div class="benefit">
            <span>▣</span>
            <div>
                <h3>Easy Shopping</h3>
                <p>Find your gift and add it to your cart in a few steps.</p>
            </div>
        </div>

    </section>

</main>

<footer class="product-footer">
    <p>© <?php echo date('Y'); ?> Giftly. Where Every Gift Tells a Story.</p>
</footer>
<script src="product.js"></script>

</body>
</html>