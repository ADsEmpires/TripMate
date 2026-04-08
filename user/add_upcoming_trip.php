<?php
// user/add_upcoming_trip.php
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

$destination_id = isset($_POST['destination_id']) ? intval($_POST['destination_id']) : 0;
$destination_name = isset($_POST['destination_name']) ? trim($_POST['destination_name']) : '';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$travelers = isset($_POST['travelers']) ? intval($_POST['travelers']) : 1;
$budget = isset($_POST['budget']) ? floatval($_POST['budget']) : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if (!$destination_name || !$start_date || !$end_date) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

// Check if trip already exists
$check_stmt = $conn->prepare("SELECT id FROM upcoming_trips WHERE user_id = ? AND destination_id = ? AND start_date = ? AND status = 'upcoming'");
$check_stmt->bind_param("iis", $user_id, $destination_id, $start_date);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'This trip already exists in your planner']);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// Insert trip
$insert_stmt = $conn->prepare("INSERT INTO upcoming_trips (user_id, destination_id, destination_name, start_date, end_date, travelers, budget, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$insert_stmt->bind_param("iissiids", $user_id, $destination_id, $destination_name, $start_date, $end_date, $travelers, $budget, $notes);

if ($insert_stmt->execute()) {
    // Record in user_history
    $history_stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'trip_plan', ?)");
    $history_details = json_encode(['destination_id' => $destination_id, 'destination_name' => $destination_name, 'start_date' => $start_date]);
    $history_stmt->bind_param("is", $user_id, $history_details);
    $history_stmt->execute();
    $history_stmt->close();
    
    echo json_encode(['status' => 'success', 'message' => 'Trip added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add trip']);
}

$insert_stmt->close();
$conn->close();
?>