
<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

$featuredProducts = [];
$categories = [];
$occasions = [];
$cartCount = 0;

try {
    $productQuery = "
        SELECT
            product_id,
            product_name,
            description,
            price,
            image_path,
            stock_quantity
        FROM products
        WHERE is_active = 1
          AND is_featured = 1
          AND stock_quantity > 0
        ORDER BY created_at DESC
        LIMIT 8
    ";

    $featuredProducts = $conn->query($productQuery)->fetch_all(MYSQLI_ASSOC);

    $categoryQuery = "
        SELECT category_id, category_name, slug, image_path
        FROM categories
        WHERE is_active = 1
        ORDER BY category_name ASC
    ";

    $categories = $conn->query($categoryQuery)->fetch_all(MYSQLI_ASSOC);

    $occasionQuery = "
        SELECT occasion_id, occasion_name, slug, description, image_path
        FROM occasions
        WHERE is_active = 1
        ORDER BY occasion_name ASC
    ";

    $occasions = $conn->query($occasionQuery)->fetch_all(MYSQLI_ASSOC);

    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();

    if ($userId !== null) {
        $cartQuery = $conn->prepare("
            SELECT COALESCE(SUM(ci.quantity), 0)
            FROM carts c
            INNER JOIN cart_items ci ON c.cart_id = ci.cart_id
            WHERE c.user_id = ?
              AND c.status = 'active'
        ");
        $cartQuery->bind_param('i', $userId);
    } else {
        $cartQuery = $conn->prepare("
            SELECT COALESCE(SUM(ci.quantity), 0)
            FROM carts c
            INNER JOIN cart_items ci ON c.cart_id = ci.cart_id
            WHERE c.session_id = ?
              AND c.status = 'active'
        ");
        $cartQuery->bind_param('s', $sessionId);
    }

    $cartQuery->execute();
    $cartCount = (int) $cartQuery->get_result()->fetch_row()[0];
    $cartQuery->close();

} catch (Throwable $e) {
    error_log("Welcome page error: " . $e->getMessage());
}

// Safe output helper
function escape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Giftly | Where Every Gift Tells a Story</title>

    <meta name="description"
          content="Discover thoughtful gifts for every person and every special occasion with Giftly.">

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

    <!-- NAVIGATION -->
    <header class="site-header">

        <a href="home.php" class="logo">
            Giftly
        </a>

        <nav class="main-navigation" id="mainNavigation">

            <a href="home.php">Home</a>
            <a href="../shop/shop.php">Shop</a>
            <a href="#categories">Categories</a>

        </nav>

        <div class="header-actions">

            <a href="../cart/cart.php" class="cart-link">
                Cart
                <span class="cart-count">
                    <?= $cartCount ?>
                </span>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>

                <a href="../dashboard/CustomerDashboard.php">
                    My Account
                </a>

            <?php else: ?>

                <a href="../login/login.php">Login</a>
                <a href="../register/register.php">Register</a>

            <?php endif; ?>

            <button
                type="button"
                id="menuToggle"
                class="menu-toggle"
                aria-label="Toggle navigation"
                aria-expanded="false"
                aria-controls="mainNavigation"
            >
                Menu
            </button>

        </div>

    </header>


    <main>

        <!-- HERO SECTION -->
        <section class="hero-section">

            <div class="hero-content">

                <p class="hero-subtitle">
                    Thoughtful gifts. Meaningful moments.
                </p>

                <h1>
                    Where Every Gift Tells a Story
                </h1>

                <p>
                    Discover thoughtful gifts for the people
                    who make your life special.
                </p>

                <a href="../shop/shop.php" class="primary-button">
                    Explore Gifts
                </a>

                <a href="../shop/shop.php"
                   class="secondary-button">
                    Browse All Gifts
                </a>

            </div>

        </section>


        <!-- OCCASIONS SECTION -->
        <section class="occasions-section">

            <h2>Shop by Occasion</h2>

            <p>
                Celebrate every special moment with Giftly.
            </p>

            <div class="occasion-grid">

                <?php if (!empty($occasions)): ?>

                    <?php foreach ($occasions as $occasion): ?>

                        <article class="occasion-card">

                            <?php if (!empty($occasion['image_path'])): ?>

                                <img
                                    src="../../<?= escape($occasion['image_path']) ?>"
                                    alt="<?= escape($occasion['occasion_name']) ?>"
                                >

                            <?php endif; ?>

                            <h3>
                                <?= escape($occasion['occasion_name']) ?>
                            </h3>

                            <a href="../shop/shop.php?occasion=<?= escape($occasion['slug']) ?>">
                                Explore
                            </a>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>
                        Our occasions will be available soon.
                    </p>

                <?php endif; ?>

            </div>

        </section>


        <!-- CATEGORIES SECTION -->
        <section class="categories-section" id="categories">

            <h2>Explore Our Categories</h2>

            <div class="category-grid">

                <?php if (!empty($categories)): ?>

                    <?php foreach ($categories as $category): ?>

                        <article class="category-card">

                            <?php if (!empty($category['image_path'])): ?>

                                <img
                                    src="../../<?= escape($category['image_path']) ?>"
                                    alt="<?= escape($category['category_name']) ?>"
                                >

                            <?php endif; ?>

                            <h3>
                                <?= escape($category['category_name']) ?>
                            </h3>

                            <a href="../shop/shop.php?category=<?= escape($category['slug']) ?>">
                                View Gifts
                            </a>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>Categories will appear here soon.</p>

                <?php endif; ?>

            </div>

        </section>


        <!-- GIFT RECOMMENDATION SECTION -->
        <section class="recommendation-section">

            <h2>Not Sure What to Gift?</h2>

            <p>
                Tell us about the occasion, your budget and
                your preferences. We will help you discover
                suitable gifts.
            </p>

            <a href="../shop/shop.php">
                Explore Gifts
            </a>

        </section>


        <!-- FEATURED PRODUCTS -->
        <section class="featured-section">

            <h2>Featured Gifts</h2>

            <div class="product-grid">

                <?php if (!empty($featuredProducts)): ?>

                    <?php foreach ($featuredProducts as $product): ?>

                        <article class="product-card"
                                 data-product-id="<?= (int) $product['product_id'] ?>">

                            <?php if (!empty($product['image_path'])): ?>

                                <img
                                    src="../../<?= escape($product['image_path']) ?>"
                                    alt="<?= escape($product['product_name']) ?>"
                                >

                            <?php endif; ?>

                            <h3>
                                <?= escape($product['product_name']) ?>
                            </h3>

                            <p>
                                GH₵<?= number_format((float) $product['price'], 2) ?>
                            </p>

                            <a href="../product/product.php?id=<?= (int) $product['product_id'] ?>">
                                View Product
                            </a>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>
                        Featured gifts will be displayed here soon.
                    </p>

                <?php endif; ?>

            </div>

        </section>

    </main>


    <!-- FOOTER -->
    <footer class="site-footer">

        <h2>Giftly</h2>

        <p>Where Every Gift Tells a Story.</p>

        <p>
            Thoughtful gifts for every special moment.
        </p>

        <p>&copy; <?= date('Y') ?> Giftly. All rights reserved.</p>

    </footer>


    <script src="home.js" defer></script>

</body>
</html>
