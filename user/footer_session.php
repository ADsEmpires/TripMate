<?php

/**
 * Universal Footer & Session Manager Include
 * File: user/footer_session.php
 * 
 * Include this file before closing </body> on any page that needs
 * persistent sessions across all pages.
 * 
 * Usage in PHP pages:
 *   <?php include 'footer_session.php'; ?>
 * </body>
 */

// Ensure session is initialized before using session data
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/session_init.php';
}

// Ensure base_url is set (fallback if not already defined)
if (!isset($base_url)) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = $protocol . "://" . $host . str_replace('/user', '', $script_dir);
}

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? '';
?>

<!-- Session Manager Scripts - Keep session alive across all pages -->
<script>
    // Store session info globally for JavaScript
    window.tripmate_session = {
        user_id: <?php echo json_encode($user_id); ?>,
        user_name: <?php echo json_encode($user_name); ?>,
        last_activity: <?php echo time(); ?>
    };
</script>

<!-- Keep-alive system for persistent sessions -->
<script src="<?php echo $base_url; ?>/user/session-keepalive.js" async></script>

<!-- Session synchronization across browser tabs -->
<script src="<?php echo $base_url; ?>/user/session-sync.js" async></script>

<!-- Auto-logout after inactivity -->
<script src="<?php echo $base_url; ?>/user/auto-logout.js" async></script>