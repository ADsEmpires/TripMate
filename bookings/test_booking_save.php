<?php
/**
 * Test Booking Save Script
 * Tests if bookings are properly saved to the database with correct confirmation data
 */
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

// Simulate a logged-in user
if (!isset($_SESSION['user_id'])) {
    $user_query = $conn->query("SELECT id FROM users LIMIT 1");
    if ($user_query && $user_query->num_rows > 0) {
        $user_row = $user_query->fetch_assoc();
        $_SESSION['user_id'] = $user_row['id'];
    } else {
        $_SESSION['user_id'] = 1; // Fallback
    }
}

echo "<h2>Booking Save Test</h2>";
echo "<hr>";

// Test Data 1: Flight Booking
echo "<h3>Test 1: Flight Booking</h3>";
$flight_booking_data = [
    'type' => 'flight',
    'flight_id' => 1,
    'destination_id' => 1,
    'travelers' => 2,
    'total' => 5000,
    'checkin' => date('Y-m-d', strtotime('+5 days')),
    'checkout' => date('Y-m-d', strtotime('+12 days'))
];

// Simulate the booking save
$user_id = $_SESSION['user_id'];
$type = $flight_booking_data['type'];
$destination_id = $flight_booking_data['destination_id'];
$total = $flight_booking_data['total'];
$travelers = $flight_booking_data['travelers'];
$checkin = $flight_booking_data['checkin'];
$checkout = $flight_booking_data['checkout'];
$flight_id = $flight_booking_data['flight_id'];
$hotel_id = 0;

// Get destination name
$dest_name = 'Test Destination';
$d_stmt = $conn->prepare("SELECT name FROM destinations WHERE id = ? LIMIT 1");
if ($d_stmt) {
    $d_stmt->bind_param("i", $destination_id);
    $d_stmt->execute();
    $d_res = $d_stmt->get_result()->fetch_assoc();
    if ($d_res) $dest_name = $d_res['name'];
    $d_stmt->close();
}

$title = ucfirst($type) . ' booking - ' . $dest_name;

// Build details
$details = [
    'booking_type' => $type,
    'destination_id' => $destination_id,
    'destination_name' => $dest_name,
    'flight_id' => $flight_id,
    'hotel_id' => $hotel_id,
    'travelers' => $travelers,
    'rooms' => 1,
    'guests' => $travelers,
    'checkin_date' => $checkin,
    'checkout_date' => $checkout,
    'total_cost' => $total,
    'booking_timestamp' => date('Y-m-d H:i:s')
];
$details_json = json_encode($details);

$booking_status = 'confirmed';
$payment_status = 'pending';

// Insert
$stmt = $conn->prepare("
    INSERT INTO bookings 
    (user_id, booking_title, destination_id, booking_type, 
     start_date, end_date, number_of_people, total_amount, booking_details, 
     booking_status, payment_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo "<p style='color: red;'><strong>Error preparing statement:</strong> " . $conn->error . "</p>";
} else {
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
        echo "<p style='color: green;'><strong>✓ Flight booking saved successfully!</strong></p>";
        echo "<p>Booking ID: $booking_id</p>";
        echo "<p>Total: ₹" . number_format($total, 2) . "</p>";
        echo "<p>Dates: $checkin to $checkout</p>";
        echo "<p>Travelers: $travelers</p>";
        
        // Verify it was saved
        $verify = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
        $verify->bind_param("i", $booking_id);
        $verify->execute();
        $result = $verify->get_result()->fetch_assoc();
        if ($result) {
            echo "<p style='color: blue;'><strong>✓ Verification: Booking found in database</strong></p>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'><strong>✗ Verification Failed: Booking not found</strong></p>";
        }
        $verify->close();
    } else {
        echo "<p style='color: red;'><strong>Error executing statement:</strong> " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<hr>";

// Test Data 2: Hotel Booking
echo "<h3>Test 2: Hotel Booking</h3>";

$type = 'hotel';
$hotel_id = 1;
$flight_id = 0;
$checkin = date('Y-m-d', strtotime('+10 days'));
$checkout = date('Y-m-d', strtotime('+15 days'));
$travelers = 2;
$rooms = 1;
$total = 8000;
$destination_id = 1;

$title = ucfirst($type) . ' booking - ' . $dest_name;

$details = [
    'booking_type' => $type,
    'destination_id' => $destination_id,
    'destination_name' => $dest_name,
    'flight_id' => $flight_id,
    'hotel_id' => $hotel_id,
    'travelers' => $travelers,
    'rooms' => $rooms,
    'guests' => $travelers,
    'checkin_date' => $checkin,
    'checkout_date' => $checkout,
    'total_cost' => $total,
    'booking_timestamp' => date('Y-m-d H:i:s')
];
$details_json = json_encode($details);

$booking_status = 'pending';
$payment_status = 'pending';

$stmt = $conn->prepare("
    INSERT INTO bookings 
    (user_id, booking_title, destination_id, booking_type, 
     start_date, end_date, number_of_people, total_amount, booking_details, 
     booking_status, payment_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo "<p style='color: red;'><strong>Error preparing statement:</strong> " . $conn->error . "</p>";
} else {
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
        echo "<p style='color: green;'><strong>✓ Hotel booking saved successfully!</strong></p>";
        echo "<p>Booking ID: $booking_id</p>";
        echo "<p>Total: ₹" . number_format($total, 2) . "</p>";
        echo "<p>Check-in: $checkin</p>";
        echo "<p>Check-out: $checkout</p>";
        echo "<p>Rooms: $rooms, Guests: $travelers</p>";
        
        // Verify it was saved
        $verify = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
        $verify->bind_param("i", $booking_id);
        $verify->execute();
        $result = $verify->get_result()->fetch_assoc();
        if ($result) {
            echo "<p style='color: blue;'><strong>✓ Verification: Booking found in database</strong></p>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'><strong>✗ Verification Failed: Booking not found</strong></p>";
        }
        $verify->close();
    } else {
        echo "<p style='color: red;'><strong>Error executing statement:</strong> " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<hr>";
echo "<h3>Summary: Check Admin Panel</h3>";
echo "<p>Now visit: <strong>Admin Panel → Bookings</strong> to verify the bookings appear and contain all confirmation data.</p>";
?>
