<?php
// bookings/process_booking.php — Generic booking processor for flights/hotels
session_start();
require_once __DIR__ . '/../database/dbconfig.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$type = $data['type'] ?? 'unknown';
$destination_id = intval($data['destination_id'] ?? 0);
$total = floatval($data['total'] ?? 0);

// Handle different booking types to extract dates and counts
$travelers = intval($data['travelers'] ?? $data['guests'] ?? 1);
$rooms = intval($data['rooms'] ?? 1);
$checkin = $data['checkin'] ?? date('Y-m-d');
$checkout = $data['checkout'] ?? date('Y-m-d', strtotime('+1 day'));

// Extract flight/hotel IDs
$flight_id = intval($data['flight_id'] ?? 0);
$hotel_id = intval($data['hotel_id'] ?? 0);

// Build title with destination
$dest_name = 'Unknown';
if ($destination_id) {
    $d_stmt = $conn->prepare("SELECT name FROM destinations WHERE id = ?");
    $d_stmt->bind_param("i", $destination_id);
    $d_stmt->execute();
    $d_res = $d_stmt->get_result()->fetch_assoc();
    if ($d_res) $dest_name = $d_res['name'];
    $d_stmt->close();
}

$title = ucfirst($type) . ' booking - ' . $dest_name;

// Store full booking details including confirmation specifics
$details = [
    'booking_type' => $type,
    'destination_id' => $destination_id,
    'destination_name' => $dest_name,
    'flight_id' => $flight_id,
    'hotel_id' => $hotel_id,
    'travelers' => $travelers,
    'rooms' => $rooms,
    'guests' => intval($data['guests'] ?? $travelers),
    'checkin_date' => $checkin,
    'checkout_date' => $checkout,
    'total_cost' => $total,
    'booking_timestamp' => date('Y-m-d H:i:s'),
    'raw_data' => $data
];
$details_json = json_encode($details);

// Determine booking status based on type (flights confirmed immediately, hotels may need confirmation)
$booking_status = ($type === 'flight' || $type === 'package') ? 'confirmed' : 'pending';
$payment_status = 'pending';

// Insert booking with all necessary fields
$stmt = $conn->prepare("
    INSERT INTO bookings 
    (user_id, booking_title, destination_id, booking_type, 
     start_date, end_date, number_of_people, total_amount, booking_details, 
     booking_status, payment_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(['status'=>'error','message'=>'Database prepare error: '.$conn->error]);
    exit();
}

$stmt->bind_param(
    "isisssidsss", 
    $user_id, 
    $title, 
    $destination_id, 
    $type, 
    $checkin, 
    $checkout, 
    $travelers, 
    $total, 
    $details_json, 
    $booking_status, 
    $payment_status
);

if ($stmt->execute()) {
    $booking_id = $conn->insert_id;
    
    // Log booking confirmation details to booking_payments if immediate confirmation
    if ($booking_status === 'confirmed') {
        $payment_method = 'pending';
        $payment_stmt = $conn->prepare("
            INSERT INTO booking_payments (booking_id, amount, payment_date, payment_method) 
            VALUES (?, ?, NOW(), ?)
        ");
        if ($payment_stmt) {
            $payment_stmt->bind_param("ids", $booking_id, $total, $payment_method);
            $payment_stmt->execute();
            $payment_stmt->close();
        }
    }
    
    echo json_encode([
        'status'=>'success',
        'message'=>'Booking confirmed successfully!',
        'booking_id'=>$booking_id,
        'booking_type'=>$type,
        'total_amount'=>$total
    ]);
} else {
    echo json_encode(['status'=>'error','message'=>'Database error: '.$stmt->error]);
}

$stmt->close();
$conn->close();
?>
