<?php
// user/save_search_history.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$search_query = isset($_POST['query']) ? trim($_POST['query']) : '';
$search_type = isset($_POST['type']) ? trim($_POST['type']) : 'destination';
$results_count = isset($_POST['results_count']) ? intval($_POST['results_count']) : 0;

if (!$search_query) {
    echo json_encode(['status' => 'error', 'message' => 'Search query is required']);
    exit();
}

// Insert into search history
$insert_stmt = $conn->prepare("INSERT INTO user_search_history (user_id, search_query, search_type, results_count) VALUES (?, ?, ?, ?)");
$insert_stmt->bind_param("issi", $user_id, $search_query, $search_type, $results_count);

if ($insert_stmt->execute()) {
    // Also record in user_history for activity feed
    $history_stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'search', ?)");
    $history_stmt->bind_param("is", $user_id, $search_query);
    $history_stmt->execute();
    $history_stmt->close();
    
    echo json_encode(['status' => 'success', 'message' => 'Search saved']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save search']);
}

$insert_stmt->close();
$conn->close();
?>