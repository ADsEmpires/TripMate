<?php
// actions/save_trip.php — Saves a planned trip to user_history
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error','message'=>'Not logged in']); exit(); }

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$destination = $data['destination'] ?? '';
$start_date = $data['start_date'] ?? '';
$end_date = $data['end_date'] ?? '';
$budget = $data['budget'] ?? 0;
$travelers = $data['travelers'] ?? 1;

if (empty($destination)) { echo json_encode(['status'=>'error','message'=>'Destination required']); exit(); }

$details = json_encode([
    'name' => $destination,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'budget' => $budget,
    'travelers' => $travelers
]);

$stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'trip', ?)");
$stmt->bind_param("is", $user_id, $details);
$stmt->execute();

echo json_encode(['status'=>'success','message'=>'Trip saved!']);
$stmt->close();
$conn->close();
?>
