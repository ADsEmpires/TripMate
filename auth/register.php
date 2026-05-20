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
    $check_user_stmt = $conn->prepare("SELECT id, auth_provider, name, profile_pic, password FROM users WHERE email = ?");
    $check_user_stmt->bind_param("s", $email);
    $check_user_stmt->execute();
    $check_user_result = $check_user_stmt->get_result();

    if ($check_user_result->num_rows > 0) {
        $existing_user = $check_user_result->fetch_assoc();
        $check_user_stmt->close();

        // If this is an old Google user in the main users table upgrading, allow it
        if ($existing_user['auth_provider'] === 'google') {
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            $new_provider = 'manual';
            $upgrade_stmt = $conn->prepare("UPDATE users SET name = ?, password = ?, auth_provider = ? WHERE id = ?");
            $upgrade_stmt->bind_param("sssi", $name, $hashed_password, $new_provider, $existing_user['id']);

            if ($upgrade_stmt->execute()) {
                // Clear old session data
                $_SESSION = array();
                
                // Set fresh session data
                $_SESSION['user_id'] = $existing_user['id'];
                $_SESSION['user_name'] = $name;
                $_SESSION['auth_provider'] = 'manual';
                $_SESSION['user_email'] = $email;
                $_SESSION['search_history'] = [];
                
                // Set profile pic if exists
                if (!empty($existing_user['profile_pic'])) {
                    $_SESSION['user_pic'] = $existing_user['profile_pic'];
                }
                
                echo "Registration Successfully";
            } else {
                echo "Database error during account upgrade.";
            }
            $upgrade_stmt->close();
            $conn->close();
            exit();
        }

        echo "Email already registered.";
        exit();
    }
    $check_user_stmt->close();

    // Check if this email is waiting in users_google to be upgraded
    $g_check_stmt = $conn->prepare("SELECT id, profile_pic, provider_id, name as google_name FROM users_google WHERE email = ?");
    $g_check_stmt->bind_param("s", $email);
    $g_check_stmt->execute();
    $g_check_result = $g_check_stmt->get_result();

    if ($g_check_result->num_rows > 0) {
        $google_user = $g_check_result->fetch_assoc();
        $g_check_stmt->close();
        
        // Hash password
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
        
        // Use the name from form or fallback to Google name
        $final_name = !empty($name) ? $name : ($google_user['google_name'] ?? explode('@', $email)[0]);
        
        // Insert into FULL `users` table including the profile_pic and provider_id!
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, auth_provider, profile_pic, provider_id) VALUES (?, ?, ?, 'manual', ?, ?)");
        $stmt->bind_param("sssss", $final_name, $email, $hashed_password, $google_user['profile_pic'], $google_user['provider_id']);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Clear old session data
            $_SESSION = array();
            
            // Set fresh session data
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $final_name;
            $_SESSION['auth_provider'] = 'manual';
            $_SESSION['user_email'] = $email;
            $_SESSION['search_history'] = [];
            
            if (!empty($google_user['profile_pic'])) {
                $_SESSION['user_pic'] = $google_user['profile_pic'];
            }
            
            // Cleanup from users_google to avoid duplicates
            $del_stmt = $conn->prepare("DELETE FROM users_google WHERE email = ?");
            $del_stmt->bind_param("s", $email);
            $del_stmt->execute();
            $del_stmt->close();

            echo "Registration Successfully";
            exit();
        } else {
            echo "Database error during account upgrade.";
            exit();
        }
    }
    $g_check_stmt->close();

    // Hash password
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // Insert into users table only
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, auth_provider) VALUES (?, ?, ?, 'manual')");
    $stmt->bind_param("sss", $name, $email, $hashed_password);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Clear old session data
        $_SESSION = array();
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['auth_provider'] = 'manual';
        $_SESSION['user_email'] = $email;
        $_SESSION['search_history'] = [];

        // Track User IP
        if (file_exists('../admin/ip_tracking.php')) {
            require_once '../admin/ip_tracking.php';
            if (function_exists('trackUserIP')) {
                trackUserIP($user_id, $conn, 'user');
            }
        }

        echo "Registration Successfully";
    } else {
        echo "Database error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>