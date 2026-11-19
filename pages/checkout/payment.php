<?php
session_start();

// Require the necessary files
require_once __DIR__ . '/../../config/payment.php';
require_once __DIR__ . '/../../config/db.php'; // Required by CheckoutController
require_once __DIR__ . '/../../controllers/CheckoutController.php';

$userEmail = $_SESSION['email'] ?? 'customer@example.com'; 
$totalAmount = 0;

// Use the CheckoutController instead of CartController
$checkoutController = new CheckoutController();

// Get the details of only the items selected for checkout (includes packaging)
$cartDetails = $checkoutController->getSelectedCartDetails();

if ($cartDetails && isset($cartDetails['summary']['subtotal'])) {
    $totalAmount = (float) $cartDetails['summary']['subtotal'];
}

// If total is 0, they didn't select any items or the cart is empty
if ($totalAmount <= 0) {
    // Redirect them back to the cart page (adjust path if your cart is in a different folder)
    header("Location: ../cart/cart.php"); 
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Checkout</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .pay-btn { background: #2c3e50; color: white; padding: 15px 30px; font-size: 18px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Complete Your Payment</h2>
    <p>Total: <strong>GHS <?php echo number_format($totalAmount, 2); ?></strong></p>
    <button id="payButton" class="pay-btn">Pay Now</button>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        document.getElementById('payButton').addEventListener('click', function() {
            var handler = PaystackPop.setup({
                key: '<?php echo PAYSTACK_PUBLIC_KEY; ?>',
                email: '<?php echo $userEmail; ?>',
                amount: <?php echo $totalAmount * 100; ?>,
                currency: '<?php echo PAYSTACK_CURRENCY; ?>',
                callback: function(response) {
                    // Go up to the includes folder to verify
                    window.location.href = '../../includes/verify_payment.php?reference=' + response.reference;
                },
                onClose: function() { alert('Payment cancelled.'); }
            });
            handler.openIframe();
        });
    </script>
</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Checkout</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .pay-btn { background: #2c3e50; color: white; padding: 15px 30px; font-size: 18px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Complete Your Payment</h2>
    <p>Total: <strong>GHS <?php echo number_format($totalAmount, 2); ?></strong></p>
    <button id="payButton" class="pay-btn">Pay Now</button>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        document.getElementById('payButton').addEventListener('click', function() {
            var handler = PaystackPop.setup({
                key: '<?php echo PAYSTACK_PUBLIC_KEY; ?>',
                email: '<?php echo $userEmail; ?>',
                amount: <?php echo $totalAmount * 100; ?>,
                currency: '<?php echo PAYSTACK_CURRENCY; ?>',
                callback: function(response) {
                    // Go up to the includes folder to verify
                    window.location.href = '../../includes/verify_payment.php?reference=' + response.reference;
                },
                onClose: function() { alert('Payment cancelled.'); }
            });
            handler.openIframe();
        });
    </script>
</body>
</html>