
<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // Register a new customer
    public function register($first_name, $last_name, $email, $phone, $password)
    {
        // Check if email already exists
        $check = $this->conn->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $check->close();
            return "Email already exists.";
        }

        $check->close();

        // Get the Customer role ID
        $role = $this->conn->prepare(
            "SELECT role_id FROM roles WHERE role_name = 'Customer' LIMIT 1"
        );

        $role->execute();
        $role_result = $role->get_result();
        $role_data = $role_result->fetch_assoc();
        $role->close();

        if (!$role_data) {
            return "Customer role was not found.";
        }

        $role_id = $role_data['role_id'];

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new customer
        $stmt = $this->conn->prepare(
            "INSERT INTO users
            (role_id, first_name, last_name, email, phone, password_hash)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "isssss",
            $role_id,
            $first_name,
            $last_name,
            $email,
            $phone,
            $password_hash
        );

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }

        $stmt->close();
        return "Registration failed. Please try again.";
    }


    // Get customer profile using user ID
    public function getProfileById($user_id)
    {
        $stmt = $this->conn->prepare(
            "SELECT user_id, first_name, last_name, email, phone
             FROM users
             WHERE user_id = ?
             LIMIT 1"
        );

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();

        $stmt->close();

        return $profile;
    }

    // Find a user by email
    public function findByEmail($email)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        return $user;
    }
}

?>
