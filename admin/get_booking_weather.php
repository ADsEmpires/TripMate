<?php
// get_booking_weather.php
header('Content-Type: application/json');
require_once '../database/dbconfig.php';

// Function to get weather data
function getWeatherData($location) {
    $api_key = 'YOUR_OPENWEATHERMAP_API_KEY_HERE'; // REPLACE WITH YOUR API KEY
    
    if (empty($api_key) || $api_key === 'YOUR_OPENWEATHERMAP_API_KEY_HERE') {
        return [
            'success' => false,
            'message' => 'Weather API key not configured'
        ];
    }
    
    $url = "http://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . "&appid=" . $api_key . "&units=metric";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['main']) && isset($data['weather'][0])) {
            return [
                'success' => true,
                'temperature' => round($data['main']['temp']),
                'weather' => $data['weather'][0]['main'],
                'description' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity'],
                'wind_speed' => $data['wind']['speed'] ?? 0,
                'feasibility' => checkVisitFeasibility($data)
            ];
        }
    }
    
    return ['success' => false];
}

function checkVisitFeasibility($weather_data) {
    $main_weather = strtolower($weather_data['weather'][0]['main']);
    $temp = $weather_data['main']['temp'];
    
    $bad_conditions = ['thunderstorm', 'hurricane', 'tornado', 'blizzard', 'sandstorm', 'squall'];
    $extreme_temp = ($temp < -10 || $temp > 40);
    
    if (in_array($main_weather, $bad_conditions) || $extreme_temp) {
        return 'not_feasible';
    } elseif ($main_weather == 'rain' || $main_weather == 'snow' || $main_weather == 'drizzle') {
        return 'challenging';
    } else {
        return 'feasible';
    }
}

function getWeatherMessage($feasibility, $weather, $temperature) {
    switch ($feasibility) {
        case 'not_feasible':
            return "Travel is not advisable due to $weather conditions (${temperature}°C). Consider rescheduling your trip.";
        case 'challenging':
            return "Travel may be challenging due to $weather (${temperature}°C). Pack appropriate clothing and gear.";
        case 'feasible':
            return "Good weather conditions for travel with $weather (${temperature}°C). Enjoy your trip!";
        default:
            return "Please check local weather updates before traveling.";
    }
}

// Get upcoming bookings (next 7 days)
$query = "SELECT b.*, d.name as destination_name, d.location as destination_location 
          FROM bookings b 
          LEFT JOIN destinations d ON b.destination_id = d.id 
          WHERE b.booking_status IN ('confirmed', 'pending')
          AND (
            (b.start_date IS NOT NULL AND b.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY))
            OR 
            (b.booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY))
          )
          ORDER BY b.start_date ASC, b.booking_date ASC
          LIMIT 10";

$result = $conn->query($query);
$alerts = [];

if ($result && $result->num_rows > 0) {
    while ($booking = $result->fetch_assoc()) {
        // Get weather for destination
        $location = $booking['destination_location'] ?? $booking['destination_name'];
        
        if ($location) {
            $weather = getWeatherData($location);
            
            if ($weather['success']) {
                $alerts[] = [
                    'booking_id' => $booking['id'],
                    'destination' => $booking['destination_name'],
                    'location' => $location,
                    'booking_date' => $booking['start_date'] ?: $booking['booking_date'],
                    'status' => $booking['booking_status'],
                    'weather' => $weather['weather'],
                    'temperature' => $weather['temperature'],
                    'feasibility' => $weather['feasibility'],
                    'message' => getWeatherMessage($weather['feasibility'], $weather['weather'], $weather['temperature'])
                ];
            }
        }
    }
}

echo json_encode(['alerts' => $alerts, 'count' => count($alerts)]);
?>