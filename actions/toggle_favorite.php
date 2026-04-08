<?php
// actions/toggle_favorite.php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

// Handle both FormData and URL-encoded data
$input = $_POST;
if (empty($input)) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
}

$destination_id = isset($input['destination_id']) ? intval($input['destination_id']) : 0;
$action = isset($input['action']) ? $input['action'] : '';

if (!$destination_id || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

require_once __DIR__ . '/../database/dbconfig.php';

$user_id = intval($_SESSION['user_id']);

try {
    if ($action === 'add') {
        // Check if already exists in user_history (to match search.php approach)
        $check = $conn->prepare("SELECT id FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND activity_details = ?");
        $check->bind_param("is", $user_id, $destination_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            // Insert into user_history (search.php expects this format)
            $stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details, created_at) VALUES (?, 'favorite', ?, NOW())");
            $stmt->bind_param("is", $user_id, $destination_id);
            $stmt->execute();
            
            // Also insert into favorites table if it exists (for our queries)
            $fav_check = $conn->prepare("INSERT IGNORE INTO favorites (user_id, destination_id, created_at) VALUES (?, ?, NOW())");
            $fav_check->bind_param("ii", $user_id, $destination_id);
            $fav_check->execute();
            $fav_check->close();
            
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'action' => 'added']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to add favorite']);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'success', 'action' => 'exists']);
        }
        $check->close();
    } else {
        // Remove favorite from user_history (search.php approach)
        $stmt = $conn->prepare("DELETE FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND activity_details = ?");
        $stmt->bind_param("is", $user_id, $destination_id);
        $stmt->execute();
        
        // Also remove from favorites table
        $fav_del = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND destination_id = ?");
        $fav_del->bind_param("ii", $user_id, $destination_id);
        $fav_del->execute();
        $fav_del->close();
        
        echo json_encode(['status' => 'success', 'action' => 'removed']);
        $stmt->close();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>