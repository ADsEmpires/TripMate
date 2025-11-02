<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if (!isset($_POST['destination_id']) || !isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$destination_id = intval($_POST['destination_id']);
$action = $_POST['action'];

// Get destination details
$dest_stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
$dest_stmt->bind_param("i", $destination_id);
$dest_stmt->execute();
$destination = $dest_stmt->get_result()->fetch_assoc();
$dest_stmt->close();

if (!$destination) {
    echo json_encode(['status' => 'error', 'message' => 'Destination not found']);
    exit();
}

if ($action === 'add') {
    // Check if already favorited
    $check_stmt = $conn->prepare("SELECT id FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND JSON_EXTRACT(activity_details, '$.id') = ?");
    $check_stmt->bind_param("ii", $user_id, $destination_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;
    $check_stmt->close();
    
    if ($exists) {
        echo json_encode(['status' => 'success', 'action' => 'exists']);
        exit();
    }
    
    // Prepare destination details for storage
    $activity_details = json_encode([
        'id' => $destination['id'],
        'name' => $destination['name'],
        'type' => $destination['type'],
        'location' => $destination['location'],
        'budget' => $destination['budget'],
        'image' => $destination['image_urls']
    ]);
    
    $insert_stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'favorite', ?)");
    $insert_stmt->bind_param("is", $user_id, $activity_details);
    $insert_stmt->execute();
    $insert_stmt->close();
    
    echo json_encode(['status' => 'success', 'action' => 'added']);
} elseif ($action === 'remove') {
    $delete_stmt = $conn->prepare("DELETE FROM user_history WHERE user_id = ? AND activity_type = 'favorite' AND JSON_EXTRACT(activity_details, '$.id') = ?");
    $delete_stmt->bind_param("ii", $user_id, $destination_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    echo json_encode(['status' => 'success', 'action' => 'removed']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

$conn->close();
?>