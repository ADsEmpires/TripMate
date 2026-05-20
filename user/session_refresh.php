<?php

/**
 * Session Refresh & Validation Endpoint
 * File: user/session_refresh.php
 * 
 * Purpose:
 * - Keep sessions alive with periodic pings
 * - Refresh user data from database
 * - Validate session status
 * - Return JSON responses for AJAX calls
 * 
 * IMPORTANT: This is an API endpoint - return JSON only
 */

// ============================================
// Set headers BEFORE any output
// ============================================
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ============================================
// Error handling
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================
// Start/resume session
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include session initialization to validate
require_once __DIR__ . '/session_init.php';
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
                'status' => 'ok'
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
                'message' => 'Invalid action',
                'status' => 'error'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
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
        $stmt = $conn->prepare("SELECT id, name, email, profile_pic, created_at FROM users WHERE id = ? LIMIT 1");
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
            $stmt = $conn->prepare("SELECT id, name, email, profile_pic, created_at FROM users_google WHERE id = ? LIMIT 1");
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
            return [
                'success' => false,
                'message' => 'User not found',
                'status' => 'user_not_found'
            ];
        }
        
        // Update session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['userid'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_pic'] = $user['profile_pic'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        $_SESSION['_last_activity'] = time();
        
        // Update activity timestamp
        $_SESSION['_last_activity'] = time();

        return [
            'success' => true,
            'message' => 'Session refreshed',
            'status' => 'ok',
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'profile_pic' => $user['profile_pic']
            ],
            'session_expires_in' => 7200 // 2 hours in seconds
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error refreshing session: ' . $e->getMessage(),
            'status' => 'error'
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
    
    // Reset cookie to extend expiration
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
}

/**
 * Check if session is still valid
 */
function checkSessionValidity($conn, $user_id)
{
    try {
        // Check if user still exists
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
            return [
                'success' => true,
                'message' => 'Session valid',
                'status' => 'ok',
                'is_valid' => true
            ];
        } else {
            return [
                'success' => false,
                'message' => 'User not found',
                'status' => 'user_not_found',
                'is_valid' => false
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error checking session: ' . $e->getMessage(),
            'status' => 'error',
            'is_valid' => false
        ];
    }
}
?>
