
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/CartController.php';

// Escape output safely
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Currency formatting
function formatMoney($amount) {
    return 'GH₵ ' . number_format((float) $amount, 2);
}

// Get categories
$categories = [];

$categoryQuery = "
    SELECT category_id, category_name, slug
    FROM categories
    WHERE is_active = 1
    ORDER BY category_name ASC
";

$categoryResult = $conn->query($categoryQuery);

if ($categoryResult) {
    while ($category = $categoryResult->fetch_assoc()) {
        $categories[] = $category;
    }
}

// Search and filter values
$search = trim($_GET['search'] ?? '');
$selectedCategory = $_GET['category'] ?? '';
$selectedOccasion = $_GET['occasion'] ?? '';

// Build products query
$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.image_path,
        p.stock_quantity,
        c.category_name,
        c.slug AS category_slug
    FROM products p
    INNER JOIN categories c
        ON p.category_id = c.category_id
    WHERE p.is_active = 1
";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if ($selectedCategory !== '') {
    $sql .= " AND c.slug = ?";
    $params[] = $selectedCategory;
    $types .= 's';
}

if ($selectedOccasion !== '') {
    $sql .= " AND EXISTS (
        SELECT 1
        FROM product_occasions po
        INNER JOIN occasions o ON o.occasion_id = po.occasion_id
        WHERE po.product_id = p.product_id
          AND o.slug = ?
          AND o.is_active = 1
    )";
    $params[] = $selectedOccasion;
    $types .= 's';
}

$sql .= " ORDER BY p.is_featured DESC, p.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();

// Get cart count
$cartController = new CartController();
$cartSummary = $cartController->getCartSummary();
$cartCount = $cartSummary['total_items'] ?? 0;

// Get selected category name
$selectedCategoryName = 'All Gifts';

foreach ($categories as $category) {
    if ($category['slug'] === $selectedCategory) {
        $selectedCategoryName = $category['category_name'];
        break;
    }
}

$selectedOccasionName = '';

if ($selectedOccasion !== '') {
    $occasionStmt = $conn->prepare(
        "SELECT occasion_name FROM occasions WHERE slug = ? AND is_active = 1 LIMIT 1"
    );
    $occasionStmt->bind_param('s', $selectedOccasion);
    $occasionStmt->execute();
    $occasionStmt->bind_result($selectedOccasionName);
    $occasionStmt->fetch();
    $occasionStmt->close();
}

