
<?php

require_once __DIR__ . '/../config/database.php';

class Cart
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // Get an existing active cart or create a new one
    private function getActiveCartId($user_id, $session_id)
    {
        if ($user_id !== null) {

            $stmt = $this->conn->prepare(
                "SELECT cart_id FROM carts
                 WHERE user_id = ? AND status = 'active'
                 ORDER BY cart_id DESC LIMIT 1"
            );

            $stmt->bind_param("i", $user_id);

        } else {

            $stmt = $this->conn->prepare(
                "SELECT cart_id FROM carts
                 WHERE session_id = ?
                 AND user_id IS NULL
                 AND status = 'active'
                 ORDER BY cart_id DESC LIMIT 1"
            );

            $stmt->bind_param("s", $session_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_assoc();
        $stmt->close();

        if ($cart) {
            return (int) $cart['cart_id'];
        }

        // Create a new cart if one does not exist
        if ($user_id !== null) {

            $stmt = $this->conn->prepare(
                "INSERT INTO carts (user_id, status)
                 VALUES (?, 'active')"
            );

            $stmt->bind_param("i", $user_id);

        } else {

            $stmt = $this->conn->prepare(
                "INSERT INTO carts (session_id, status)
                 VALUES (?, 'active')"
            );

            $stmt->bind_param("s", $session_id);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $cart_id = $this->conn->insert_id;
        $stmt->close();

        return (int) $cart_id;
    }

    // Add a product to the cart
    public function addItem($product_id, $quantity, $user_id, $session_id)
    {
        $cart_id = $this->getActiveCartId($user_id, $session_id);

        if (!$cart_id) {
            return "Unable to create or retrieve the cart.";
        }

        $this->conn->begin_transaction();

        try {

            // Check whether the product exists and is available
            $stmt = $this->conn->prepare(
                "SELECT stock_quantity, is_active
                 FROM products
                 WHERE product_id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            if (!$product || (int) $product['is_active'] !== 1) {
                $this->conn->rollback();
                return "This product is unavailable.";
            }

            $stock = (int) $product['stock_quantity'];

            if ($stock < $quantity) {
                $this->conn->rollback();
                return "Not enough stock available.";
            }

            // Check whether the product is already in the cart
            $stmt = $this->conn->prepare(
                "SELECT cart_item_id, quantity
                 FROM cart_items
                 WHERE cart_id = ? AND product_id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("ii", $cart_id, $product_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $existing_item = $result->fetch_assoc();
            $stmt->close();

            if ($existing_item) {

                $new_quantity =
                    (int) $existing_item['quantity'] + $quantity;

                if ($new_quantity > $stock) {
                    $this->conn->rollback();
                    return "The requested quantity exceeds available stock.";
                }

                $stmt = $this->conn->prepare(
                    "UPDATE cart_items
                     SET quantity = ?
                     WHERE cart_item_id = ? AND cart_id = ?"
                );

                $cart_item_id = (int) $existing_item['cart_item_id'];

                $stmt->bind_param(
                    "iii",
                    $new_quantity,
                    $cart_item_id,
                    $cart_id
                );

            } else {

                $stmt = $this->conn->prepare(
                    "INSERT INTO cart_items
                     (cart_id, product_id, quantity)
                     VALUES (?, ?, ?)"
                );

                $stmt->bind_param(
                    "iii",
                    $cart_id,
                    $product_id,
                    $quantity
                );
            }

            if (!$stmt->execute()) {
                $stmt->close();
                $this->conn->rollback();
                return "Unable to add the product to the cart.";
            }

            $stmt->close();
            $this->conn->commit();

            return true;

        } catch (Throwable $e) {

            $this->conn->rollback();
            error_log($e->getMessage());

            return "An error occurred while adding the product.";
        }
    }

    // Retrieve all products in the cart
    public function getCartItems($user_id, $session_id)
    {
        $cart_id = $this->getActiveCartId($user_id, $session_id);

        if (!$cart_id) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT
                ci.cart_item_id,
                ci.product_id,
                ci.quantity,
                p.product_name,
                p.price,
                p.image_path,
                p.stock_quantity,
                p.is_active,
                (p.price * ci.quantity) AS line_total
             FROM cart_items ci
             INNER JOIN products p
                ON ci.product_id = p.product_id
             WHERE ci.cart_id = ?
             ORDER BY ci.added_at DESC"
        );

        $stmt->bind_param("i", $cart_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $items = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();

        return $items;
    }

    // Update a product quantity
    public function updateItem(
        $cart_item_id,
        $quantity,
        $user_id,
        $session_id
    ) {
        $cart_id = $this->getActiveCartId($user_id, $session_id);

        if (!$cart_id) {
            return "Cart not found.";
        }

        // Confirm that this item belongs to the current cart
        $stmt = $this->conn->prepare(
            "SELECT ci.product_id, p.stock_quantity, p.is_active
             FROM cart_items ci
             INNER JOIN products p
                ON ci.product_id = p.product_id
             WHERE ci.cart_item_id = ? AND ci.cart_id = ?
             LIMIT 1"
        );

        $stmt->bind_param("ii", $cart_item_id, $cart_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();

        if (!$item) {
            return "Cart item not found.";
        }

        if ((int) $item['is_active'] !== 1) {
            return "This product is unavailable.";
        }

        if ($quantity > (int) $item['stock_quantity']) {
            return "The requested quantity exceeds available stock.";
        }

        $stmt = $this->conn->prepare(
            "UPDATE cart_items
             SET quantity = ?
             WHERE cart_item_id = ? AND cart_id = ?"
        );

        $stmt->bind_param(
            "iii",
            $quantity,
            $cart_item_id,
            $cart_id
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success ? true : "Unable to update cart quantity.";
    }

    // Remove a product from the cart
    public function removeItem($cart_item_id, $user_id, $session_id)
    {
        $cart_id = $this->getActiveCartId($user_id, $session_id);

        if (!$cart_id) {
            return "Cart not found.";
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM cart_items
             WHERE cart_item_id = ? AND cart_id = ?"
        );

        $stmt->bind_param("ii", $cart_item_id, $cart_id);
        $stmt->execute();

        $deleted = $stmt->affected_rows;
        $stmt->close();

        return $deleted > 0
            ? true
            : "Cart item not found.";
    }

    // Calculate cart quantity and subtotal
    public function getCartSummary($user_id, $session_id)
    {
        $cart_id = $this->getActiveCartId($user_id, $session_id);

        if (!$cart_id) {
            return [
                'total_items' => 0,
                'subtotal' => 0
            ];
        }

        $stmt = $this->conn->prepare(
            "SELECT
                COALESCE(SUM(ci.quantity), 0) AS total_items,
                COALESCE(SUM(p.price * ci.quantity), 0) AS subtotal
             FROM cart_items ci
             INNER JOIN products p
                ON ci.product_id = p.product_id
             WHERE ci.cart_id = ?"
        );

        $stmt->bind_param("i", $cart_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();
        $stmt->close();

        return [
            'total_items' => (int) $summary['total_items'],
            'subtotal' => (float) $summary['subtotal']
        ];
    }
}

?>
