
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$auth = new AuthController();
$error = '';

$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $auth->loginUser($email, $password);

    if ($result === true) {
        header("Location: ../../index.php");
        exit();
    }

    $error = $result;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Giftly</title>
</head>

<body>

    <h1>Login to Giftly</h1>

    <p>Welcome back! Please log in to your account.</p>

    <?php if ($success !== ''): ?>
        <p><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Email:</label>
        <input
            type="email"
            name="email"
            required
        >

        <br><br>

        <label>Password:</label>
        <input
            type="password"
            name="password"
            required
        >

        <br><br>

        <button type="submit">Login</button>

    </form>

    <p>
        Don't have an account?
        <a href="../register/register.php">Register here</a>
    </p>

</body>
</html>
