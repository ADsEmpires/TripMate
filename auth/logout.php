<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies
setcookie('user_id', '', time() - 3600, '/');
setcookie('remember_me', '', time() - 3600, '/');

// Handle both normal redirects and beacon requests
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    // Beacon request - just return success
    http_response_code(204); // No content
    exit();
}

// Normal logout - redirect to index
header("Location: ../main/index.html");
exit();
?>