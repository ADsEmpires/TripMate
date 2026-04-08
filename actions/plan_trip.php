<?php
// File: ../actions/plan_trip.php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in through various possible session keys
$user_id = null;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['userid']) && !empty($_SESSION['userid'])) {
    $user_id = $_SESSION['userid'];
} elseif (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to plan a trip']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit();
}

$destination_id = intval($input['destination_id'] ?? 0);
$departure_city = $input['departure_city'] ?? '';
$hotel_budget = floatval($input['hotel_budget'] ?? 5000);
$flight_budget = floatval($input['flight_budget'] ?? 25000);
$start_date = $input['start_date'] ?? '';
$end_date = $input['end_date'] ?? '';
$travelers = intval($input['travelers'] ?? 1);
$free_cancellation = isset($input['free_cancellation']) ? filter_var($input['free_cancellation'], FILTER_VALIDATE_BOOLEAN) : false;
$breakfast_included = isset($input['breakfast_included']) ? filter_var($input['breakfast_included'], FILTER_VALIDATE_BOOLEAN) : false;
$refundable_flights = isset($input['refundable_flights']) ? filter_var($input['refundable_flights'], FILTER_VALIDATE_BOOLEAN) : false;

// Validate destination
if (!$destination_id) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a destination']);
    exit();
}

// Validate departure city
if (empty($departure_city)) {
    echo json_encode(['status' => 'error', 'message' => 'Please select your departure city']);
    exit();
}

// Validate dates
if (empty($start_date) || empty($end_date)) {
    echo json_encode(['status' => 'error', 'message' => 'Please select start and end dates']);
    exit();
}

// Calculate trip duration and get month range
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$interval = $start->diff($end);
$nights = $interval->days;

if ($nights <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'End date must be after start date']);
    exit();
}

// Get all months covered by the trip (for seasonal pricing)
$months = [];
$current = clone $start;
while ($current <= $end) {
    $months[] = intval($current->format('n')); // Get month number (1-12)
    $current->modify('+1 month');
}
$months = array_unique($months); // Remove duplicates
sort($months);

// Function to get seasonal price multiplier with improved logic
function getSeasonalMultiplier($conn, $item_type, $item_id, $months) {
    if (empty($months)) return 1.00;
    
    // Check if seasonal_pricing table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'seasonal_pricing'");
    if ($table_check->num_rows == 0) {
        error_log("Seasonal pricing table does not exist");
        return 1.00;
    }
    
    $max_multiplier = 1.00;
    $multipliers_found = [];
    
    // Check each month for applicable pricing
    foreach ($months as $month) {
        // Handle cross-year seasons (e.g., Nov to Feb)
        $query = "SELECT price_multiplier, season_name FROM seasonal_pricing 
                  WHERE item_type = ? 
                  AND item_id = ? 
                  AND is_active = 1
                  AND (
                      (start_month <= end_month AND ? BETWEEN start_month AND end_month)
                      OR 
                      (start_month > end_month AND (? >= start_month OR ? <= end_month))
                  )";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siiii", $item_type, $item_id, $month, $month, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $multipliers_found[] = $row['price_multiplier'];
            if ($row['price_multiplier'] > $max_multiplier) {
                $max_multiplier = $row['price_multiplier'];
                error_log("Found multiplier for $item_type ID $item_id month $month: " . $row['price_multiplier'] . " (" . $row['season_name'] . ")");
            }
        }
        $stmt->close();
    }
    
    if (!empty($multipliers_found)) {
        error_log("Multipliers found for $item_type ID $item_id: " . implode(', ', $multipliers_found) . " - Using max: $max_multiplier");
    } else {
        error_log("No seasonal pricing found for $item_type ID $item_id, using default 1.00");
    }
    
    return $max_multiplier;
}

// Get destination info
$dest_query = "SELECT id, name, location, budget FROM destinations WHERE id = ?";
$dest_stmt = $conn->prepare($dest_query);
$dest_stmt->bind_param("i", $destination_id);
$dest_stmt->execute();
$dest_result = $dest_stmt->get_result();
$destination = $dest_result->fetch_assoc();
$dest_stmt->close();

if (!$destination) {
    echo json_encode(['status' => 'error', 'message' => 'Destination not found']);
    exit();
}

// Log the months being checked
error_log("Checking seasonal pricing for months: " . implode(', ', $months));

// Build hotels query with dynamic pricing
$hotel_query = "SELECT * FROM hotels WHERE destination_id = ?";
$hotel_params = [$destination_id];
$hotel_types = "i";

// Apply budget filter (using base price for filtering)
$hotel_query .= " AND price_per_night <= ?";
$hotel_params[] = $hotel_budget;
$hotel_types .= "d";

if ($free_cancellation) {
    $hotel_query .= " AND free_cancellation = 1";
}
if ($breakfast_included) {
    $hotel_query .= " AND breakfast_included = 1";
}

$hotel_query .= " ORDER BY price_per_night ASC LIMIT 12";

$hotel_stmt = $conn->prepare($hotel_query);
$hotel_stmt->bind_param($hotel_types, ...$hotel_params);
$hotel_stmt->execute();
$hotel_result = $hotel_stmt->get_result();

$hotels = [];
while ($row = $hotel_result->fetch_assoc()) {
    // Get seasonal multiplier for this hotel
    $multiplier = getSeasonalMultiplier($conn, 'hotel', $row['id'], $months);
    
    // Apply multiplier to price
    $row['base_price_per_night'] = $row['price_per_night'];
    $row['price_per_night'] = round($row['price_per_night'] * $multiplier, 2);
    $row['seasonal_multiplier'] = $multiplier;
    $row['season_active'] = ($multiplier != 1.00);
    
    // Decode amenities JSON if needed
    if (isset($row['amenities']) && is_string($row['amenities'])) {
        $row['amenities'] = json_decode($row['amenities'], true);
    }
    
    $hotels[] = $row;
}
$hotel_stmt->close();

