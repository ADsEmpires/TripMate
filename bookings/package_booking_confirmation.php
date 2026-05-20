<?php
require_once __DIR__ . '/../user/session_init.php';
require_once '../database/dbconfig.php';

// Block Google-only users — they must create a full account first
if (isset($_SESSION['auth_provider']) && $_SESSION['auth_provider'] === 'google') {
    // Check if they have a password set (incomplete upgrade)
    if (isset($_SESSION['user_id'])) {
        $check_stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND auth_provider = 'google'");
        $check_stmt->bind_param("i", $_SESSION['user_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $user_check = $check_result->fetch_assoc();
        $check_stmt->close();

        if (!$user_check || empty($user_check['password'])) {
            $google_email = isset($_SESSION['user_email']) ? urlencode($_SESSION['user_email']) : '';
            $google_name = isset($_SESSION['user_name']) ? urlencode($_SESSION['user_name']) : '';
            header("Location: ../auth/register.html?upgrade=1&email={$google_email}&name={$google_name}");
            exit;
        } else {
            $_SESSION['auth_provider'] = 'manual';
        }
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;
$travelers = isset($_GET['travelers']) ? intval($_GET['travelers']) : 2;
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$from = isset($_GET['from']) ? $_GET['from'] : '';

// Calculate nights
$nights = 0;
if ($checkin && $checkout) {
    $nights = ceil((strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24));
}

// Get hotel details
$hotel = null;
if ($hotel_id) {
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotel = $result->fetch_assoc();
    $stmt->close();
}

// Get flight details
$flight = null;
if ($flight_id) {
    $stmt = $conn->prepare("SELECT * FROM flights WHERE id = ?");
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $flight = $result->fetch_assoc();
    $stmt->close();
}

// Get destination details
$destination = null;
$destination_id = 0;
if ($hotel && isset($hotel['destination_id'])) {
    $destination_id = $hotel['destination_id'];
} elseif ($flight && isset($flight['destination_id'])) {
    $destination_id = $flight['destination_id'];
}

if ($destination_id) {
    $stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $destination = $result->fetch_assoc();
    $stmt->close();
}

// Apply seasonal pricing
$current_month = date('n', strtotime($checkin));
$hotel_multiplier = 1.0;
$flight_multiplier = 1.0;

if ($hotel && $hotel_id) {
    $stmt = $conn->prepare("SELECT price_multiplier FROM seasonal_pricing 
                            WHERE item_type = 'hotel' AND item_id = ? 
                            AND start_month <= ? AND end_month >= ? AND is_active = 1");
    $stmt->bind_param("iii", $hotel_id, $current_month, $current_month);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hotel_multiplier = $row['price_multiplier'];
    }
    $stmt->close();
}

if ($flight && $flight_id) {
    $stmt = $conn->prepare("SELECT price_multiplier FROM seasonal_pricing 
                            WHERE item_type = 'flight' AND item_id = ? 
                            AND start_month <= ? AND end_month >= ? AND is_active = 1");
    $stmt->bind_param("iii", $flight_id, $current_month, $current_month);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $flight_multiplier = $row['price_multiplier'];
    }
    $stmt->close();
}

// Calculate final prices
$hotel_total = $hotel ? round($hotel['price_per_night'] * $hotel_multiplier * $nights * $travelers, 2) : 0;
$flight_total = $flight ? round($flight['price_per_person'] * $flight_multiplier * $travelers, 2) : 0;
$grand_total = $hotel_total + $flight_total;

// Generate unique booking ID
$booking_id = 'TM' . strtoupper(substr(uniqid(), -8)) . date('Ymd');

// Save booking to database
if ($hotel || $flight) {
    $booking_query = "INSERT INTO bookings (user_id, booking_id, destination_id, hotel_id, flight_id, 
                      checkin_date, checkout_date, travelers, total_amount, booking_date, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'confirmed')";
    $booking_stmt = $conn->prepare($booking_query);
    $booking_stmt->bind_param(
        "isiiissid",
        $user_id,
        $booking_id,
        $destination_id,
        $hotel_id,
        $flight_id,
        $checkin,
        $checkout,
        $travelers,
        $grand_total
    );
    $booking_stmt->execute();
    $booking_stmt->close();
}

// Get user details
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_email = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Session Management Meta Tags -->
    <meta name="user-id" content="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
    <meta name="user-name" content="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>">

    <title>Booking Confirmation - TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Session Management Scripts -->
    <script src="../user/session-keepalive.js"></script>
    <script src="../user/session-sync.js"></script>
    <script src="../user/auto-logout.js"></script>

    <style>
        :root {
            --primary: #16034f;
            --secondary: #2a0a8a;
            --accent: #ff6600;
            --success: #10b981;
            --white: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f9fafc 0%, #f0f4ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .confirmation-card {
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(22, 3, 79, 0.2);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .success-header i {
            font-size: 4rem;
            color: var(--success);
            background: rgba(255, 255, 255, 0.2);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }

        .success-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .success-header p {
            opacity: 0.9;
        }

        .booking-details {
            padding: 2rem;
        }

        .detail-section {
            margin-bottom: 2rem;
            border-bottom: 1px solid #eef4ff;
            padding-bottom: 1.5rem;
        }

        .detail-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--accent);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            padding: 0.75rem;
            background: #f9fafc;
            border-radius: 12px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1rem;
        }

        .price-breakdown {
            background: linear-gradient(135deg, #fff7ed, #fffbeb);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1rem;
            border: 1px solid #fed7aa;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px dashed #fed7aa;
        }

        .price-row.total {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .price-row.total .price {
            color: var(--accent);
            font-size: 1.5rem;
        }

        .guest-info {
            background: #eef4ff;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .guest-info i {
            font-size: 2rem;
            color: var(--accent);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 0.875rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95rem;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: #cc5200;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .booking-id {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eef4ff;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .booking-id strong {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .image-preview {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            margin-right: 1rem;
        }

        .hotel-flight-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="user-logged-in" data-user-id="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
    <div class="confirmation-card">
        <div class="success-header">
            <i class="fas fa-check-circle"></i>
            <h1>Booking Confirmed!</h1>
            <p>Your trip has been successfully booked</p>
        </div>

        <div class="booking-details">
            <!-- Booking ID -->
            <div class="booking-id">
                <i class="fas fa-barcode"></i> Booking ID:
                <strong><?php echo $booking_id; ?></strong>
            </div>

            <!-- Package Summary -->
            <div class="detail-section">
                <div class="section-title">
                    <i class="fas fa-gem"></i>
                    Trip Summary
                </div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Destination</span>
                        <span class="detail-value"><?php echo htmlspecialchars($destination['name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Location</span>
                        <span class="detail-value"><?php echo htmlspecialchars($destination['location'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value"><?php echo $nights; ?> nights</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Travelers</span>
                        <span class="detail-value"><?php echo $travelers; ?> <?php echo $travelers > 1 ? 'persons' : 'person'; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Check-in</span>
                        <span class="detail-value"><?php echo date('M d, Y', strtotime($checkin)); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Check-out</span>
                        <span class="detail-value"><?php echo date('M d, Y', strtotime($checkout)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Flight Details -->
            <?php if ($flight): ?>
                <div class="detail-section">
                    <div class="section-title">
                        <i class="fas fa-plane"></i>
                        Flight Details
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Airline</span>
                            <span class="detail-value"><?php echo htmlspecialchars($flight['airline']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">From</span>
                            <span class="detail-value"><?php echo htmlspecialchars($flight['departure_city'] ?? $from); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Departure Time</span>
                            <span class="detail-value"><?php echo htmlspecialchars($flight['departure_time'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Class</span>
                            <span class="detail-value"><?php echo htmlspecialchars($flight['flight_class'] ?? 'Economy'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Duration</span>
                            <span class="detail-value"><?php echo htmlspecialchars($flight['duration_hours'] ?? 'N/A'); ?> hours</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Baggage</span>
                            <span class="detail-value"><?php echo htmlspecialchars($flight['baggage_allowance'] ?? '15kg'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Hotel Details -->
            <?php if ($hotel): ?>
                <div class="detail-section">
                    <div class="section-title">
                        <i class="fas fa-hotel"></i>
                        Hotel Details
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Hotel Name</span>
                            <span class="detail-value"><?php echo htmlspecialchars($hotel['hotel_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Rating</span>
                            <span class="detail-value"><?php echo $hotel['hotel_rating']; ?> ★</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Room Type</span>
                            <span class="detail-value"><?php echo ucfirst($hotel['hotel_type']); ?> Budget</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Check-in Time</span>
                            <span class="detail-value"><?php echo htmlspecialchars($hotel['check_in_time'] ?? '14:00'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Check-out Time</span>
                            <span class="detail-value"><?php echo htmlspecialchars($hotel['check_out_time'] ?? '11:00'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Amenities</span>
                            <span class="detail-value">
                                <?php
                                $amenities = json_decode($hotel['amenities'] ?? '[]', true);
                                echo is_array($amenities) ? implode(', ', array_slice($amenities, 0, 3)) : 'Standard Amenities';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Price Breakdown -->
            <div class="price-breakdown">
                <div class="section-title" style="margin-top: 0;">
                    <i class="fas fa-receipt"></i>
                    Price Breakdown
                </div>

                <?php if ($flight && $flight_total > 0): ?>
                    <div class="price-row">
                        <span>Flight (<?php echo $travelers; ?> traveler<?php echo $travelers > 1 ? 's' : ''; ?>)</span>
                        <span class="price">₹<?php echo number_format($flight_total, 2); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($hotel && $hotel_total > 0): ?>
                    <div class="price-row">
                        <span>Hotel (<?php echo $nights; ?> nights × <?php echo $travelers; ?> travelers)</span>
                        <span class="price">₹<?php echo number_format($hotel_total, 2); ?></span>
                    </div>
                <?php endif; ?>

                <div class="price-row total">
                    <span>Total Amount</span>
                    <span class="price">₹<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>

            <!-- Guest Information -->
            <div class="guest-info">
                <i class="fas fa-user-circle"></i>
                <div>
                    <strong>Booking for:</strong> <?php echo htmlspecialchars($user_name); ?><br>
                    <small>A confirmation email has been sent to <?php echo htmlspecialchars($user_email ?: 'your email'); ?></small>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="../user/user_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> View My Bookings
                </a>
                <a href="../main/index.html" class="btn btn-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="../user/session-sync.js"></script>
    <script>
        // Track this booking in user history
        <?php if (isset($_SESSION['user_id'])): ?>
            fetch('../user/track_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?php echo $_SESSION['user_id']; ?>,
                    hotel_id: <?php echo $hotel_id ?: 0; ?>,
                    flight_id: <?php echo $flight_id ?: 0; ?>,
                    booking_id: '<?php echo $booking_id; ?>',
                    checkin: '<?php echo $checkin; ?>',
                    checkout: '<?php echo $checkout; ?>',
                    travelers: <?php echo $travelers; ?>,
                    total: <?php echo $grand_total; ?>
                })
            }).catch(err => console.log('Tracking error:', err));
        <?php endif; ?>
    </script>
</body>

</html>