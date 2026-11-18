
<?php

require_once __DIR__ . '/../config/database.php';

class Order
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // Create an order using the customer's active cart
    public function createOrder($user_id, $data, $delivery_fee = 0)
    {
        if (
            !is_numeric($user_id) ||
            (int) $user_id <= 0 ||
            !is_numeric($delivery_fee) ||
            (float) $delivery_fee < 0
        ) {
            return "Invalid order information.";
        }

        $user_id = (int) $user_id;
        $delivery_fee = (float) $delivery_fee;

        $payment_method = $data['payment_method'] ?? '';

        $allowed_methods = [
            'mobile_money',
            'card',
            'cash_on_delivery'
        ];

        if (!in_array($payment_method, $allowed_methods, true)) {
            return "Invalid payment method.";
        }

        $recipient_name = trim($data['recipient_name'] ?? '');
        $contact_phone = trim($data['contact_phone'] ?? '');
        $delivery_method = $data['delivery_method'] ?? 'delivery';

        $delivery_address = trim($data['delivery_address'] ?? '');
        $city = trim($data['city'] ?? '');
        $delivery_notes = trim($data['delivery_notes'] ?? '');
        $gift_message = trim($data['gift_message'] ?? '');

        if ($recipient_name === '' || $contact_phone === '') {
            return "Recipient name and contact phone are required.";
        }

        if (!in_array($delivery_method, ['delivery', 'pickup'], true)) {
            return "Invalid delivery method.";
        }

        if (
            $delivery_method === 'delivery' &&
            ($delivery_address === '' || $city === '')
        ) {
            return "Delivery address and city are required.";
        }

        if ($delivery_method === 'pickup') {
            $delivery_address = null;
            $city = null;
            $delivery_fee = 0;
        }

        $this->conn->begin_transaction();

        try {

            // Find and lock the customer's active cart
            $stmt = $this->conn->prepare(
                "SELECT cart_id
                 FROM carts
                 WHERE user_id = ?
                 AND status = 'active'
                 ORDER BY cart_id DESC
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $cart = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$cart) {
                $this->conn->rollback();
                return "Your cart is empty.";
            }

            $cart_id = (int) $cart['cart_id'];

            // Retrieve cart products and lock their stock records
            $stmt = $this->conn->prepare(
                "SELECT
                    ci.product_id,
                    ci.quantity,
                    p.product_name,
                    p.price,
                    p.stock_quantity,
                    p.is_active
                 FROM cart_items ci
                 INNER JOIN products p
                    ON ci.product_id = p.product_id
                 WHERE ci.cart_id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $cart_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $items = [];

            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }

            $stmt->close();

            if (empty($items)) {
                $this->conn->rollback();
                return "Your cart is empty.";
            }

            // Calculate subtotal and check stock
            $subtotal = 0;

            foreach ($items as $item) {

                if ((int) $item['is_active'] !== 1) {
                    $this->conn->rollback();
                    return $item['product_name'] . " is unavailable.";
                }

                if (
                    (int) $item['quantity'] <= 0 ||
                    (int) $item['quantity'] > (int) $item['stock_quantity']
                ) {
                    $this->conn->rollback();
                    return "Not enough stock for " . $item['product_name'] . ".";
                }

                $subtotal +=
                    (float) $item['price'] * (int) $item['quantity'];
            }

            $total_amount = $subtotal + $delivery_fee;

            // Generate a unique order number
            $order_number = 'GLY-' . date('Ymd') . '-' .
                strtoupper(bin2hex(random_bytes(4)));

            // Save the main order
            $stmt = $this->conn->prepare(
                "INSERT INTO orders (
                    order_number,
                    user_id,
                    recipient_name,
                    contact_phone,
                    delivery_method,
                    delivery_address,
                    city,
                    delivery_notes,
                    gift_message,
                    subtotal,
                    delivery_fee,
                    total_amount
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sisssssssddd",
                $order_number,
                $user_id,
                $recipient_name,
                $contact_phone,
                $delivery_method,
                $delivery_address,
                $city,
                $delivery_notes,
                $gift_message,
                $subtotal,
                $delivery_fee,
                $total_amount
            );

            if (!$stmt->execute()) {
                throw new Exception("Unable to save the order.");
            }

            $order_id = $this->conn->insert_id;
            $stmt->close();

            // Save each purchased product
            $stmt = $this->conn->prepare(
                "INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_name,
                    unit_price,
                    quantity
                ) VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($items as $item) {

                $product_id = (int) $item['product_id'];
                $product_name = $item['product_name'];
                $unit_price = (float) $item['price'];
                $quantity = (int) $item['quantity'];

                $stmt->bind_param(
                    "iisdi",
                    $order_id,
                    $product_id,
                    $product_name,
                    $unit_price,
                    $quantity
                );

                if (!$stmt->execute()) {
                    throw new Exception("Unable to save order items.");
                }
            }

            $stmt->close();

            // Create a pending payment record
            $currency = 'GHS';

            $stmt = $this->conn->prepare(
                "INSERT INTO payments (
                    order_id,
                    payment_method,
                    amount,
                    currency
                ) VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "isds",
                $order_id,
                $payment_method,
                $total_amount,
                $currency
            );

            if (!$stmt->execute()) {
                throw new Exception("Unable to create payment record.");
            }

            $stmt->close();

            // Empty the cart after successfully creating the order
            $stmt = $this->conn->prepare(
                "DELETE FROM cart_items WHERE cart_id = ?"
            );

            $stmt->bind_param("i", $cart_id);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();

            return [
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order_number,
                'subtotal' => $subtotal,
                'delivery_fee' => $delivery_fee,
                'total_amount' => $total_amount
            ];

        } catch (Throwable $e) {

            $this->conn->rollback();
            error_log($e->getMessage());

            return "Unable to create your order. Please try again.";
        }
    }


    // Confirm an order and reduce product stock
    public function confirmOrder($order_id)
    {
        if (!is_numeric($order_id) || (int) $order_id <= 0) {
            return "Invalid order.";
        }

        $order_id = (int) $order_id;

        $this->conn->begin_transaction();

        try {

            // Check and lock the order
            $stmt = $this->conn->prepare(
                "SELECT order_status
                 FROM orders
                 WHERE order_id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$order) {
                throw new Exception("Order not found.");
            }

            if ($order['order_status'] !== 'pending') {
                throw new Exception("This order has already been processed.");
            }

            // Retrieve the products in the order
            $stmt = $this->conn->prepare(
                "SELECT product_id, quantity
                 FROM order_items
                 WHERE order_id = ?"
            );

            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $items = [];

            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }

            $stmt->close();

            if (empty($items)) {
                throw new Exception("No products found in this order.");
            }

            // Check and reduce stock for each product
            foreach ($items as $item) {

                $product_id = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];

                // Lock the product stock record
                $stmt = $this->conn->prepare(
                    "SELECT stock_quantity
                     FROM products
                     WHERE product_id = ?
                     FOR UPDATE"
                );

                $stmt->bind_param("i", $product_id);
                $stmt->execute();

                $product = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$product) {
                    throw new Exception("A product in this order was not found.");
                }

                if ((int) $product['stock_quantity'] < $quantity) {
                    throw new Exception("Insufficient stock for a product in this order.");
                }

                // Deduct the purchased quantity
                $stmt = $this->conn->prepare(
                    "UPDATE products
                     SET stock_quantity = stock_quantity - ?
                     WHERE product_id = ?
                     AND stock_quantity >= ?"
                );

                $stmt->bind_param(
                    "iii",
                    $quantity,
                    $product_id,
                    $quantity
                );

                if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                    $stmt->close();
                    throw new Exception("Unable to update product stock.");
                }

                $stmt->close();
            }

            // Mark the order as confirmed
            $stmt = $this->conn->prepare(
                "UPDATE orders
                 SET order_status = 'confirmed'
                 WHERE order_id = ?
                 AND order_status = 'pending'"
            );

            $stmt->bind_param("i", $order_id);

            if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                $stmt->close();
                throw new Exception("Unable to confirm the order.");
            }

            $stmt->close();

            $this->conn->commit();

            return true;

        } catch (Throwable $e) {

            $this->conn->rollback();
            error_log($e->getMessage());

            return $e->getMessage();
        }
    }


    // Retrieve a customer's previous orders
    public function getOrderHistory($user_id)
    {
        $stmt = $this->conn->prepare(
            "SELECT
                order_id,
                order_number,
                recipient_name,
                delivery_method,
                subtotal,
                delivery_fee,
                total_amount,
                order_status,
                placed_at
             FROM orders
             WHERE user_id = ?
             ORDER BY placed_at DESC"
        );

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $orders = [];

        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();

        return $orders;
    }


    // Retrieve one order belonging to a specific customer
    public function getOrderById($order_id, $user_id)
    {
        $stmt = $this->conn->prepare(
            "SELECT
                order_id,
                order_number,
                recipient_name,
                contact_phone,
                delivery_method,
                delivery_address,
                city,
                delivery_notes,
                gift_message,
                subtotal,
                delivery_fee,
                total_amount,
                order_status,
                placed_at
             FROM orders
             WHERE order_id = ?
             AND user_id = ?
             LIMIT 1"
        );

        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        $stmt->close();

        return $order ?: null;
    }


    // Retrieve the products belonging to one customer's order
    public function getOrderDetails($order_id, $user_id)
    {
        $stmt = $this->conn->prepare(
            "SELECT
                oi.order_item_id,
                oi.product_id,
                oi.product_name,
                oi.unit_price,
                oi.quantity,
                oi.line_total
             FROM order_items oi
             INNER JOIN orders o
                ON oi.order_id = o.order_id
             WHERE o.order_id = ?
             AND o.user_id = ?"
        );

        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $items = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();

        return $items;
    }
}

?>