// If no hotels found within budget, get some sample hotels (relax budget constraint)
if (empty($hotels)) {
    error_log("No hotels found within budget, fetching sample hotels");
    $sample_query = "SELECT * FROM hotels WHERE destination_id = ? ORDER BY price_per_night ASC LIMIT 6";
    $sample_stmt = $conn->prepare($sample_query);
    $sample_stmt->bind_param("i", $destination_id);
    $sample_stmt->execute();
    $sample_result = $sample_stmt->get_result();
    
    while ($row = $sample_result->fetch_assoc()) {
        // Still apply seasonal pricing to sample hotels
        $multiplier = getSeasonalMultiplier($conn, 'hotel', $row['id'], $months);
        $row['base_price_per_night'] = $row['price_per_night'];
        $row['price_per_night'] = round($row['price_per_night'] * $multiplier, 2);
        $row['seasonal_multiplier'] = $multiplier;
        $row['season_active'] = ($multiplier != 1.00);
        
        if (isset($row['amenities']) && is_string($row['amenities'])) {
            $row['amenities'] = json_decode($row['amenities'], true);
        }
        
        $hotels[] = $row;
    }
    $sample_stmt->close();
}

// Build flights query with dynamic pricing
$flight_query = "SELECT * FROM flights WHERE destination_id = ?";
$flight_params = [$destination_id];
$flight_types = "i";

// Add departure city filter if specified
if (!empty($departure_city)) {
    $flight_query .= " AND departure_city = ?";
    $flight_params[] = $departure_city;
    $flight_types .= "s";
}

// Apply budget filter (using base price for filtering)
$flight_query .= " AND price_per_person <= ?";
$flight_params[] = $flight_budget;
$flight_types .= "d";

if ($refundable_flights) {
    $flight_query .= " AND refundable = 1";
}

$flight_query .= " ORDER BY price_per_person ASC LIMIT 12";

$flight_stmt = $conn->prepare($flight_query);
$flight_stmt->bind_param($flight_types, ...$flight_params);
$flight_stmt->execute();
$flight_result = $flight_stmt->get_result();

$flights = [];
while ($row = $flight_result->fetch_assoc()) {
    // Get seasonal multiplier for this flight
    $multiplier = getSeasonalMultiplier($conn, 'flight', $row['id'], $months);
    
    // Apply multiplier to price
    $row['base_price_per_person'] = $row['price_per_person'];
    $row['price_per_person'] = round($row['price_per_person'] * $multiplier, 2);
    $row['seasonal_multiplier'] = $multiplier;
    $row['season_active'] = ($multiplier != 1.00);
    
    $flights[] = $row;
}
$flight_stmt->close();

// If no flights found for this departure city, get all flights for this destination as fallback
if (empty($flights)) {
    error_log("No flights found for departure city $departure_city, fetching all flights");
    $fallback_query = "SELECT * FROM flights WHERE destination_id = ? AND price_per_person <= ? ORDER BY price_per_person ASC LIMIT 8";
    $fallback_stmt = $conn->prepare($fallback_query);
    $fallback_stmt->bind_param("id", $destination_id, $flight_budget);
    $fallback_stmt->execute();
    $fallback_result = $fallback_stmt->get_result();
    
    while ($row = $fallback_result->fetch_assoc()) {
        // Apply seasonal pricing to fallback flights too
        $multiplier = getSeasonalMultiplier($conn, 'flight', $row['id'], $months);
        $row['base_price_per_person'] = $row['price_per_person'];
        $row['price_per_person'] = round($row['price_per_person'] * $multiplier, 2);
        $row['seasonal_multiplier'] = $multiplier;
        $row['season_active'] = ($multiplier != 1.00);
        
        $flights[] = $row;
    }
    $fallback_stmt->close();
}

// Get user details for activity tracking
$user_query = "SELECT name FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

$user_name = $user['name'] ?? 'User';

// Save this trip planning activity to user history
$activity_details = json_encode([
    'destination_id' => $destination_id,
    'destination_name' => $destination['name'],
    'departure_city' => $departure_city,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'travelers' => $travelers,
    'nights' => $nights,
    'hotel_budget' => $hotel_budget,
    'flight_budget' => $flight_budget,
    'months_covered' => $months,
    'user_name' => $user_name
]);

// Check if user_history table exists, create if not
$check_table = $conn->query("SHOW TABLES LIKE 'user_history'");
if ($check_table->num_rows == 0) {
    $create_table = "CREATE TABLE user_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        activity_details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_type (activity_type)
    )";
    $conn->query($create_table);
}

$history_query = "INSERT INTO user_history (user_id, activity_type, activity_details, created_at) VALUES (?, 'trip_plan', ?, NOW())";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("is", $user_id, $activity_details);
$history_stmt->execute();
$history_stmt->close();

// Log the results for debugging
error_log("Found " . count($hotels) . " hotels and " . count($flights) . " flights for destination ID $destination_id");

echo json_encode([
    'status' => 'success',
    'destination' => $destination,
    'hotels' => $hotels,
    'flights' => $flights,
    'months_covered' => $months,
    'summary' => [
        'nights' => $nights,
        'travelers' => $travelers
    ]
]);

$conn->close();
?>