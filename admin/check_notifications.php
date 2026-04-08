<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../database/dbconfig.php';

$new_messages = $conn->query("SELECT COUNT(*) as total FROM messages WHERE status = 'unread'")->fetch_assoc()['total'] ?? 0;
$new_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;

header('Content-Type: application/json');
echo json_encode([
    'new_messages' => $new_messages,
    'new_bookings' => $new_bookings,
    'timestamp' => time()
]);
?>