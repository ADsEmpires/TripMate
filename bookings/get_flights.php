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
$to = $input['to'] ?? '';
$depart = $input['depart'] ?? '';
$passengers = $input['passengers'] ?? 1;
$class = $input['class'] ?? 'economy';
$trip_type = $input['trip_type'] ?? 'roundtrip';

// Map UI class to database flight_type
$flight_type_map = [
    'economy' => 'low',
    'premium' => 'medium',
    'business' => 'high',
    'first' => 'high'
];

$db_class = $flight_type_map[$class] ?? 'low';

// Get current month for seasonal pricing
$current_month = date('n', strtotime($depart));

// Query flights
$query = "SELECT f.*, d.name as destination_name, d.location 
          FROM flights f 
          JOIN destinations d ON f.destination_id = d.id 
          WHERE f.departure_city = ? AND d.id = ? AND f.flight_type = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sis", $from, $to, $db_class);
$stmt->execute();
$result = $stmt->get_result();

$flights = [];
while ($row = $result->fetch_assoc()) {
    // Apply seasonal pricing multiplier if available
    $multiplier = 1.0;
    $season_query = "SELECT price_multiplier FROM seasonal_pricing 
                     WHERE item_type = 'flight' AND item_id = ? 
                     AND start_month <= ? AND end_month >= ? AND is_active = 1";
    $season_stmt = $conn->prepare($season_query);
    $season_stmt->bind_param("iii", $row['id'], $current_month, $current_month);
    $season_stmt->execute();
    $season_result = $season_stmt->get_result();
    
    if ($season_result->num_rows > 0) {
        $season = $season_result->fetch_assoc();
        $multiplier = $season['price_multiplier'];
    }
    
    $row['price_per_person'] = round($row['price_per_person'] * $multiplier, 2);
    $flights[] = $row;
    
    $season_stmt->close();
}

$stmt->close();

// If no flights found, return mock data for demonstration
if (empty($flights)) {
    $flights = generateMockFlights($from, $to, $db_class, $current_month);
}

echo json_encode($flights);

function generateMockFlights($from, $to, $class, $month) {
    $airlines = [
        'low' => ['IndiGo', 'SpiceJet', 'GoAir'],
        'medium' => ['Vistara', 'Air India'],
        'high' => ['Emirates', 'Singapore Airlines', 'Qatar Airways']
    ];
    
    $base_prices = [
        'low' => 5000,
        'medium' => 10000,
        'high' => 20000
    ];
    
    $flights = [];
    $selected_airlines = $airlines[$class] ?? $airlines['low'];
    
    foreach ($selected_airlines as $index => $airline) {
        $price = $base_prices[$class] + ($index * 1000);
        
        // Apply seasonal multiplier (simulated)
        if ($month >= 11 || $month <= 2) {
            $price *= 1.3; // Winter peak
        } elseif ($month >= 3 && $month <= 6) {
            $price *= 0.8; // Summer discount
        }
        
        $flights[] = [
            'id' => $index + 1,
            'airline' => $airline,
            'departure_city' => $from,
            'destination_id' => $to,
            'price_per_person' => round($price, 2),
            'duration_hours' => rand(2, 6),
            'stops' => rand(0, 1),
            'departure_time' => rand(6, 22) . ':00',
            'arrival_time' => rand(8, 23) . ':30',
            'flight_class' => ucfirst($class),
            'baggage_allowance' => $class == 'low' ? '15kg' : ($class == 'medium' ? '25kg' : '40kg'),
            'refundable' => $class != 'low',
            'meal_included' => $class != 'low'
        ];
    }
    
    return $flights;
}
?>