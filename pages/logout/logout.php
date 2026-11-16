
<?php

require_once __DIR__ . '/../../includes/session.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Verify the CSRF token
$submitted_token = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['csrf_token'] ?? '';

if (
    $submitted_token === '' ||
    $session_token === '' ||
    !hash_equals($session_token, $submitted_token)
) {
    http_response_code(403);
    exit('Invalid logout request.');
}

// Clear all session data
$_SESSION = [];

// Expire the session cookie
if (ini_get('session.use_cookies')) {

    $cookie_params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $cookie_params['path'],
            'domain' => $cookie_params['domain'],
            'secure' => $cookie_params['secure'],
            'httponly' => $cookie_params['httponly'],
            'samesite' => $cookie_params['samesite'] ?? 'Lax'
        ]
    );
}

// Destroy the session
session_destroy();

// Redirect the customer to the login page
header("Location: ../login/login.php");
exit();

?>
