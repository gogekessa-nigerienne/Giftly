
<?php

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    // Handle customer registration
    public function registerUser($data)
    {
        $first_name = trim($data['first_name'] ?? '');
        $last_name = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';

        if (
            $first_name === '' ||
            $last_name === '' ||
            $email === '' ||
            $phone === '' ||
            $password === '' ||
            $confirm_password === ''
        ) {
            return "Please complete all fields.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Please enter a valid email address.";
        }

        if (strlen($password) < 8) {
            return "Password must contain at least 8 characters.";
        }

        if ($password !== $confirm_password) {
            return "Passwords do not match.";
        }

        return $this->user->register(
            $first_name,
            $last_name,
            $email,
            $phone,
            $password
        );
    }


    // Get the logged-in customer's profile
    public function getCustomerProfile($user_id)
    {
        if (!is_numeric($user_id) || (int)$user_id <= 0) {
            return "Invalid customer ID.";
        }

        $profile = $this->user->getProfileById((int)$user_id);

        if (!$profile) {
            return "Customer profile not found.";
        }

        return $profile;
    }


    // Handle customer login
    public function loginUser($email, $password)
    {
        $email = trim($email);

        if ($email === '' || $password === '') {
            return "Please enter your email and password.";
        }

        $user = $this->user->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return "Incorrect email or password.";
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];

        return true;
    }
}

?>
