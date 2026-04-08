<?php
/**
 * ADMIN SESSION CHECK & AUTO-LOGOUT
 * 
 * This file handles:
 * 1. Session authentication check
 * 2. Session timeout/inactivity logout
 * 3. Redirect to login if not authenticated
 * 
 * Include this file at the TOP of every admin page
 * Usage: include 'admin_session_check.php';
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout duration (in seconds)
// 30 minutes = 1800 seconds
// Change this value to adjust timeout duration
define('SESSION_TIMEOUT', 1800);

// Redirect URL for login
define('LOGIN_REDIRECT', 'admin_login.php');

// List of files that don't require login check (besides admin_login.php)
$no_auth_required = ['admin_login.php', 'admin_logout.php'];

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Check if login is required for this page
if (!in_array($current_page, $no_auth_required)) {
    
    // Check if user is logged in
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // User not logged in - redirect to login
        header("Location: " . LOGIN_REDIRECT);
        exit();
    }
    
    // Check if session timeout is set
    if (!isset($_SESSION['last_activity'])) {
        // First activity on this page, set timestamp
        $_SESSION['last_activity'] = time();
    } else {
        // Check if session has expired due to inactivity
        $time_since_last_activity = time() - $_SESSION['last_activity'];
        
        if ($time_since_last_activity > SESSION_TIMEOUT) {
            // Session expired - clear all session data and redirect to login
            session_destroy();
            
            // Create a message to display on login page
            session_start(); // Restart session to store message
            $_SESSION['timeout_message'] = 'Your session expired due to inactivity. Please login again.';
            
            header("Location: " . LOGIN_REDIRECT);
            exit();
        }
    }
    
    // Update last activity timestamp to current time
    $_SESSION['last_activity'] = time();
    
    // Optional: Log admin activity
    // You can add logging here for security auditing
    // logAdminActivity($_SESSION['admin_id'], 'Page accessed: ' . $current_page);
}

/**
 * Optional: Get time remaining until logout (in seconds)
 * Usage: $timeout_remaining = getSessionTimeRemaining();
 */
function getSessionTimeRemaining() {
    if (isset($_SESSION['last_activity'])) {
        $time_elapsed = time() - $_SESSION['last_activity'];
        $time_remaining = SESSION_TIMEOUT - $time_elapsed;
        return max(0, $time_remaining);
    }
    return SESSION_TIMEOUT;
}

/**
 * Optional: Get time remaining in human readable format
 * Usage: echo getSessionTimeReadable();
 */
function getSessionTimeReadable() {
    $remaining = getSessionTimeRemaining();
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    return sprintf('%02d:%02d', $minutes, $seconds);
}

/**
 * Optional: Log admin activities for security auditing
 */
function logAdminActivity($admin_id, $action) {
    global $conn;
    
    // Create admin_activity_log table if it doesn't exist
    $check_table = "SHOW TABLES LIKE 'admin_activity_log'";
    $table_exists = $conn->query($check_table)->num_rows > 0;
    
    if (!$table_exists) {
        $create_table = "
            CREATE TABLE admin_activity_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                admin_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE,
                INDEX (admin_id, timestamp)
            )
        ";
        $conn->query($create_table);
    }
    
    // Insert activity log
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("
        INSERT INTO admin_activity_log (admin_id, action, ip_address, timestamp) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iss", $admin_id, $action, $ip);
    $stmt->execute();
}

?>
