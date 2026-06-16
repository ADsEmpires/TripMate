<?php
// bookings/book_hotel.php — Standalone hotel booking page
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$hotel_id = intval($_GET['hotel_id'] ?? 0);
$checkin = $_GET['checkin'] ?? date('Y-m-d', strtotime('+1 day'));
$checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime('+3 days'));
$rooms = intval($_GET['rooms'] ?? 1);
$guests = intval($_GET['guests'] ?? 2);
$destination_id = intval($_GET['destination_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

$hotel = null;
if ($hotel_id) {
    $stmt = $conn->prepare("SELECT h.*, h.name AS hotel_name, h.stars AS hotel_rating, d.name as dest_name, d.location
        FROM hotels h LEFT JOIN destinations d ON h.destination_id = d.id WHERE h.id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $hotel = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$hotel) { header('Location: booking_page.php'); exit(); }

$nights = max(1, ceil((strtotime($checkout) - strtotime($checkin)) / 86400));
$total = round($hotel['price_per_night'] * $nights * $rooms, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Hotel - TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#4f46e5; --secondary:#06b6d4; --bg:#f1f5f9; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); }
        .navbar { position:fixed; top:0; left:0; right:0; height:64px; background:linear-gradient(135deg,#16034f,#2a0a8a);
            display:flex; align-items:center; padding:0 24px; z-index:1000; }
        .navbar .logo { display:flex; align-items:center; gap:10px; font-weight:800; font-size:1.3rem; text-decoration:none; color:#fff; }
        .navbar .logo i { color:#ff6600; }
        .main { max-width:800px; margin:0 auto; padding:88px 20px 40px; }
        .back-link { color:var(--primary); text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:6px; margin-bottom:20px; }
        .booking-card { background:var(--card); border-radius:20px; box-shadow:0 4px 20px rgba(0,0,0,0.06); overflow:hidden; }
        .booking-header { background:linear-gradient(135deg,#f59e0b,#d97706); padding:24px; color:#fff; }
        .booking-header h1 { font-size:1.5rem; font-weight:800; }
        .booking-header .hotel-name { font-size:1.1rem; margin-top:8px; opacity:0.95; }
        .stars { color:#fbbf24; font-size:1rem; margin-top:4px; }
        .booking-body { padding:24px; }
        .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
        .detail-box { background:var(--bg); border-radius:12px; padding:14px; }
        .detail-box .label { font-size:0.75rem; color:var(--muted); text-transform:uppercase; font-weight:600; margin-bottom:4px; }
        .detail-box .value { font-weight:700; }
        .price-section { border-top:2px solid var(--border); padding-top:20px; margin-top:20px; }
        .price-row { display:flex; justify-content:space-between; margin-bottom:8px; }
        .price-total { font-size:1.3rem; font-weight:800; color:var(--primary); }
        .btn-book { width:100%; padding:16px; background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff;
            border:none; border-radius:12px; font-weight:800; font-size:1.1rem; cursor:pointer; margin-top:20px; transition:all .2s; }
        .btn-book:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(245,158,11,0.3); }
    </style>
</head>
<body>
    <nav class="navbar"><a href="../main/index.html" class="logo"><i class="fa-solid fa-paper-plane"></i> Trip<span style="color:var(--secondary)">Mate</span></a></nav>
    <div class="main">
        <a href="javascript:history.back()" class="back-link"><i class="fas fa-arrow-left"></i> Back</a>
        <div class="booking-card">
            <div class="booking-header">
                <h1><i class="fas fa-hotel"></i> Hotel Booking</h1>
                <div class="hotel-name"><?php echo htmlspecialchars($hotel['hotel_name']); ?></div>
                <div class="stars"><?php for($i=0;$i<$hotel['hotel_rating'];$i++) echo '★'; ?></div>
            </div>
            <div class="booking-body">
                <div class="detail-grid">
                    <div class="detail-box"><div class="label">Check-in</div><div class="value"><?php echo date('M d, Y', strtotime($checkin)); ?></div></div>
                    <div class="detail-box"><div class="label">Check-out</div><div class="value"><?php echo date('M d, Y', strtotime($checkout)); ?></div></div>
                    <div class="detail-box"><div class="label">Nights</div><div class="value"><?php echo $nights; ?> night(s)</div></div>
                    <div class="detail-box"><div class="label">Rooms</div><div class="value"><?php echo $rooms; ?> room(s)</div></div>
                    <div class="detail-box"><div class="label">Location</div><div class="value"><?php echo htmlspecialchars($hotel['dest_name'] ?? $hotel['location'] ?? ''); ?></div></div>
                    <div class="detail-box"><div class="label">Room Type</div><div class="value"><?php echo ucfirst($hotel['hotel_type']); ?></div></div>
                </div>
                <div class="price-section">
                    <div class="price-row"><span>Price per night</span><span>₹<?php echo number_format($hotel['price_per_night'], 2); ?></span></div>
                    <div class="price-row"><span>Nights × Rooms</span><span><?php echo $nights; ?> × <?php echo $rooms; ?></span></div>
                    <div class="price-row"><span class="price-total">Total</span><span class="price-total">₹<?php echo number_format($total, 2); ?></span></div>
                </div>
                <button class="btn-book" onclick="confirmBooking()"><i class="fas fa-check-circle"></i> Confirm Booking</button>
            </div>
        </div>
    </div>
    <script>
    function confirmBooking() {
        if (!confirm('Confirm hotel booking for ₹<?php echo number_format($total, 2); ?>?')) return;
        fetch('../bookings/process_booking.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                type: 'hotel', hotel_id: <?php echo $hotel_id; ?>,
                destination_id: <?php echo $hotel['destination_id']; ?>,
                checkin: '<?php echo $checkin; ?>', checkout: '<?php echo $checkout; ?>',
                rooms: <?php echo $rooms; ?>, guests: <?php echo $guests; ?>, total: <?php echo $total; ?>
            })
        }).then(r => r.json()).then(d => {
            if (d.status === 'success') {
                alert('Hotel booked successfully!');
                window.location.href = '../user/my_trips.php';
            } else alert(d.message || 'Booking failed');
        }).catch(() => alert('Error processing booking'));
    }
    </script>
</body>
</html>
