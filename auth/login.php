<?php
/**
 * Login Handler
 * File: auth/login.php
 * 
 * Handles traditional email/password login
 * Creates proper PHP session + returns JSON for frontend
 * CRITICAL: This file sets up the session that everything else depends on
 */

// ============================================
// CRITICAL: Configure session BEFORE session_start()
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    // Session security configuration
    ini_set('session.cookie_lifetime', 0); // Browser session only
    ini_set('session.gc_maxlifetime', 86400); // 24 hours server-side
    ini_set('session.cookie_httponly', 1); // No JavaScript access
    ini_set('session.cookie_samesite', 'Lax'); // CSRF protection
    
    // Secure flag for HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Set JSON header immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include database config
require_once __DIR__ . '/../database/dbconfig.php';

// ============================================
// Prevent double login
// ============================================
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User already logged in. Please logout first.'
    ]);
    http_response_code(400);
    exit;
}

// ============================================
// Only handle POST requests
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    http_response_code(405);
    exit;
}

// ============================================
// Get and validate credentials
// ============================================
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$redirect = isset($_POST['redirect']) ? trim($_POST['redirect']) : '../user/user_dashboard.php';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and password are required'
    ]);
    http_response_code(400);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format'
    ]);
    http_response_code(400);
    exit;
}

try {
    // ============================================
    // Query database for user
    // ============================================
    $stmt = $conn->prepare("SELECT id, name, email, password, profile_pic, user_level FROM users WHERE email = ?");
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // ============================================
    // Verify credentials
    // ============================================
    if (!$user) {
        // User not found - don't reveal this for security
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password'
        ]);
        exit;
    }
    
    if (!password_verify($password, $user['password'])) {
        // Password mismatch
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password'
        ]);
        exit;
    }
    
    // ============================================
    // CRITICAL: Create proper session
    // ============================================
    // Regenerate session ID for security (prevents session fixation)
    session_regenerate_id(true);
    
    // Set session variables - STANDARDIZED KEYS
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['userid'] = (int)$user['id']; // Backward compatibility
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['username'] = $user['name']; // Backward compatibility
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_pic'] = $user['profile_pic'] ?? '';
    $_SESSION['profile_pic'] = $user['profile_pic'] ?? ''; // Backward compatibility
    $_SESSION['user_level'] = $user['user_level'] ?? 'normal';
    
    // Session metadata for validation
    $_SESSION['created_at'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['session_created'] = time();
    $_SESSION['auth_provider'] = 'manual';
    $_SESSION['_regenerated'] = true;
    $_SESSION['last_validation'] = time();
    
    // Save session to ensure it's written
    if (!session_write_close()) {
        throw new Exception("Failed to write session");
    }
    
    // ============================================
    // Track login activity (optional)
    // ============================================
    if (file_exists(__DIR__ . '/../admin/ip_tracking.php')) {
        require_once __DIR__ . '/../admin/ip_tracking.php';
        if (function_exists('trackUserIP')) {
            trackUserIP($user['id'], $conn, 'login');
        }
    }
    
    // ============================================
    // Close database connection
    // ============================================
    if (isset($conn) && $conn) {
        $conn->close();
    }
    
    // ============================================
    // Return success response
    // ============================================
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user_id' => (int)$user['id'],
        'user_name' => $user['name'],
        'user_email' => $user['email'],
        'redirect' => $redirect ?: '../user/user_dashboard.php'
    ]);
    exit;
    
} catch (Exception $e) {
    // Log error but don't expose details to client
    error_log('Login error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred during login. Please try again.'
    ]);
    exit;
}
?>
