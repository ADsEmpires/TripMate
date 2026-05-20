<?php
session_start();
include '../database/dbconfig.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Allow both authenticated and guest users for demo
// Uncomment the check below if you require authentication
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
//     exit();
// }

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'] ?? null; // Allow null for demo
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
    // Validate dates
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    if ($start > $end) {
        throw new Exception('End date must be after start date');
    }
    
    $days = $start->diff($end)->days + 1;
    
    if ($days < 1) {
        throw new Exception('Trip must be at least 1 day long');
    }
    
    // Get destination name
    $dest_name = getDestinationName($conn, $destination_id);
    if (!$dest_name) {
        throw new Exception('Invalid destination selected');
    }
    
    // If user is authenticated, save to database
    if ($user_id) {
        $stmt = $conn->prepare("
            INSERT INTO itineraries 
            (user_id, destination_id, title, start_date, end_date, budget, travel_style, preferences, generated_by_ai)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $title = "Trip to " . $dest_name;
        $prefs_json = json_encode($preferences);
        
        $stmt->bind_param("iisssdss", $user_id, $destination_id, $title, $start_date, $end_date, $budget, $travel_style, $prefs_json);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save itinerary: ' . $stmt->error);
        }
        
        $itinerary_id = $conn->insert_id;
        
        // Generate AI itinerary in database
        generateAIItinerary($conn, $itinerary_id, $destination_id, $days, $budget, $travel_style, $preferences, $start_date);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Itinerary generated successfully',
            'itinerary_id' => $itinerary_id
        ]);
    } else {
        // Demo mode: generate without saving to database
        $itinerary_data = generateDemoItinerary($conn, $destination_id, $days, $budget, $travel_style, $start_date);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Itinerary generated successfully (Demo mode)',
            'data' => $itinerary_data
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();

// Generate demo itinerary without database save
function generateDemoItinerary($conn, $destination_id, $days, $budget, $travel_style, $start_date) {
    $daily_budget = $budget / $days;
    
    // Fetch destination
    $dest_stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
    $dest_stmt->bind_param("i", $destination_id);
    $dest_stmt->execute();
    $destination = $dest_stmt->get_result()->fetch_assoc();
    $dest_stmt->close();
    
    if (!$destination) {
        throw new Exception('Destination not found');
    }
    
    $activities = [];
    
    // Generate day-by-day activities
    for ($day = 1; $day <= $days; $day++) {
        $date = new DateTime($start_date);
        $date->modify("+".($day-1)." days");
        $current_date = $date->format('Y-m-d');
        
        $day_activities = [
            [
                'name' => 'Breakfast & Explore ' . $destination['name'],
                'type' => 'food',
                'time' => '08:00 AM - 11:00 AM',
                'cost' => $daily_budget * 0.2,
                'description' => 'Start your day with local breakfast and explore the main attractions'
            ],
            [
                'name' => 'Lunch at Local Restaurant',
                'type' => 'food',
                'time' => '12:00 PM - 02:00 PM',
                'cost' => $daily_budget * 0.25,
                'description' => 'Enjoy authentic local cuisine'
            ],
            [
                'name' => 'Adventure Activity',
                'type' => 'adventure',
                'time' => '03:00 PM - 06:00 PM',
                'cost' => $daily_budget * 0.35,
                'description' => 'Engage in exciting activities suited to your travel style'
            ],
            [
                'name' => 'Dinner & Rest',
                'type' => 'food',
                'time' => '07:00 PM - 09:00 PM',
                'cost' => $daily_budget * 0.2,
                'description' => 'Relax and enjoy dinner'
            ]
        ];
        
        $activities[] = [
            'day' => $day,
            'date' => $current_date,
            'activities' => $day_activities
        ];
    }
    
    return [
        'destination_name' => $destination['name'],
        'destination_location' => $destination['location'],
        'duration' => $days,
        'total_cost' => $budget,
        'daily_budget' => number_format($daily_budget, 2),
        'travel_style' => ucfirst($travel_style),
        'num_activities' => count($activities) * 4,
        'activities' => $activities
    ];
}

// Helper function to generate AI itinerary in database
function generateAIItinerary($conn, $itinerary_id, $destination_id, $days, $budget, $travel_style, $preferences, $start_date) {
    $daily_budget = $budget / $days;
    
    // Fetch destination details
    $dest_stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
    $dest_stmt->bind_param("i", $destination_id);
    $dest_stmt->execute();
    $destination = $dest_stmt->get_result()->fetch_assoc();
    $dest_stmt->close();
    
    if (!$destination) {
        throw new Exception('Destination not found');
    }
    
    $attractions = json_decode($destination['attractions'] ?? '[]', true);
    
    // Create day-by-day itinerary
    for ($day = 1; $day <= $days; $day++) {
        $date = new DateTime($start_date);
        $date->modify("+".($day-1)." days");
        $current_date = $date->format('Y-m-d');
        
        $day_title = "Day " . $day . " - " . $destination['name'];
        
        // Check if table exists before inserting
        $day_stmt = $conn->prepare("
            INSERT INTO itinerary_days 
            (itinerary_id, day_number, date, title, description, estimated_cost)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if (!$day_stmt) {
            continue; // Skip if table doesn't exist
        }
        
        $description = generateDayDescription($day, $days, $travel_style, $destination['name']);
        $day_stmt->bind_param("iissssd", $itinerary_id, $day, $current_date, $day_title, $description, $daily_budget);
        
        if ($day_stmt->execute()) {
            $itinerary_day_id = $conn->insert_id;
            generateDayActivities($conn, $itinerary_day_id, $attractions, $daily_budget, $travel_style, $day);
        }
        
        $day_stmt->close();
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
        
        if (!$stmt) {
            continue; // Skip if table doesn't exist
        }
        
        $activity_type = getActivityType($activity_name);
        $description = "Experience {$activity_name} and immerse yourself in the local culture.";
        
        $stmt->bind_param("issssdsi", $itinerary_day_id, $activity_name, $activity_type, $description, $time_required, $estimated_cost, $time_of_day, $priority);
        $stmt->execute();
        $stmt->close();
    }
}

function getActivityType($activity_name) {
    $types = ['sightseeing', 'adventure', 'food', 'wellness', 'cultural'];
    return $types[array_rand($types)];
}

function getDestinationName($conn, $destination_id) {
    $stmt = $conn->prepare("SELECT name FROM destinations WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['name'] ?? null;
}
?>