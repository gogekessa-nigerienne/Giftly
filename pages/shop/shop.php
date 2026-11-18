
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

$sql = "SELECT product_id, product_name, description, price,
               image_path, stock_quantity
        FROM products
        WHERE is_active = 1
        ORDER BY created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to retrieve products. Please try again later.");
}

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>
