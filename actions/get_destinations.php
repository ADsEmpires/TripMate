<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database/dbconfig.php';

try {
    // Get all destinations from database
    $query = "SELECT id, name, location, type, description, budget, best_season, image_urls 
              FROM destinations 
              ORDER BY name ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $destinations = [];
    while ($row = $result->fetch_assoc()) {
        $destinations[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'location' => $row['location'],
            'type' => $row['type'],
            'description' => $row['description'],
            'budget' => (float)$row['budget'],
            'best_season' => $row['best_season'],
            'image_urls' => $row['image_urls']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'destinations' => $destinations,
        'count' => count($destinations)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>