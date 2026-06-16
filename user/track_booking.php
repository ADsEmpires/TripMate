<?php
// user/track_booking.php — Tracks booking activity/status
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error','message'=>'Not logged in']); exit(); }

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$booking_id = $data['booking_id'] ?? null;
$action = $data['action'] ?? 'view';

if (!$booking_id) { echo json_encode(['status'=>'error','message'=>'No booking ID']); exit(); }

// Record tracking event
$details = json_encode([
    'booking_id' => $booking_id,
    'action' => $action,
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

$stmt = $conn->prepare("INSERT INTO user_history (user_id, activity_type, activity_details) VALUES (?, 'booking', ?)");
$stmt->bind_param("is", $user_id, $details);
$stmt->execute();

echo json_encode(['status'=>'success','message'=>'Booking tracked']);
$stmt->close();
$conn->close();
?>