if ($selectedOccasionName !== '') {
    $selectedCategoryName = 'Gifts for ' . $selectedOccasionName;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Gifts | Giftly</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<!-- HEADER -->
<header class="shop-header">
    <div class="shop-header-inner">

        <a href="../home/home.php" class="shop-logo">
            Giftly
            <span>Where Every Gift Tells a Story.</span>
        </a>

        <nav class="shop-nav">
            <a href="../home/home.php">Home</a>
            <a href="shop.php" class="active">Shop</a>
        </nav>

        <div class="shop-header-actions">
            <a href="../dashboard/CustomerDashboard.php" class="account-link">
                My Account
            </a>

            <a href="../cart/cart.php" class="cart-link">
                🛒 Cart
                <span class="cart-count"><?php echo (int) $cartCount; ?></span>
            </a>
        </div>

    </div>
</header>

<!-- HERO -->
<section class="shop-hero">
    <div class="shop-hero-content">

        <p class="shop-eyebrow">THOUGHTFUL GIFTS. MEANINGFUL MOMENTS.</p>

        <h1>Find something <em>special.</em></h1>

        <p>
            Discover thoughtful gifts for the people who make your world brighter.
            Whether it's a celebration or a simple "thinking of you,"
            find something they'll love.
        </p>

        <a href="#products" class="hero-button">
            Explore our gifts ↓
        </a>

    </div>
</section>

<!-- SHOP CONTENT -->
<main class="shop-container" id="products">

    <div class="shop-heading">
        <div>
            <p class="shop-section-label">THE GIFT COLLECTION</p>
            <h2><?php echo e($selectedCategoryName); ?></h2>
            <p class="product-count">
                <?php echo count($products); ?> gifts available
            </p>
        </div>
    </div>

    <!-- SEARCH -->
    <form method="GET" action="shop.php" class="shop-search-form">

        <?php if ($selectedCategory !== ''): ?>
            <input
                type="hidden"
                name="category"
                value="<?php echo e($selectedCategory); ?>"
            >
        <?php endif; ?>

        <?php if ($selectedOccasion !== ''): ?>
            <input
                type="hidden"
                name="occasion"
                value="<?php echo e($selectedOccasion); ?>"
            >
        <?php endif; ?>

        <div class="search-input-wrapper">
            <span class="search-icon">⌕</span>

            <input
                type="search"
                name="search"
                placeholder="Search for a gift..."
                value="<?php echo e($search); ?>"
            >

            <button type="submit">Search</button>
        </div>

    </form>

    <!-- CATEGORY FILTERS -->
    <div class="category-filter">

        <a
            href="shop.php<?php
                $allFilters = array_filter([
                    'search' => $search,
                    'occasion' => $selectedOccasion
                ], static function ($value) {
                    return $value !== '';
                });
                echo $allFilters ? '?' . e(http_build_query($allFilters)) : '';
            ?>"
            class="category-chip <?php echo $selectedCategory === '' ? 'selected' : ''; ?>"
        >
            All Gifts
        </a>

        <?php foreach ($categories as $category): ?>

            <a
                href="shop.php?<?php
                    $categoryFilters = array_filter([
                        'category' => $category['slug'],
                        'search' => $search,
                        'occasion' => $selectedOccasion
                    ], static function ($value) {
                        return $value !== '';
                    });
                    echo e(http_build_query($categoryFilters));
                ?>"
                class="category-chip <?php echo $selectedCategory === $category['slug'] ? 'selected' : ''; ?>"
            >
                <?php echo e($category['category_name']); ?>
            </a>

        <?php endforeach; ?>

    </div>

    <!-- PRODUCT GRID -->
    <section class="products-section">

        <?php if (!empty($products)): ?>

            <div class="products-grid">

                <?php foreach ($products as $product): ?>

                    <article class="product-card">

                        <div class="product-image-container">

                            <?php if (!empty($product['image_path'])): ?>

                                <img
                                    src="../../<?php echo e($product['image_path']); ?>"
                                    alt="<?php echo e($product['product_name']); ?>"
                                    class="product-image"
                                >

                            <?php else: ?>

                                <div class="product-placeholder">
                                    <span>Giftly</span>
                                    <small>A gift waiting to be discovered</small>
                                </div>

                            <?php endif; ?>

                            <?php if ((int) $product['stock_quantity'] <= 0): ?>

                                <span class="stock-badge out-of-stock">
                                    Out of Stock
                                </span>

                            <?php elseif ((int) $product['stock_quantity'] <= 5): ?>

                                <span class="stock-badge low-stock">
                                    Almost Gone
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="product-card-content">

                            <p class="product-category">
                                <?php echo e($product['category_name']); ?>
                            </p>

                            <h3><?php echo e($product['product_name']); ?></h3>

                            <p class="product-description">
                                <?php echo e($product['description'] ?? 'A thoughtful gift for someone special.'); ?>
                            </p>

                            <a
                                class="product-details-link"
                                href="../product/product.php?id=<?php echo (int) $product['product_id']; ?>"
                            >
                                View Gift Details
                            </a>

                            <div class="product-card-bottom">

                                <span class="product-price">
                                    <?php echo formatMoney($product['price']); ?>
                                </span>

                            </div>

                            <?php if ((int) $product['stock_quantity'] > 0): ?>

                                <form
                                    action="../cart/cart_action.php"
                                    method="POST"
                                    class="add-to-cart-form"
                                >

                                    <input type="hidden" name="action" value="add">

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?php echo (int) $product['product_id']; ?>"
                                    >

                                    <input type="hidden" name="quantity" value="1">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo e($_SESSION['csrf_token']); ?>"
                                    >

                                    <button type="submit" class="add-to-cart-button">
                                        Add to Cart
                                    </button>

                                </form>

                            <?php else: ?>

                                <button class="add-to-cart-button disabled" disabled>
                                    Out of Stock
                                </button>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="shop-empty-state">

                <div class="empty-icon">♡</div>

                <h3>No gifts found</h3>

                <p>
                    We couldn't find any gifts matching your search.
                    Try another keyword or explore all our gifts.
                </p>

                <a href="shop.php" class="reset-shop-button">
                    View All Gifts
                </a>

            </div>

        <?php endif; ?>

    </section>

</main>

<!-- FOOTER -->
<footer class="shop-footer">
    <p>© <?php echo date('Y'); ?> Giftly. Where Every Gift Tells a Story.</p>
</footer>
<script src="shop.js"></script>
</body>
</html>
