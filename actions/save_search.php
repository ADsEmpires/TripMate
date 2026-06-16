<?php
// actions/save_search.php — Saves search queries to search_history
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error','message'=>'Not logged in']); exit(); }

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$query = $data['query'] ?? $data['search_query'] ?? '';

if (empty($query)) { echo json_encode(['status'=>'error','message'=>'No search query']); exit(); }

// Check for existing recent duplicate
$check = $conn->prepare("SELECT id FROM search_history WHERE user_id = ? AND search_query = ? AND search_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$check->bind_param("is", $user_id, $query);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['status'=>'success','message'=>'Already recorded']);
    $check->close(); $conn->close(); exit();
}
$check->close();

$stmt = $conn->prepare("INSERT INTO search_history (user_id, search_query, search_date) VALUES (?, ?, NOW())");
$stmt->bind_param("is", $user_id, $query);
$stmt->execute();

echo json_encode(['status'=>'success','message'=>'Search saved']);
$stmt->close();
$conn->close();
?>
