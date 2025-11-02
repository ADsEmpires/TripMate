<?php
// Start the session if not already started
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies related to login
setcookie('user_id', '', time() - 3600, '/');
setcookie('remember_me', '', time() - 3600, '/');

// Redirect to index.html
header("Location: ../main/index.html");
exit();
