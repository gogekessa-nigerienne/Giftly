
<?php
// CUSTOMER DASHBOARD CONTROLLER

// Start session and check customer login
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Database connection
require_once __DIR__ . '/../config/database.php';

// Get logged-in customer ID
$user_id = (int) $_SESSION['user_id'];

// CUSTOMER INFORMATION

$customer = [];

$stmt = $conn->prepare(
    "SELECT user_id, first_name, last_name, email, phone
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc() ?? [];

$stmt->close();

// ORDER STATISTICS


$total_orders = 0;
$pending_orders = 0;
$delivered_orders = 0;

$stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total_orders,
        SUM(CASE
            WHEN order_status IN (
                'pending',
                'confirmed',
                'processing',
                'out_for_delivery'
            )
            THEN 1 ELSE 0
        END) AS pending_orders,
        SUM(CASE
            WHEN order_status = 'delivered'
            THEN 1 ELSE 0
        END) AS delivered_orders
     FROM orders
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$order_stats = $result->fetch_assoc();

if ($order_stats) {
    $total_orders = (int) $order_stats['total_orders'];
    $pending_orders = (int) $order_stats['pending_orders'];
    $delivered_orders = (int) $order_stats['delivered_orders'];
}

$stmt->close();


//  RECENT ORDERS


$recent_orders = [];

$stmt = $conn->prepare(
    "SELECT
        order_id,
        order_number,
        total_amount,
        order_status,
        placed_at
     FROM orders
     WHERE user_id = ?
     ORDER BY placed_at DESC
     LIMIT 5"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recent_orders[] = $row;
}

$stmt->close();

//  CUSTOMER CART COUNT


$cart_count = 0;

$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(ci.quantity), 0) AS cart_count
     FROM carts c
     INNER JOIN cart_items ci ON c.cart_id = ci.cart_id
     WHERE c.user_id = ?
       AND c.status = 'active'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$cart_data = $result->fetch_assoc();

if ($cart_data) {
    $cart_count = (int) $cart_data['cart_count'];
}

$stmt->close();


// FEATURED GIFT PRODUCCTS


$featured_products = [];

$stmt = $conn->prepare(
    "SELECT
        product_id,
        product_name,
        price,
        image_path,
        stock_quantity
     FROM products
     WHERE is_active = 1
       AND is_featured = 1
       AND stock_quantity > 0
     ORDER BY created_at DESC
     LIMIT 4"
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $featured_products[] = $row;
}

$stmt->close();


//  CUSTOMER FIRST NAME


$customer_name = $customer['first_name'] ?? 'Customer';

?>
