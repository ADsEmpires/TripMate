<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

// For demo purposes, we'll just return random destinations
// In a real app, you would analyze user history to make recommendations
$stmt = $conn->prepare("
    SELECT id, name, type, location, budget, image_urls 
    FROM destinations 
    ORDER BY RAND() 
    LIMIT 3
");
$stmt->execute();
$result = $stmt->get_result();

$recommendations = [];
while ($row = $result->fetch_assoc()) {
    $recommendations[] = $row;
}

echo json_encode(['status' => 'success', 'recommendations' => $recommendations]);
$stmt->close();
$conn->close();
?>
