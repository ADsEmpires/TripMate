<?php
session_start();
require_once '../database/dbconfig.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $user_id = $_SESSION['user_id'];
    $user_email = $_SESSION['user_email'] ?? '';
    $user_name = $_SESSION['user_name'] ?? 'Guest';

    // 1. Fetch the full booking details to generate the email
    $fetch_stmt = $conn->prepare("SELECT start_date, end_date, number_of_people, total_amount, booking_details FROM bookings WHERE booking_ref = ? AND user_id = ?");
    $fetch_stmt->bind_param("si", $booking_id, $user_id);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
        $details = json_decode($booking_data['booking_details'], true);
        
        $total_amount = $booking_data['total_amount'];
        $start_date = date('M d, Y', strtotime($booking_data['start_date']));
        $end_date = date('M d, Y', strtotime($booking_data['end_date']));
        $travelers = $booking_data['number_of_people'];
        
        $hotel_name = $details['hotel_name'] ?? 'N/A';
        $airline = $details['airline'] ?? 'N/A';
        $destination = $details['destination_name'] ?? 'Your Destination';

        // 2. Update the booking payment status to 'paid'
        $update_stmt = $conn->prepare("UPDATE bookings SET payment_status = 'paid' WHERE booking_ref = ? AND user_id = ?");
        $update_stmt->bind_param("si", $booking_id, $user_id);
        
        if ($update_stmt->execute()) {
            
            // 3. Send Success Email via SMTP with FULL Package Details
            if (!empty($user_email)) {
                // Adjust the path to your SMTP email_functions.php based on your structure
                require_once '../admin/smtp/email_functions.php'; 
                
                $subject = "Payment Successful - Your Trip to {$destination} is Confirmed! [{$booking_id}]";
                
                // Build a beautiful email body with all package details
                $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eef4ff; border-radius: 12px; padding: 20px;'>
                        <h2 style='color: #10b981; text-align: center;'>Payment Successful!</h2>
                        <p>Hi <strong>{$user_name}</strong>,</p>
                        <p>We have received your payment of <strong>₹" . number_format($total_amount, 2) . "</strong>. Your trip is now fully confirmed!</p>
                        
                        <div style='background: #f9fafc; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                            <h3 style='color: #16034f; margin-top: 0; border-bottom: 2px solid #ff6600; padding-bottom: 5px;'>Package Details</h3>
                            <ul style='list-style: none; padding-left: 0; line-height: 1.6;'>
                                <li><strong>Booking Reference:</strong> {$booking_id}</li>
                                <li><strong>Destination:</strong> {$destination}</li>
                                <li><strong>Travelers:</strong> {$travelers} Person(s)</li>
                                <li><strong>Check-in:</strong> {$start_date}</li>
                                <li><strong>Check-out:</strong> {$end_date}</li>
                            </ul>
                            
                            <h3 style='color: #16034f; margin-top: 15px; border-bottom: 2px solid #ff6600; padding-bottom: 5px;'>Included Services</h3>
                            <ul style='list-style: none; padding-left: 0; line-height: 1.6;'>
                                <li>🏨 <strong>Hotel:</strong> {$hotel_name}</li>
                                <li>✈️ <strong>Flight:</strong> {$airline}</li>
                            </ul>
                        </div>
                        
                        <p>You can view your complete itinerary by logging into your TripMate dashboard.</p>
                        <br>
                        <p style='color: #6b7280;'>Happy Travels,<br><strong>The TripMate Team</strong></p>
                    </div>
                ";
                
                // Call your SMTP function to send the email
                // send_email($user_email, $user_name, $subject, $body);
            }

            // 4. Redirect to User Dashboard with a success flag
            header("Location: ../user/user_dashboard.php?payment_success=1&booking=" . urlencode($booking_id));
            exit();
        }
        $update_stmt->close();
    }
    $fetch_stmt->close();
} else {
    // Invalid request, send back to home
    header("Location: ../main/index.html");
    exit();
}
?>