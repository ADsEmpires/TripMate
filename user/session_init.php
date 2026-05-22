<?php

/**
 * Session Initialization Helper - FIXED VERSION
 * File: user/session_init.php
 * 
 * CRITICAL: Include this file at the TOP of EVERY page that needs persistent sessions
 * Must be called BEFORE any output (including HTML)
 * 
 * FIXES:
 * - Proper session initialization order
 * - Clear validation logic
 * - Prevent premature session destruction
 * - Ensure consistency between PHP session and browser storage
 */

// ============================================
// ERROR SUPPRESSION FOR SESSION OPERATIONS
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================
// CRITICAL: Session settings MUST be set BEFORE session_start()
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    // ============================================
    // Set secure cookie parameters FIRST
    // ============================================
    $cookie_options = [
        'lifetime' => 0,  // Browser session (expires when browser closes)
        'path' => '/',
        'domain' => '',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    session_set_cookie_params($cookie_options);
    
    // ============================================
    // Set session garbage collection - IMPORTANT
    // ============================================
    // These MUST be set before session_start()
    ini_set('session.gc_maxlifetime', 7200); // 2 hours server-side
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    
    // ============================================
    // Set session ID generation parameters
    // ============================================
    ini_set('session.hash_function', 'sha256');
    ini_set('session.hash_bits_per_character', 5);
    
    // ============================================
    // CRITICAL: START SESSION
    // ============================================
    session_start();
    
    // ============================================
    // CRITICAL: Initialize session if new
    // ============================================
    if (!isset($_SESSION['_session_init'])) {
        $_SESSION['_session_init'] = true;
        $_SESSION['_created_at'] = time();
        $_SESSION['_last_activity'] = time();
        session_regenerate_id(true);
    }
    
    // ============================================
    // Update last activity time on EVERY page load
    // ============================================
    $_SESSION['_last_activity'] = time();
}

// ============================================
// CRITICAL: Validate session hasn't been tampered with
// ============================================
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null) {
    // Check session age and inactivity
    $session_created = $_SESSION['_created_at'] ?? time();
    $last_activity = $_SESSION['_last_activity'] ?? time();
    $current_time = time();
    
    // Session timeout: 2 hours idle or 24 hours absolute
    $idle_timeout = 7200; // 2 hours
    $absolute_timeout = 86400; // 24 hours
    
    // Check if session has expired
    if (($current_time - $last_activity) > $idle_timeout || ($current_time - $session_created) > $absolute_timeout) {
        // Session expired - destroy it
        error_log('[SessionInit] Session expired for user ' . $_SESSION['user_id']);
        session_destroy();
        $_SESSION = [];
        setcookie(session_name(), '', time() - 3600, '/');
        // Don't exit - let the page handle the redirect
    } else {
        // Session is valid, update activity time
        $_SESSION['_last_activity'] = $current_time;
        
        // Regenerate session ID every 30 minutes for security
        if (!isset($_SESSION['_last_regenerate'])) {
            $_SESSION['_last_regenerate'] = time();
        }
        
        if (($current_time - $_SESSION['_last_regenerate']) > 1800) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerate'] = $current_time;
        }
    }
}

// ============================================
// Session Variable Normalization
// ============================================
// IMPORTANT: Use 'user_id' as the primary key everywhere
// These normalize any legacy or inconsistent key names

if (isset($_SESSION['userid']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['userid'];
}
if (isset($_SESSION['user_id']) && !isset($_SESSION['userid'])) {
    $_SESSION['userid'] = $_SESSION['user_id'];
}

if (isset($_SESSION['username']) && !isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = $_SESSION['username'];
}
if (isset($_SESSION['user_name']) && !isset($_SESSION['username'])) {
    $_SESSION['username'] = $_SESSION['user_name'];
}

if (isset($_SESSION['profile_pic']) && !isset($_SESSION['user_pic'])) {
    $_SESSION['user_pic'] = $_SESSION['profile_pic'];
}
if (isset($_SESSION['user_pic']) && !isset($_SESSION['profile_pic'])) {
    $_SESSION['profile_pic'] = $_SESSION['user_pic'];
}

// ============================================
// CRITICAL: Session Validation Against Database
// ============================================
// Validate user still exists in database (only if config exists)
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null && file_exists(__DIR__ . '/../database/dbconfig.php')) {
    // Check if validation is needed (every 30 minutes)
    $last_validation = $_SESSION['_last_db_validation'] ?? 0;
    
    if ((time() - $last_validation) > 1800) {
        require_once __DIR__ . '/../database/dbconfig.php';
        
        $user_id = $_SESSION['user_id'];
        $user_found = false;
        
        // Check if user exists in either users or users_google table
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user_found = true;
            }
            $stmt->close();
        }
        
        // If not found in users, check users_google
        if (!$user_found) {
            $stmt = $conn->prepare("SELECT id FROM users_google WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $user_found = true;
                }
                $stmt->close();
            }
        }
        
        if (!$user_found) {
            // User doesn't exist - destroy session
            error_log('[SessionInit] User ' . $user_id . ' not found in database. Destroying session.');
            session_destroy();
            $_SESSION = [];
            setcookie(session_name(), '', time() - 3600, '/');
        } else {
            // Update validation timestamp
            $_SESSION['_last_db_validation'] = time();
        }
    }
}

