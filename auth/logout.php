<?php
// ============================================
// CRITICAL: Complete session cleanup
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    session_start();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/', '', false, true);
}

// Destroy the session
session_destroy();

// Clear any other cookies related to user
setcookie('user_id', '', time() - 3600, '/');
setcookie('remember_me', '', time() - 3600, '/');
setcookie('userid', '', time() - 3600, '/');

// Handle both normal redirects and beacon requests
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    // Beacon request - just return success (no content)
    http_response_code(204);
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
        // Clear all user session data from browser storage
        localStorage.removeItem('tripmate_active_user_id');
        localStorage.removeItem('tripmate_active_user_id_legacy');
        localStorage.removeItem('tripmate_active_user_name');
        localStorage.removeItem('tripmate_active_user_name_legacy');

        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('user_name');
        sessionStorage.removeItem('userid');
        sessionStorage.removeItem('username');
        sessionStorage.removeItem('userId');
        sessionStorage.removeItem('userName');
        sessionStorage.removeItem('auth_provider');
        sessionStorage.removeItem('user_email');
        sessionStorage.removeItem('user_pic');

        // Remove the logged-in class
        document.body.classList.remove('user-logged-in');

        // Redirect to home
        window.location.href = '../main/index.html';
    </script>
    <p>Logging out... Please wait.</p>
</body>

</html>