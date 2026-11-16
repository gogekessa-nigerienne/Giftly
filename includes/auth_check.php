
<?php

require_once __DIR__ . '/session.php';

if (!isset($_SESSION['user_id'])) {

    $_SESSION['error_message'] = "Please log in to access this page.";

    header("Location: ../pages/login/login.php");
    exit();
}

?>
