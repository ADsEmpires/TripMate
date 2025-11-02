<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get favorite destinations with details
$stmt = $conn->prepare("
    SELECT d.id, d.name, d.type, d.location, d.budget, d.image_urls, d.description
    FROM user_history uh
    JOIN destinations d ON JSON_EXTRACT(uh.activity_details, '$.id') = d.id
    WHERE uh.user_id = ? AND uh.activity_type = 'favorite'
    ORDER BY uh.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}

echo json_encode(['status' => 'success', 'favorites' => $favorites]);
$stmt->close();
$conn->close();
?>