<?php
session_start();
require_once '../database/dbconfig.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Function to log messages for debugging
function logDebug($message) {
    error_log("[verify_password] " . $message);
}

logDebug("=== Password Verification Request Started ===");

// Check multiple session sources
$user_id = null;

// First check PHP session
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    logDebug("User ID found in PHP session: " . $user_id);
}
// If not in PHP session, check if user ID was sent in request body
else {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['user_id'])) {
        $user_id = intval($input['user_id']);
        logDebug("User ID received from request body: " . $user_id);
        
        // Restore PHP session from the user ID
        $_SESSION['user_id'] = $user_id;
        if (isset($input['user_name'])) {
            $_SESSION['user_name'] = $input['user_name'];
            $_SESSION['username'] = $input['user_name'];
        }
        logDebug("PHP session restored from request data");
    }
}

// If still no user ID, try to get from session storage via cookie or header
if (!$user_id && isset($_SERVER['HTTP_X_USER_ID'])) {
    $user_id = intval($_SERVER['HTTP_X_USER_ID']);
    logDebug("User ID from header: " . $user_id);
    if ($user_id) {
        $_SESSION['user_id'] = $user_id;
    }
}

if (!$user_id) {
    logDebug("No user ID found - user not logged in");
    echo json_encode([
        'success' => false, 
        'message' => 'User not logged in. Please login first.',
        'code' => 'NOT_LOGGED_IN'
    ]);
    exit();
}

// Get password from request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['password'])) {
    logDebug("Password not provided in request");
    echo json_encode(['success' => false, 'message' => 'Password required']);
    exit();
}

$password = $input['password'];

logDebug("Verifying password for user_id: " . $user_id);

// Get user's hashed password from database
$stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE id = ?");
if (!$stmt) {
    logDebug("Database prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    logDebug("User not found for ID: " . $user_id);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

logDebug("User found: " . $user['name']);

// Verify password
$passwordValid = password_verify($password, $user['password']);

if ($passwordValid) {
    logDebug("Password verified successfully for user: " . $user['name']);
    
    // Update session to keep it alive
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['username'] = $user['name'];
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Password verified successfully',
        'user_name' => $user['name'],
        'user_id' => $user['id']
    ]);
} else {
    logDebug("Password verification FAILED for user_id: " . $user_id);
    echo json_encode([
        'success' => false, 
        'message' => 'Incorrect password. Please try again.',
        'code' => 'INVALID_PASSWORD'
    ]);
}

$conn->close();
?>