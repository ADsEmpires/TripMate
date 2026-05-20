<?php
require_once __DIR__ . '/../user/session_init.php';
require_once '../database/dbconfig.php';

// Check if user is logged in - If not, redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;

if (!$flight_id) {
    header('Location: booking_page.php');
    exit();
}

// Get flight details
$stmt = $conn->prepare("SELECT f.*, d.name as destination_name, d.location
                       FROM flights f
                       JOIN destinations d ON f.destination_id = d.id
                       WHERE f.id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$flight) {
    header('Location: booking_page.php');
    exit();
}

// Get form data from POST or session defaults
$from = isset($_POST['from']) ? $_POST['from'] : (isset($_SESSION['booking_from']) ? $_SESSION['booking_from'] : '');
$to = isset($_POST['to']) ? $_POST['to'] : (isset($_SESSION['booking_to']) ? $_SESSION['booking_to'] : '');
$depart = isset($_POST['depart']) ? $_POST['depart'] : (isset($_SESSION['booking_depart']) ? $_SESSION['booking_depart'] : '');
$return = isset($_POST['return']) ? $_POST['return'] : (isset($_SESSION['booking_return']) ? $_SESSION['booking_return'] : '');
$passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : (isset($_SESSION['booking_passengers']) ? intval($_SESSION['booking_passengers']) : 1);
$class = isset($_POST['class']) ? $_POST['class'] : (isset($_SESSION['booking_class']) ? $_SESSION['booking_class'] : 'economy');
$trip_type = isset($_POST['trip_type']) ? $_POST['trip_type'] : (isset($_SESSION['booking_trip_type']) ? $_SESSION['booking_trip_type'] : 'roundtrip');

// Calculate nights for return trip
$nights = 0;
if ($trip_type === 'roundtrip' && $depart && $return) {
    $nights = ceil((strtotime($return) - strtotime($depart)) / (60 * 60 * 24));
}

// Store in session for next step
$_SESSION['selected_flight'] = [
    'id' => $flight_id,
    'from' => $from,
    'to' => $to,
    'depart' => $depart,
    'return' => $return,
    'passengers' => $passengers,
    'class' => $class,
    'trip_type' => $trip_type,
    'nights' => $nights,
    'airline' => $flight['airline'],
    'flight_class' => $flight['flight_class'],
    'departure_city' => $flight['departure_city'],
    'destination_name' => $flight['destination_name'],
    'price_per_person' => $flight['price_per_person'],
    'duration_hours' => $flight['duration_hours'],
    'stops' => $flight['stops'],
    'departure_time' => $flight['departure_time'],
    'arrival_time' => $flight['arrival_time'],
    'baggage_allowance' => $flight['baggage_allowance'],
    'refundable' => $flight['refundable'],
    'meal_included' => $flight['meal_included']
];

// Redirect to hotel selection
header('Location: booking_page.php?tab=hotels&flight_selected=1');
exit();
