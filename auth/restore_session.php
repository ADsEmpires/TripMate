<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id']) || !isset($input['user_name'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$user_id = intval($input['user_id']);
$user_name = $input['user_name'];

// STEP 1: Check if user exists in `users` table (full accounts)
$query = "SELECT id, name, email, user_level FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    // Full account — restore session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['userid'] = $user['id'];
    $_SESSION['username'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_level'] = $user['user_level'] ?? 'normal';
    $_SESSION['auth_provider'] = 'manual';
    
    $user['auth_provider'] = 'manual';
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    // STEP 2: Check if user exists in `users_google` table (Google-only visitors)
    $g_query = "SELECT id, name, email, profile_pic, user_level FROM users_google WHERE id = ?";
    $g_stmt = $conn->prepare($g_query);
    $g_stmt->bind_param("i", $user_id);
    $g_stmt->execute();
    $g_result = $g_stmt->get_result();
    $g_user = $g_result->fetch_assoc();
    $g_stmt->close();

    if ($g_user) {
        // Google-only visitor — restore session
        $_SESSION['user_id'] = $g_user['id'];
        $_SESSION['user_name'] = $g_user['name'];
        $_SESSION['userid'] = $g_user['id'];
        $_SESSION['username'] = $g_user['name'];
        $_SESSION['user_email'] = $g_user['email'];
        $_SESSION['user_level'] = $g_user['user_level'] ?? 'normal';
        $_SESSION['auth_provider'] = 'google';

        $g_user['auth_provider'] = 'google';
        echo json_encode(['success' => true, 'user' => $g_user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

$conn->close();
?>