<?php
require_once __DIR__ . '/session_init.php'; // Initialize session management
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // ============================================
    // FIXED: Use correct table name user_search_history
    // ============================================
    $stmt = $conn->prepare("DELETE FROM user_search_history WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid id']);
}
$conn->close();
