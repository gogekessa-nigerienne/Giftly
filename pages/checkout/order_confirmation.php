<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId <= 0) {
    http_response_code(404);
    exit('Order not found.');
}

$stmt = $conn->prepare(
    "SELECT
        o.order_number,
        o.total_amount,
        o.order_status,
        o.placed_at,
        p.payment_method,
        p.payment_status
     FROM orders o
     LEFT JOIN payments p
        ON p.payment_id = (
            SELECT MAX(latest_payment.payment_id)
            FROM payments latest_payment
            WHERE latest_payment.order_id = o.order_id
        )
     WHERE o.order_id = ?
       AND o.user_id = ?
     LIMIT 1"
);

$userId = (int) $_SESSION['user_id'];
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Received | Giftly</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <main>
        <h1>Thank you for your order</h1>
        <p>Your order has been received.</p>

        <dl>
            <dt>Order number</dt>
            <dd><?php echo e($order['order_number']); ?></dd>

            <dt>Order status</dt>
            <dd><?php echo e(ucfirst(str_replace('_', ' ', $order['order_status']))); ?></dd>

            <dt>Payment method</dt>
            <dd><?php echo e(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'Not recorded'))); ?></dd>

            <dt>Payment status</dt>
            <dd><?php echo e(ucfirst($order['payment_status'] ?? 'Pending')); ?></dd>

            <dt>Total</dt>
            <dd>GH₵ <?php echo number_format((float) $order['total_amount'], 2); ?></dd>
        </dl>

        <p>Payment processing is not available yet. Your payment status will remain pending.</p>

        <a href="../dashboard/CustomerDashboard.php">Go to My Account</a>
        <a href="../shop/shop.php">Continue Shopping</a>
    </main>
</body>
</html>
