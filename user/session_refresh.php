<?php

/**
 * Session Refresh & Validation Endpoint - FIXED VERSION
 * File: user/session_refresh.php
 * 
 * FIXES:
 * - Proper session initialization
 * - Better error handling
 * - Clear status codes
 * - Prevent premature session destruction
 * - Proper error logging
 * 
 * Purpose:
 * - Keep sessions alive with periodic pings
 * - Refresh user data from database
 * - Validate session status
 * - Return JSON responses for AJAX calls
 * 
 * IMPORTANT: This is an API endpoint - return JSON only, no HTML
 */

// ============================================
// Set headers BEFORE any output
// ============================================
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Credentials: true');

// ============================================
// Error handling - DO NOT DISPLAY ERRORS
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================
// Start/resume session BEFORE anything else
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    // Configure session BEFORE starting
    ini_set('session.gc_maxlifetime', 7200);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// ============================================
// Include session initialization
// ============================================
require_once __DIR__ . '/session_init.php';

// ============================================
// Load database config
// ============================================
if (!file_exists(__DIR__ . '/../database/dbconfig.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database config not found',
        'status' => 'error'
    ]);
    exit;
}

require_once __DIR__ . '/../database/dbconfig.php';

// ============================================
// Check if user has active session
// ============================================
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No active session',
        'status' => 'unauthorized'
    ]);
    exit;
}

// ============================================
// Determine action
// ============================================
$action = $_GET['action'] ?? $_POST['action'] ?? 'keepalive';
$user_id = $_SESSION['user_id'];

// Log the action for debugging
error_log('[SessionRefresh] Action: ' . $action . ', User: ' . $user_id . ', Timestamp: ' . date('Y-m-d H:i:s'));

try {
    switch ($action) {
        case 'refresh':
            // Refresh user session data from database
            $response = refreshUserSession($conn, $user_id);
            echo json_encode($response);
            break;

        case 'keepalive':
            // Keep session alive by updating last_activity
            keepSessionAlive($user_id);
            echo json_encode([
                'success' => true,
                'message' => 'Session kept alive',
                'status' => 'ok',
                'timestamp' => time()
            ]);
            break;

        case 'check':
            // Check if session is still valid
            $response = checkSessionValidity($conn, $user_id);
            echo json_encode($response);
            break;

        case 'sync':
            // Full sync - refresh + keepalive
            keepSessionAlive($user_id);
            $response = refreshUserSession($conn, $user_id);
            echo json_encode($response);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action,
                'status' => 'error'
            ]);
    }
} catch (Exception $e) {
    error_log('[SessionRefresh] Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'status' => 'error'
    ]);
}

exit;

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Refresh user session from database
 */
function refreshUserSession($conn, $user_id)
{
    try {
        // Fetch latest user data from both possible tables
        $user = null;
        
        // Try users table first
        $stmt = $conn->prepare("SELECT id, name, email, profile_pic, user_level, created_at FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
            }
            $stmt->close();
        }
        
        // If not found, try users_google table
        if (!$user) {
            $stmt = $conn->prepare("SELECT id, name, email, profile_pic, user_level, created_at FROM users_google WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                }
                $stmt->close();
            }
        }
        
        if (!$user) {
            error_log('[SessionRefresh] User ' . $user_id . ' not found in database');
            return [
                'success' => false,
                'message' => 'User not found',
                'status' => 'user_not_found',
                'is_valid' => false
            ];
        }
        
        // Update session variables - CRITICAL for persistence
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['userid'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_pic'] = $user['profile_pic'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        $_SESSION['user_level'] = $user['user_level'] ?? 'normal';
        $_SESSION['_last_activity'] = time();
        
        // Force write session to disk immediately
        session_write_close();
        session_start();

        error_log('[SessionRefresh] Session refreshed for user ' . $user_id);

        return [
            'success' => true,
            'message' => 'Session refreshed',
            'status' => 'ok',
            'is_valid' => true,
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'profile_pic' => $user['profile_pic']
            ],
            'session_expires_in' => 7200 // 2 hours in seconds
        ];
    } catch (Exception $e) {
        error_log('[SessionRefresh] Exception in refreshUserSession: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error refreshing session',
            'status' => 'error',
            'is_valid' => false
        ];
    }
}

/**
 * Keep session alive by updating last activity time
 */
function keepSessionAlive($user_id)
{
    $_SESSION['user_id'] = $user_id;
    $_SESSION['_last_activity'] = time();
    
    // Reset cookie to extend expiration - CRITICAL
    $cookieParams = session_get_cookie_params();
    setcookie(
        session_name(),
        session_id(),
        0, // Browser session
        $cookieParams['path'],
        $cookieParams['domain'],
        $cookieParams['secure'],
        $cookieParams['httponly']
    );
    
    error_log('[SessionRefresh] Keep-alive: User ' . $user_id . ' at ' . date('Y-m-d H:i:s'));
}

/**
 * Check if session is still valid
 */
function checkSessionValidity($conn, $user_id)
{
    try {
        // Check if user still exists in users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $user_found = false;
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $user_found = true;
            }
            $stmt->close();
        }
        
        // Check users_google if not found
        if (!$user_found) {
            $stmt = $conn->prepare("SELECT id FROM users_google WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $user_found = true;
                }
                $stmt->close();
            }
        }
        
        if ($user_found) {
            $_SESSION['_last_activity'] = time();
            error_log('[SessionRefresh] Session valid for user ' . $user_id);
            return [
                'success' => true,
                'message' => 'Session valid',
                'status' => 'ok',
                'is_valid' => true
            ];
        } else {
            error_log('[SessionRefresh] User ' . $user_id . ' not found in database');
            return [
                'success' => false,
                'message' => 'User not found',
                'status' => 'user_not_found',
                'is_valid' => false
            ];
        }
    } catch (Exception $e) {
        error_log('[SessionRefresh] Exception in checkSessionValidity: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error checking session',
            'status' => 'error',
            'is_valid' => false
        ];
    }
}
?>
