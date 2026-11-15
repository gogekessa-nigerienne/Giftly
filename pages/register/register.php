
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$auth = new AuthController();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = $auth->registerUser($_POST);

    if ($result === true) {
        $_SESSION['success_message'] =
            "Your account has been created successfully. Please log in.";

        header("Location: ../login/login.php");
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
    <title>Register - Giftly</title>
</head>

<body>

    <h1>Create Your Giftly Account</h1>

    <p>Register to start shopping with Giftly.</p>

    <?php if ($error !== ''): ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>First Name:</label>
        <input
            type="text"
            name="first_name"
            required
            value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
        >

        <br><br>

        <label>Last Name:</label>
        <input
            type="text"
            name="last_name"
            required
            value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
        >

        <br><br>

        <label>Email:</label>
        <input
            type="email"
            name="email"
            required
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
        >

        <br><br>

        <label>Phone Number:</label>
        <input
            type="tel"
            name="phone"
            required
            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
        >

        <br><br>

        <label>Password:</label>
        <input
            type="password"
            name="password"
            required
            minlength="8"
        >

        <br><br>

        <label>Confirm Password:</label>
        <input
            type="password"
            name="confirm_password"
            required
            minlength="8"
        >

        <br><br>

        <button type="submit">Register</button>

    </form>

    <p>
        Already have an account?
        <a href="../login/login.php">Login here</a>
    </p>

</body>
</html>