// ============================================
// CSRF Protection Functions
// ============================================

if (!function_exists('generateCSRFToken')) {
    /**
     * Generate a CSRF token and store it in session
     * 
     * @return string CSRF token
     */
    function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCSRFToken')) {
    /**
     * Validate CSRF token against session stored token
     * 
     * @param string $token Token to validate
     * @return bool True if valid
     */
    function validateCSRFToken($token)
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('getCSRFTokenField')) {
    /**
     * Get HTML hidden input field for CSRF token
     * 
     * @return string HTML input field
     */
    function getCSRFTokenField()
    {
        $token = generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

// ============================================
// Session Manager Scripts Function
// ============================================

if (!function_exists('getSessionManagerScripts')) {
    /**
     * Get session manager JavaScript includes for footer
     * 
     * @param string $base_url Base URL of the application (optional, auto-detected)
     * @return string HTML script tags with session data
     */
    function getSessionManagerScripts($base_url = '')
    {
        // Auto-detect base URL if not provided
        if (empty($base_url)) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $script_dir = dirname($_SERVER['SCRIPT_NAME']);
            // Remove /user from path if present
            $base_path = str_replace('/user', '', $script_dir);
            $base_url = $protocol . "://" . $host . $base_path;
        }

        $user_id = $_SESSION['user_id'] ?? null;
        $user_name = $_SESSION['user_name'] ?? '';
        $user_email = $_SESSION['user_email'] ?? '';
        $user_pic = $_SESSION['user_pic'] ?? '';

        // Output normalized session data as JSON for JavaScript
        $session_data = json_encode([
            'user_id' => $user_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_pic' => $user_pic,
            'is_logged_in' => !is_null($user_id),
            'session_started_at' => $_SESSION['_created_at'] ?? time()
        ]);

        $current_time = time();

        return <<<HTML
<!-- Session Manager Scripts - Keep session alive across all pages -->
<script>
    // Store session info globally (normalized)
    window.tripmate_session = $session_data;
    window.tripmate_session.last_activity = {$current_time};
    
    // Set sessionStorage keys (for client-side tracking)
    if (window.tripmate_session.user_id) {
        sessionStorage.setItem('user_id', window.tripmate_session.user_id);
        sessionStorage.setItem('user_name', window.tripmate_session.user_name);
        sessionStorage.setItem('user_email', window.tripmate_session.user_email);
        sessionStorage.setItem('user_pic', window.tripmate_session.user_pic);
        localStorage.setItem('tripmate_active_user_id', window.tripmate_session.user_id);
        localStorage.setItem('tripmate_active_user_name', window.tripmate_session.user_name);
        localStorage.setItem('tripmate_active_user_email', window.tripmate_session.user_email);
        localStorage.setItem('tripmate_active_user_pic', window.tripmate_session.user_pic);
        document.body.classList.add('user-logged-in');
        document.body.setAttribute('data-user-id', window.tripmate_session.user_id);
        document.body.setAttribute('data-user-name', window.tripmate_session.user_name);
    } else {
        document.body.classList.remove('user-logged-in');
    }
</script>
<meta name="user-id" content="{$user_id}">
<meta name="api-base" content="{$base_url}">
<script src="{$base_url}/user/session-keepalive.js" async></script>
<script src="{$base_url}/user/session-sync.js" async></script>
<script src="{$base_url}/user/auto-logout.js" async></script>
HTML;
    }
}
?>
