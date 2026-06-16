<?php
session_start();

// Store user ID for logging before clearing session
$user_id = $_SESSION['user_id'] ?? null;

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/', '', false, true);
}

// Destroy the session
session_destroy();

// Clear any other cookies
setcookie('user_id', '', time() - 3600, '/', '', false, true);
setcookie('remember_me', '', time() - 3600, '/', '', false, true);
setcookie('tripmate_session_start', '', time() - 3600, '/', '', false, true);
setcookie('tripmate_last_activity', '', time() - 3600, '/', '', false, true);

// Handle both normal redirects and beacon requests
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    // Beacon request - just return success
    http_response_code(204); // No content
    exit();
}

// Output an HTML page that clears frontend storage then redirects
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
</head>
<body>
    <script>
        localStorage.removeItem('tripmate_active_user_id');
        localStorage.removeItem('tripmate_active_user_name');
        localStorage.removeItem('tripmate_session_start');
        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('user_name');
        sessionStorage.removeItem('auth_provider');
        sessionStorage.removeItem('user_email');
        sessionStorage.removeItem('user_pic');
        window.location.href = '../main/index.html';
    </script>
</body>
</html>