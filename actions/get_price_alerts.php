<?php
// actions/get_price_alerts.php — Get user's price alerts
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error','message'=>'Not logged in']); exit(); }

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM price_alerts WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$alerts = [];
while ($row = $result->fetch_assoc()) {
    $alerts[] = $row;
}

echo json_encode(['status'=>'success','alerts'=>$alerts]);
$stmt->close();
$conn->close();
?>
