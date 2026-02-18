<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit();
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$destination_id = $data['destination_id'] ?? null;
$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;
$budget = $data['budget'] ?? null;
$travel_style = $data['travel_style'] ?? 'adventure';
$preferences = $data['preferences'] ?? [];

// Validate inputs
if (!$destination_id || !$start_date || !$end_date || !$budget) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

try {
    // Calculate number of days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $start->diff($end)->days + 1;
    
    // Insert itinerary
    $stmt = $conn->prepare("
        INSERT INTO itineraries 
        (user_id, destination_id, title, start_date, end_date, budget, travel_style, preferences, generated_by_ai)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $title = "Trip to " . getDestinationName($conn, $destination_id);
    $prefs_json = json_encode($preferences);
    
    $stmt->bind_param("iisssdss", $user_id, $destination_id, $title, $start_date, $end_date, $budget, $travel_style, $prefs_json);
    $stmt->execute();
    
    $itinerary_id = $conn->insert_id;
    
    // Generate AI itinerary
    generateAIItinerary($conn, $itinerary_id, $destination_id, $days, $budget, $travel_style, $preferences);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Itinerary generated successfully',
        'itinerary_id' => $itinerary_id
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();

// Helper function to generate AI itinerary
function generateAIItinerary($conn, $itinerary_id, $destination_id, $days, $budget, $travel_style, $preferences) {
    $daily_budget = $budget / $days;
    
    // Fetch destination details
    $dest_stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
    $dest_stmt->bind_param("i", $destination_id);
    $dest_stmt->execute();
    $destination = $dest_stmt->get_result()->fetch_assoc();
    $dest_stmt->close();
    
    $attractions = json_decode($destination['attractions'] ?? '[]', true);
    
    // Create day-by-day itinerary
    for ($day = 1; $day <= $days; $day++) {
        $date = new DateTime($GLOBALS['start_date']);
        $date->modify("+".($day-1)." days");
        $current_date = $date->format('Y-m-d');
        
        $day_title = "Day " . $day . " - " . $destination['name'];
        
        $day_stmt = $conn->prepare("
            INSERT INTO itinerary_days 
            (itinerary_id, day_number, date, title, description, estimated_cost)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $description = generateDayDescription($day, $days, $travel_style, $destination['name']);
        $day_stmt->bind_param("iissssd", $itinerary_id, $day, $current_date, $day_title, $description, $daily_budget);
        $day_stmt->execute();
        
        $itinerary_day_id = $conn->insert_id;
        $day_stmt->close();
        
        // Add activities for the day
        generateDayActivities($conn, $itinerary_day_id, $attractions, $daily_budget, $travel_style, $day);
    }
}

function generateDayDescription($day, $total_days, $travel_style, $destination) {
    $descriptions = [
        'adventure' => "Explore the exciting {$destination}! Start your day with an early breakfast and immerse yourself in thrilling activities.",
        'relaxation' => "Unwind and relax in the beautiful {$destination}. Enjoy a leisurely morning and soothing wellness activities.",
        'cultural' => "Discover the rich culture of {$destination}. Visit historical sites and experience local traditions.",
        'luxury' => "Indulge in premium experiences in {$destination}. Enjoy fine dining and exclusive accommodations.",
        'budget' => "Make the most of {$destination} on a budget! Explore free attractions and local street food."
    ];
    
    return $descriptions[$travel_style] ?? $descriptions['adventure'];
}

function generateDayActivities($conn, $itinerary_day_id, $attractions, $daily_budget, $travel_style, $day) {
    $time_slots = ['morning', 'afternoon', 'evening'];
    $cost_per_slot = $daily_budget / 3;
    
    foreach ($time_slots as $index => $time_of_day) {
        $activity_name = $attractions[$index % count($attractions)] ?? "Local Exploration";
        $estimated_cost = $cost_per_slot * 0.8;
        $time_required = ($time_of_day === 'morning') ? 180 : 240;
        $priority = 10 - $index;
        
        $stmt = $conn->prepare("
            INSERT INTO activity_suggestions 
            (itinerary_day_id, activity_name, activity_type, description, time_required, estimated_cost, time_of_day, priority)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $activity_type = getActivityType($activity_name);
        $description = "Experience {$activity_name} and immerse yourself in the local culture.";
        
        $stmt->bind_param("issssdsi", $itinerary_day_id, $activity_name, $activity_type, $description, $time_required, $estimated_cost, $time_of_day, $priority);
        $stmt->execute();
        $stmt->close();
    }
}

function getActivityType($activity_name) {
    $types = ['sightseeing' => 0, 'adventure' => 0.3, 'food' => 0.5, 'wellness' => 0.7, 'cultural' => 0.9];
    return array_keys($types)[array_rand($types)];
}

function getDestinationName($conn, $destination_id) {
    $stmt = $conn->prepare("SELECT name FROM destinations WHERE id = ?");
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['name'] ?? 'Unknown Destination';
}
?>