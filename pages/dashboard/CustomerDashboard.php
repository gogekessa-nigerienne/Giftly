
<?php

// GIFTLY - CUSTOMER DASHBOARD PAGE

require_once __DIR__ . '/../../controllers/CustomerDashboardController.php';

// Safe output helper
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Format currency
function formatMoney($amount) {
    return 'GH₵ ' . number_format((float) $amount, 2);
}

// Format order status for display
function formatStatus($status) {
    return ucfirst(str_replace('_', ' ', (string) $status));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | Giftly</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="dashboard-sidebar" id="dashboardSidebar">

        <div class="sidebar-logo">
            <a href="../home/home.php">Giftly</a>
            <p>Where Every Gift Tells a Story.</p>
        </div>

        <nav class="sidebar-navigation">

            <a href="CustomerDashboard.php" class="nav-link active">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="../shop/shop.php" class="nav-link">
                <span class="nav-icon">◇</span>
                <span>Shop Gifts</span>
            </a>

            <a href="../orders/orders.php" class="nav-link">
                <span class="nav-icon">▤</span>
                <span>My Orders</span>
            </a>

            <a href="../cart/cart.php" class="nav-link">
                <span class="nav-icon">🛒</span>
                <span>My Cart</span>
                <span class="cart-badge">
                    <?php echo $cart_count; ?>
                </span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <form action="../logout/logout.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo e($_SESSION['csrf_token']); ?>"
                >

                <button type="submit" class="nav-link logout-link">
                    <span class="nav-icon">↪</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="dashboard-main">

        <!-- TOP BAR -->
        <header class="dashboard-topbar">

            <button
                type="button"
                class="menu-toggle"
                id="menuToggle"
                aria-label="Toggle navigation"
            >
                ☰
            </button>

            <div class="topbar-title">
                <p>My Account</p>
            </div>

            <div class="topbar-actions">

                <a href="../cart/cart.php" class="topbar-cart">
                    🛒 Cart
                    <span><?php echo $cart_count; ?></span>
                </a>

                <div class="customer-mini-profile">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($customer_name, 0, 1)); ?>
                    </div>

                    <span><?php echo e($customer_name); ?></span>
                </div>

            </div>

        </header>


        <!-- WELCOME SECTION -->
        <section class="dashboard-welcome">

            <div class="welcome-text">
                <p class="welcome-label">YOUR GIFTLY SPACE</p>

                <h1>
                    Welcome back,
                    <?php echo e($customer_name); ?>!
                </h1>

                <p>
                    We're happy to have you here.
                    Let's make someone's day special.
                </p>
            </div>

            <a href="../shop/shop.php" class="primary-button">
                Explore Gifts →
            </a>

        </section>


        <!-- ORDER STATISTICS -->
        <section class="dashboard-statistics">

            <div class="stat-card">
                <div class="stat-icon total-icon">▤</div>

                <div class="stat-info">
                    <p>Total Orders</p>
                    <h2><?php echo $total_orders; ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending-icon">◷</div>

                <div class="stat-info">
                    <p>Orders In Progress</p>
                    <h2><?php echo $pending_orders; ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon delivered-icon">✓</div>

                <div class="stat-info">
                    <p>Delivered Orders</p>
                    <h2><?php echo $delivered_orders; ?></h2>
                </div>
            </div>

        </section>


        <!-- RECENT ORDERS -->
        <section class="dashboard-section recent-orders-section" id="recent-orders">

            <div class="section-heading">

                <div>
                    <p class="section-label">YOUR PURCHASES</p>
                    <h2>Recent Orders</h2>
                </div>

                <a href="../orders/orders.php" class="view-all-link">
                    View All →
                </a>

            </div>


            <?php if (!empty($recent_orders)): ?>

                <div class="orders-table-wrapper">

                    <table class="orders-table">

                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($recent_orders as $order): ?>

                            <tr>

                                <td>
                                    #<?php echo e($order['order_number']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        'd M Y',
                                        strtotime($order['placed_at'])
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo formatMoney($order['total_amount']); ?>
                                </td>

                                <td>
                                    <span class="order-status status-<?php
                                        echo strtolower(
                                            preg_replace(
                                                '/[^a-z0-9]+/',
                                                '-',
                                                $order['order_status']
                                            )
                                        );
                                    ?>">
                                        <?php echo e(formatStatus($order['order_status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="../orders/order_details.php?order_id=<?php echo (int) $order['order_id']; ?>"
                                        class="view-all-link"
                                    >
                                        View Details
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-orders">

                    <div class="empty-orders-icon">♡</div>

                    <h3>No orders yet</h3>

                    <p>
                        Your gifting journey starts here.
                        Find something special for someone you love.
                    </p>

                    <a href="../shop/shop.php" class="primary-button">
                        Start Shopping
                    </a>

                </div>

            <?php endif; ?>

        </section>


        <!-- FEATURED GIFTS -->
        <section class="dashboard-section featured-gifts-section">

            <div class="section-heading">

                <div>
                    <p class="section-label">HANDPICKED FOR YOU</p>
                    <h2>Explore Special Gifts</h2>
                </div>

                <a href="../shop/shop.php" class="view-all-link">
                    Shop All →
                </a>

            </div>


            <?php if (!empty($featured_products)): ?>

                <div class="featured-products-grid">

                    <?php foreach ($featured_products as $product): ?>

                        <article class="featured-product-card">

                            <div class="product-image-wrapper">

                                <?php if (!empty($product['image_path'])): ?>

                                    <img
                                        src="../../<?php echo e($product['image_path']); ?>"
                                        alt="<?php echo e($product['product_name']); ?>"
                                    >

                                <?php else: ?>

                                    <div class="product-image-placeholder">
                                        Giftly
                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="featured-product-info">

                                <h3>
                                    <?php echo e($product['product_name']); ?>
                                </h3>

                                <p class="product-price">
                                    <?php echo formatMoney($product['price']); ?>
                                </p>

                                <a
                                    href="../product/product.php?id=<?php echo (int) $product['product_id']; ?>"
                                    class="product-view-link"
                                >
                                    View Gift
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="no-featured-products">
                    <p>New gifts are coming soon. Check back later!</p>
                    <a href="../shop/shop.php">Browse our shop →</a>
                </div>

            <?php endif; ?>

        </section>


        <!-- GIFT FINDER -->
        <section class="gift-finder-banner">

            <div class="gift-finder-content">

                <p class="section-label">NOT SURE WHAT TO BUY?</p>

                <h2>Let us help you find the perfect gift.</h2>

                <p>
                    Tell us who you're shopping for, the occasion,
                    and your budget. We'll help you discover a gift
                    they'll love.
                </p>

                <a href="../shop/shop.php" class="gift-finder-button">
                    Explore Gifts →
                </a>

            </div>

        </section>


        <!-- FOOTER -->
        <footer class="dashboard-footer">
            <p>
                © <?php echo date('Y'); ?> Giftly.
                Where Every Gift Tells a Story.
            </p>
        </footer>

    </main>

</div>

<script src="CustomerDashboard.js"></script>

</body>
</html>
