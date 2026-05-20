<?php
require_once __DIR__ . '/../user/session_init.php';
require_once '../database/dbconfig.php';

// Check if user is logged in - If not, redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;

if (!$hotel_id) {
    header('Location: booking_page.php');
    exit();
}

// Get hotel details
$stmt = $conn->prepare("SELECT h.*, d.name as destination_name, d.location
                       FROM hotels h
                       JOIN destinations d ON h.destination_id = d.id
                       WHERE h.id = ?");
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hotel) {
    header('Location: booking_page.php');
    exit();
}

// Get form data from POST or session defaults
$destination = isset($_POST['destination']) ? intval($_POST['destination']) : (isset($_SESSION['booking_destination']) ? intval($_SESSION['booking_destination']) : 0);
$checkin = isset($_POST['checkin']) ? $_POST['checkin'] : (isset($_SESSION['booking_checkin']) ? $_SESSION['booking_checkin'] : '');
$checkout = isset($_POST['checkout']) ? $_POST['checkout'] : (isset($_SESSION['booking_checkout']) ? $_SESSION['booking_checkout'] : '');
$rooms = isset($_POST['rooms']) ? intval($_POST['rooms']) : (isset($_SESSION['booking_rooms']) ? intval($_SESSION['booking_rooms']) : 1);
$guests = isset($_POST['guests']) ? intval($_POST['guests']) : (isset($_SESSION['booking_guests']) ? intval($_SESSION['booking_guests']) : 2);
$hotel_type = isset($_POST['hotel_type']) ? $_POST['hotel_type'] : (isset($_SESSION['booking_hotel_type']) ? $_SESSION['booking_hotel_type'] : 'all');

// Calculate nights
$nights = 0;
if ($checkin && $checkout) {
    $nights = ceil((strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24));
}

// Store in session for next step
$_SESSION['selected_hotel'] = [
    'id' => $hotel_id,
    'destination' => $destination,
    'checkin' => $checkin,
    'checkout' => $checkout,
    'rooms' => $rooms,
    'guests' => $guests,
    'hotel_type' => $hotel_type,
    'nights' => $nights,
    'hotel_name' => $hotel['hotel_name'],
    'hotel_rating' => $hotel['hotel_rating'],
    'price_per_night' => $hotel['price_per_night'],
    'description' => $hotel['description'],
    'amenities' => $hotel['amenities'],
    'check_in_time' => $hotel['check_in_time'],
    'check_out_time' => $hotel['check_out_time'],
    'free_cancellation' => $hotel['free_cancellation'],
    'breakfast_included' => $hotel['breakfast_included'],
    'destination_name' => $hotel['destination_name']
];

// Redirect to package selection or confirmation
header('Location: booking_page.php?tab=packages&hotel_selected=1');
exit();
