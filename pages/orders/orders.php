
<?php

// CUSTOMER ORDER HISTORY PAGE

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../controllers/CheckoutController.php';

// Create controller
$checkout = new CheckoutController();

// Retrieve customer's orders
$orders = $checkout->getOrderHistory();

// Handle errors
$orders_error = '';

if (is_string($orders)) {
    $orders_error = $orders;
    $orders = [];
}

if (!is_array($orders)) {
    $orders = [];
}

// Total number of orders
$total_orders = count($orders);

// Format currency
function formatOrderMoney($amount) {
    return 'GH₵ ' . number_format((float) $amount, 2);
}

// Format status
function formatOrderStatus($status) {
    return ucfirst(str_replace('_', ' ', (string) $status));
}

// Safe output
function orderEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders | Giftly</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<main class="dashboard-main">

    <section class="dashboard-section">

        <div class="section-heading">
            <div>
                <p class="section-label">YOUR PURCHASES</p>
                <h1>My Orders</h1>
                <p>You have <?php echo $total_orders; ?> order(s).</p>
            </div>

            <a href="../dashboard/CustomerDashboard.php"
               class="view-all-link">
                Back to Dashboard
            </a>
        </div>

        <?php if (!empty($orders_error)): ?>

            <p><?php echo orderEscape($orders_error); ?></p>

        <?php elseif (!empty($orders)): ?>

            <div class="orders-table-wrapper">

                <table class="orders-table">

                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Date</th>
                            <th>Recipient</th>
                            <th>Delivery</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($orders as $order): ?>

                        <tr>

                            <td>
                                #<?php echo orderEscape($order['order_number']); ?>
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
                                <?php echo orderEscape($order['recipient_name']); ?>
                            </td>

                            <td>
                                <?php echo orderEscape(ucfirst($order['delivery_method'])); ?>
                            </td>

                            <td>
                                <?php echo formatOrderMoney($order['total_amount']); ?>
                            </td>

                            <td>
                                <?php echo orderEscape(formatOrderStatus($order['order_status'])); ?>
                            </td>

                            <td>
                                <a
                                    href="order_details.php?order_id=<?php echo (int) $order['order_id']; ?>"
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
                    Your orders will appear here after you complete checkout.
                </p>

                <a href="../shop/shop.php" class="primary-button">
                    Start Shopping
                </a>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>
