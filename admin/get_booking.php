<?php
session_start();
require_once '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID provided.']);
    exit();
}

// Prepare statement for safety
$query = "SELECT booking_status, admin_notes FROM bookings WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit();
}

$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    echo json_encode([
        'success' => true, 
        'booking_status' => $booking['booking_status'], 
        'admin_notes' => $booking['admin_notes']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found in database.']);
}
?>