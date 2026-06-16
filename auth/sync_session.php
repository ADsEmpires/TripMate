<?php
// auth/sync_session.php — Syncs client-side session data with server
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['status'=>'error','message'=>'No user ID']);
    exit();
}

// Check if session matches
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
    echo json_encode([
        'status' => 'success',
        'synced' => true,
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? '',
        'user_email' => $_SESSION['user_email'] ?? ''
    ]);
    exit();
}

// Try to restore session from database
$stmt = $conn->prepare("SELECT id, name, email, auth_provider, user_level FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['auth_provider'] = $user['auth_provider'];
    $_SESSION['user_level'] = $user['user_level'];
    
    echo json_encode([
        'status' => 'success',
        'synced' => true,
        'user_id' => $user['id'],
        'user_name' => $user['name']
    ]);
} else {
    echo json_encode(['status'=>'error','message'=>'User not found','synced'=>false]);
}
$conn->close();
?>
