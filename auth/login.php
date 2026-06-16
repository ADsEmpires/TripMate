<?php
// Start output buffering to prevent raw HTML/PHP warnings from breaking the JSON response
ob_start(); 
session_start();

// 1. Safely load the database file
$db_path = __DIR__ . '/../database/dbconfig.php';
if (file_exists($db_path)) {
    require_once $db_path;
}

// 2. Safely load session config (optional file)
$session_path = __DIR__ . '/session_config.php';
if (file_exists($session_path)) {
    require_once $session_path;
}

// Ensure the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Clear the output buffer so NO HTML gets printed before the JSON
    ob_clean(); 
    header('Content-Type: application/json');

    // Verify database connection is active
    if (!isset($conn) || !($conn instanceof mysqli)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Database connection failed. Please check dbconfig.php.'
        ]);
        exit;
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if empty
    if (empty($email) || empty($password)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Email and password are required.'
        ]);
        exit;
    }

    // Fetch user from DB
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify Password (checks both Bcrypt Hashes AND Plain Text for testing)
    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
        
        // 3. Set PHP session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['session_start_time'] = time();
        $_SESSION['last_activity'] = time();

        // 4. IP Tracking (Safe load)
        $ip_path = __DIR__ . '/../admin/ip_tracking.php';
        if (file_exists($ip_path)) {
            include_once $ip_path;
            if (function_exists('trackUserIP')) {
                trackUserIP($user['id'], $conn, 'user');
            }
        }

        // Return Success JSON
        echo json_encode([
            'status' => 'success',
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'redirect' => $_POST['redirect'] ?? ''
        ]);
        exit;
        
    } else {
        // Incorrect password or email
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password.'
        ]);
        exit;
    }
} else {
    // If someone visits login.php directly via the browser URL, redirect them.
    header('Location: login.html');
    exit;
}
?>