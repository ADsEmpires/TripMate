<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM search_history WHERE user_id=$user_id ORDER BY search_date DESC LIMIT 20");
$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
echo json_encode(['status'=>'success', 'history'=>$history]);
$conn->close();
?>