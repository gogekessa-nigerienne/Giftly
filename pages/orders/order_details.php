
<?php

// CUSTOMER ORDER DETAILS PAGE

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../controllers/CheckoutController.php';

// Create controller
$checkout = new CheckoutController();

// Get and validate order ID
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

// Default values
$order = null;
$order_items = [];
$error = '';

// Validate order ID
if (!$order_id || $order_id <= 0) {

    $error = "Invalid order ID.";

} else {

    // Retrieve order information
    $order = $checkout->getOrderById($order_id);

    if (!$order) {

        $error = "Order not found or you do not have permission to view it.";

    } else {

        // Retrieve purchased products
        $order_items = $checkout->getOrderDetails($order_id);

        if (!is_array($order_items)) {
            $order_items = [];
        }
    }
}

// Format currency
function formatDetailsMoney($amount) {
    return 'GH₵ ' . number_format((float) $amount, 2);
}

// Format status
function formatDetailsStatus($status) {
    return ucfirst(str_replace('_', ' ', (string) $status));
}

// Safe output
function detailsEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Details | Giftly</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<main class="dashboard-main">

    <section class="dashboard-section">

        <div class="section-heading">

            <div>
                <p class="section-label">YOUR PURCHASE</p>
                <h1>Order Details</h1>
            </div>

            <a href="orders.php" class="view-all-link">
                Back to My Orders
            </a>

        </div>


        <?php if (!empty($error)): ?>

            <div class="empty-orders">
                <h3><?php echo detailsEscape($error); ?></h3>

                <a href="orders.php" class="primary-button">
                    Return to My Orders
                </a>
            </div>

        <?php else: ?>


            <!-- ORDER INFORMATION -->

            <div class="order-information">

                <h2>Order Information</h2>

                <p>
                    <strong>Order Number:</strong>
                    #<?php echo detailsEscape($order['order_number']); ?>
                </p>

                <p>
                    <strong>Order Date:</strong>
                    <?php
                    echo date(
                        'd M Y, h:i A',
                        strtotime($order['placed_at'])
                    );
                    ?>
                </p>

                <p>
                    <strong>Order Status:</strong>
                    <?php
                    echo detailsEscape(
                        formatDetailsStatus($order['order_status'])
                    );
                    ?>
                </p>

            </div>


            <!-- DELIVERY INFORMATION -->

            <div class="order-information">

                <h2>Delivery Information</h2>

                <p>
                    <strong>Recipient:</strong>
                    <?php echo detailsEscape($order['recipient_name']); ?>
                </p>

                <p>
                    <strong>Contact Phone:</strong>
                    <?php echo detailsEscape($order['contact_phone']); ?>
                </p>

                <p>
                    <strong>Delivery Method:</strong>
                    <?php
                    echo detailsEscape(
                        ucfirst($order['delivery_method'])
                    );
                    ?>
                </p>

                <?php if (!empty($order['delivery_address'])): ?>

                    <p>
                        <strong>Address:</strong>
                        <?php echo detailsEscape($order['delivery_address']); ?>
                    </p>

                <?php endif; ?>

                <?php if (!empty($order['city'])): ?>

                    <p>
                        <strong>City:</strong>
                        <?php echo detailsEscape($order['city']); ?>
                    </p>

                <?php endif; ?>

                <?php if (!empty($order['delivery_notes'])): ?>

                    <p>
                        <strong>Delivery Notes:</strong>
                        <?php echo detailsEscape($order['delivery_notes']); ?>
                    </p>

                <?php endif; ?>

                <?php if (!empty($order['gift_message'])): ?>

                    <p>
                        <strong>Gift Message:</strong>
                        <?php echo detailsEscape($order['gift_message']); ?>
                    </p>

                <?php endif; ?>

            </div>


            <!-- PURCHASED PRODUCTS -->

            <div class="order-information">

                <h2>Purchased Products</h2>

                <?php if (!empty($order_items)): ?>

                    <div class="orders-table-wrapper">

                        <table class="orders-table">

                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>Line Total</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($order_items as $item): ?>

                                <tr>

                                    <td>
                                        <?php echo detailsEscape($item['product_name']); ?>
                                    </td>

                                    <td>
                                        <?php echo formatDetailsMoney($item['unit_price']); ?>
                                    </td>

                                    <td>
                                        <?php echo (int) $item['quantity']; ?>
                                    </td>

                                    <td>
                                        <?php echo formatDetailsMoney($item['line_total']); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <p>No products found for this order.</p>

                <?php endif; ?>

            </div>


            <!-- ORDER TOTALS -->

            <div class="order-information">

                <h2>Payment Summary</h2>

                <p>
                    <strong>Subtotal:</strong>
                    <?php echo formatDetailsMoney($order['subtotal']); ?>
                </p>

                <p>
                    <strong>Delivery Fee:</strong>
                    <?php echo formatDetailsMoney($order['delivery_fee']); ?>
                </p>

                <p>
                    <strong>Total Amount:</strong>
                    <?php echo formatDetailsMoney($order['total_amount']); ?>
                </p>

            </div>


        <?php endif; ?>

    </section>

</main>

</body>
</html>
