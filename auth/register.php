<?php
session_start(); // Required for using $_SESSION

include '../database/dbconfig.php'; // Ensure this connects $conn

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $name = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($name) || empty($email) || empty($pass) || empty($confirm_pass)) {
        echo "All fields are required.";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }

    if ($pass !== $confirm_pass) {
        echo "Passwords do not match.";
        exit();
    }

    if (strlen($pass) < 6) {
        echo "Password must be at least 6 characters long.";
        exit();
    }

    // Check if email already exists in users table
    $check_user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_user_stmt->bind_param("s", $email);
    $check_user_stmt->execute();
    $check_user_stmt->store_result();

    if ($check_user_stmt->num_rows > 0) {
        $check_user_stmt->close();
        echo "Email already registered.";
        exit();
    }
    $check_user_stmt->close();

    // Hash password
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // Insert into users table only
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed_password);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['search_history'] = [];
        echo "Registration Successfully";
    } else {
        echo "Database error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>