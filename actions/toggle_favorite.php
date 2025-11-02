<?php
// Toggle favorite endpoint
// Expects POST: destination_id (int), action ('add'|'remove')
// Returns JSON: { status: 'success'|'error', action: 'added'|'removed'|'exists', message: '...' }

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    // Not logged in
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

$input = file_get_contents('php://input');
parse_str($input, $post);

$destination_id = isset($post['destination_id']) ? intval($post['destination_id']) : 0;
$action = isset($post['action']) ? $post['action'] : '';

if (!$destination_id || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

require_once __DIR__ . '/../database/dbconfig.php';

$user_id = intval($_SESSION['user_id']);

// Prefer normalized favorites table. If not present, fallback to user_history JSON approach.
$use_favorites_table = true;
$check_table_sql = "SHOW TABLES LIKE 'favorites'";
$res = $conn->query($check_table_sql);
if ($res === false || $res->num_rows === 0) {
    $use_favorites_table = false;
}

try {
    if ($use_favorites_table) {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, destination_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $destination_id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'action' => 'added']);
            } else {
                // already exists
                echo json_encode(['status' => 'success', 'action' => 'exists']);
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND destination_id = ?");
            $stmt->bind_param("ii", $user_id, $destination_id);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'action' => 'removed']);
            $stmt->close();
        }
    } else {
        // fallback: user_history JSON usage
        if ($action === 'add') {
            $details = json_encode(['id' => $destination_id]);
            $stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'favorite', ?)");
            $stmt->bind_param("is", $user_id, $details);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'action' => 'added']);
            $stmt->close();
        } else {
            $stmt = $conn->prepare("DELETE FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND JSON_EXTRACT(activity_details, '$.id') = ?");
            $stmt->bind_param("ii", $user_id, $destination_id);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'action' => 'removed']);
            $stmt->close();
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}