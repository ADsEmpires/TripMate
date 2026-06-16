<?php
session_start();
require_once '../database/dbconfig.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

$destination = $input['destination'] ?? '';
$checkin = $input['checkin'] ?? '';
$checkout = $input['checkout'] ?? '';
$rooms = $input['rooms'] ?? 1;
$guests = $input['guests'] ?? 2;
$hotel_type = $input['hotel_type'] ?? 'all';

// Get current month for seasonal pricing
$current_month = date('n', strtotime($checkin ?: 'now'));

// Calculate nights
$nights = 1;
if ($checkin && $checkout) {
    $nights = max(1, ceil((strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24)));
}

// Query hotels
$query = "SELECT h.*, h.name AS hotel_name, h.stars AS hotel_rating, 
          d.name as destination_name, d.location 
          FROM hotels h 
          JOIN destinations d ON h.destination_id = d.id 
          WHERE h.destination_id = ?";
$params = [$destination];
$types = "i";

if ($hotel_type !== 'all') {
    $query .= " AND h.hotel_type = ?";
    $params[] = $hotel_type;
    $types .= "s";
}

$query .= " ORDER BY h.price_per_night ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$hotels = [];
while ($row = $result->fetch_assoc()) {
    // Apply seasonal pricing multiplier if available
    $multiplier = 1.0;
    $season_query = "SELECT price_multiplier FROM seasonal_pricing 
                     WHERE item_type = 'hotel' AND item_id = ? 
                     AND ((start_month <= end_month AND start_month <= ? AND end_month >= ?)
                          OR (start_month > end_month AND (? >= start_month OR ? <= end_month)))
                     AND is_active = 1";
    $season_stmt = $conn->prepare($season_query);
    $season_stmt->bind_param("iiiii", $row['id'], $current_month, $current_month, $current_month, $current_month);
    $season_stmt->execute();
    $season_result = $season_stmt->get_result();
    
    if ($season_result->num_rows > 0) {
        $season = $season_result->fetch_assoc();
        $multiplier = $season['price_multiplier'];
    }
    $season_stmt->close();
    
    $row['price_per_night'] = round($row['price_per_night'] * $multiplier, 2);
    
    // Add default values for display fields that may not exist in DB
    $row['check_in_time'] = $row['check_in_time'] ?? '14:00';
    $row['check_out_time'] = $row['check_out_time'] ?? '11:00';
    $row['free_cancellation'] = $row['free_cancellation'] ?? ($row['hotel_type'] !== 'low');
    $row['breakfast_included'] = $row['breakfast_included'] ?? ($row['hotel_type'] !== 'low');
    
    $hotels[] = $row;
}

$stmt->close();

// If no hotels found, return mock data for demonstration
if (empty($hotels)) {
    $hotels = generateMockHotels($destination, $hotel_type, $current_month);
}

echo json_encode($hotels);

function generateMockHotels($destination_id, $type, $month) {
    $hotel_names = [
        'low' => ['Budget Inn Express', 'Economy Stay Lodge'],
        'medium' => ['Comfort Inn Suites', 'Business Class Hotel'],
        'high' => ['Grand Luxury Palace', 'Royal Resort & Spa']
    ];
    
    $base_prices = [
        'low' => 1500,
        'medium' => 4000,
        'high' => 10000
    ];
    
    $hotels = [];
    $types = $type === 'all' ? ['low', 'medium', 'high'] : [$type];
    
    foreach ($types as $t) {
        foreach ($hotel_names[$t] as $index => $name) {
            $price = $base_prices[$t] + ($index * 500);
            
            // Apply seasonal multiplier
            if ($month >= 11 || $month <= 2) {
                $price *= 1.4; // Winter peak
            } elseif ($month >= 3 && $month <= 6) {
                $price *= 0.8; // Summer discount
            }
            
            $hotels[] = [
                'id' => count($hotels) + 1,
                'hotel_name' => $name,
                'hotel_type' => $t,
                'hotel_rating' => $t == 'low' ? 3.0 : ($t == 'medium' ? 4.0 : 4.8),
                'price_per_night' => round($price, 2),
                'check_in_time' => '14:00',
                'check_out_time' => '11:00',
                'free_cancellation' => $t !== 'low',
                'breakfast_included' => $t !== 'low',
                'amenities' => json_encode(
                    $t == 'low' ? ['WiFi', 'AC'] : 
                    ($t == 'medium' ? ['WiFi', 'AC', 'Pool', 'Breakfast'] : 
                    ['WiFi', 'AC', 'Pool', 'Spa', 'Gym', 'Breakfast', 'Room Service'])
                )
            ];
        }
    }
    
    return $hotels;
}
?>
