<?php
session_start();
require_once '../database/dbconfig.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

$from = $input['from'] ?? '';
$destination = $input['destination'] ?? '';
$checkin = $input['checkin'] ?? '';
$checkout = $input['checkout'] ?? '';
$travelers = $input['travelers'] ?? 2;
$budget = $input['budget'] ?? 'all';
$flight_class = $input['flight_class'] ?? 'all';

// Calculate nights
$nights = 0;
if ($checkin && $checkout) {
    $nights = ceil((strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24));
}

// Get current month for seasonal pricing
$current_month = date('n', strtotime($checkin));

// Get destination details
$dest_query = "SELECT * FROM destinations WHERE id = ?";
$dest_stmt = $conn->prepare($dest_query);
$dest_stmt->bind_param("i", $destination);
$dest_stmt->execute();
$destination_info = $dest_stmt->get_result()->fetch_assoc();
$dest_stmt->close();

// Build hotel query
$hotel_query = "SELECT * FROM hotels WHERE destination_id = ?";
$hotel_params = [$destination];
$hotel_types = "i";

if ($budget != 'all') {
    $hotel_query .= " AND hotel_type = ?";
    $hotel_params[] = $budget;
    $hotel_types .= "s";
}

$hotel_stmt = $conn->prepare($hotel_query);
$hotel_stmt->bind_param($hotel_types, ...$hotel_params);
$hotel_stmt->execute();
$hotels = $hotel_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$hotel_stmt->close();

// Build flight query
$flight_query = "SELECT * FROM flights WHERE destination_id = ?";
$flight_params = [$destination];
$flight_types = "i";

if (!empty($from)) {
    $flight_query .= " AND departure_city = ?";
    $flight_params[] = $from;
    $flight_types .= "s";
}

if ($budget != 'all') {
    $flight_query .= " AND flight_type = ?";
    $flight_params[] = $budget;
    $flight_types .= "s";
}

if ($flight_class != 'all') {
    $class_map = ['economy' => 'low', 'business' => 'high'];
    $db_class = $class_map[$flight_class] ?? 'low';
    $flight_query .= " AND flight_type = ?";
    $flight_params[] = $db_class;
    $flight_types .= "s";
}

$flight_stmt = $conn->prepare($flight_query);
$flight_stmt->bind_param($flight_types, ...$flight_params);
$flight_stmt->execute();
$flights = $flight_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$flight_stmt->close();

// Create package combinations
$packages = [];

foreach ($hotels as $hotel) {
    foreach ($flights as $flight) {
        // Apply seasonal pricing
        $hotel_multiplier = getSeasonalMultiplier($conn, 'hotel', $hotel['id'], $current_month);
        $flight_multiplier = getSeasonalMultiplier($conn, 'flight', $flight['id'], $current_month);
        
        $hotel_price = $hotel['price_per_night'] * $hotel_multiplier * $nights * $travelers;
        $flight_price = $flight['price_per_person'] * $flight_multiplier * $travelers;
        $total_price = $hotel_price + $flight_price;
        
        // Calculate savings (compared to booking separately with 20% markup)
        $savings = round($total_price * 0.15, 2);
        
        $packages[] = [
            'hotel' => $hotel,
            'flight' => $flight,
            'destination_name' => $destination_info['name'],
            'destination_location' => $destination_info['location'],
            'duration' => $nights,
            'total_price' => round($total_price, 2),
            'savings' => $savings,
            'budget_type' => $hotel['hotel_type'],
            'hotel_price' => round($hotel_price, 2),
            'flight_price' => round($flight_price, 2)
        ];
    }
}

// Sort by price
usort($packages, function($a, $b) {
    return $a['total_price'] - $b['total_price'];
});

// If no packages found, generate mock data
if (empty($packages) && $destination_info) {
    $packages = generateMockPackages($destination_info, $from, $budget, $nights, $travelers, $current_month);
}

echo json_encode($packages);

function getSeasonalMultiplier($conn, $type, $item_id, $month) {
    $query = "SELECT price_multiplier FROM seasonal_pricing 
              WHERE item_type = ? AND item_id = ? 
              AND start_month <= ? AND end_month >= ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siii", $type, $item_id, $month, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['price_multiplier'];
    }
    
    return 1.0;
}

function generateMockPackages($destination, $from, $budget, $nights, $travelers, $month) {
    $packages = [];
    $budget_types = $budget == 'all' ? ['low', 'medium', 'high'] : [$budget];
    
    $hotel_names = [
        'low' => ['Budget Inn', 'Economy Stay'],
        'medium' => ['Comfort Inn', 'Business Hotel'],
        'high' => ['Luxury Palace', 'Grand Resort']
    ];
    
    $airlines = [
        'low' => ['IndiGo', 'SpiceJet'],
        'medium' => ['Vistara', 'Air India'],
        'high' => ['Emirates', 'Singapore Airlines']
    ];
    
    $base_hotel_prices = ['low' => 2000, 'medium' => 5000, 'high' => 12000];
    $base_flight_prices = ['low' => 5000, 'medium' => 10000, 'high' => 20000];
    
    foreach ($budget_types as $type) {
        foreach ($hotel_names[$type] as $h_index => $hotel_name) {
            foreach ($airlines[$type] as $f_index => $airline) {
                // Apply seasonal multipliers
                $hotel_multiplier = 1.0;
                $flight_multiplier = 1.0;
                
                if ($month >= 11 || $month <= 2) {
                    $hotel_multiplier = 1.4;
                    $flight_multiplier = 1.3;
                } elseif ($month >= 3 && $month <= 6) {
                    $hotel_multiplier = 0.8;
                    $flight_multiplier = 0.85;
                }
                
                $hotel_price = $base_hotel_prices[$type] * $hotel_multiplier * $nights * $travelers;
                $flight_price = $base_flight_prices[$type] * $flight_multiplier * $travelers;
                $total = $hotel_price + $flight_price;
                
                $packages[] = [
                    'hotel' => [
                        'id' => count($packages) + 100,
                        'hotel_name' => $hotel_name,
                        'hotel_type' => $type,
                        'price_per_night' => $base_hotel_prices[$type],
                        'hotel_rating' => $type == 'low' ? 3.5 : ($type == 'medium' ? 4.2 : 4.8)
                    ],
                    'flight' => [
                        'id' => count($packages) + 200,
                        'airline' => $airline,
                        'flight_type' => $type,
                        'price_per_person' => $base_flight_prices[$type],
                        'departure_city' => $from ?: 'Mumbai',
                        'flight_class' => $type == 'high' ? 'Business' : 'Economy'
                    ],
                    'destination_name' => $destination['name'],
                    'duration' => $nights,
                    'total_price' => round($total, 2),
                    'savings' => round($total * 0.15, 2),
                    'budget_type' => $type
                ];
            }
        }
    }
    
    return $packages;
}
?>