<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.html'); exit(); }
$user_id = $_SESSION['user_id'];

// Get booking details
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$booking_id) { header('Location: my_trips.php'); exit(); }

$stmt = $conn->prepare("
    SELECT b.*, d.name as destination_name, d.location, d.image_urls, d.latitude, d.longitude, d.description
    FROM bookings b 
    LEFT JOIN destinations d ON b.destination_id = d.id 
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) { header('Location: my_trips.php'); exit(); }

$details = json_decode($booking['booking_details'] ?? '{}', true) ?: [];
$images = json_decode($booking['image_urls'] ?? '[]', true) ?: [];
$img = $images[0] ?? '';
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking #<?php echo $booking_id; ?> - TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#4f46e5; --secondary:#06b6d4; --bg:#f1f5f9; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); }
        .navbar { position:fixed; top:0; left:0; right:0; height:64px; background:linear-gradient(135deg,#16034f,#2a0a8a);
            display:flex; align-items:center; justify-content:space-between; padding:0 24px; z-index:1000; color:#fff; }
        .navbar .logo { display:flex; align-items:center; gap:10px; font-weight:800; font-size:1.3rem; text-decoration:none; color:#fff; }
        .navbar .logo i { color:#ff6600; }
        .nav-links a { color:rgba(255,255,255,0.85); text-decoration:none; font-weight:600; font-size:0.9rem; margin-left:16px; }
        .main { max-width:900px; margin:0 auto; padding:88px 20px 40px; }
        .back-link { color:var(--primary); text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:6px; margin-bottom:20px; }
        .booking-header { background:var(--card); border-radius:20px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06); margin-bottom:24px; }
        .booking-hero { height:250px; background-size:cover; background-position:center; position:relative; }
        .booking-hero::after { content:''; position:absolute; bottom:0; left:0; right:0; height:100px;
            background:linear-gradient(transparent,rgba(0,0,0,0.6)); }
        .booking-hero-text { position:absolute; bottom:20px; left:24px; color:#fff; z-index:1; }
        .booking-hero-text h1 { font-size:1.8rem; font-weight:800; }
        .booking-hero-text .loc { opacity:0.9; font-size:0.95rem; }
        .booking-body { padding:24px; }
        .status-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
        .badge { padding:6px 16px; border-radius:20px; font-size:0.85rem; font-weight:700; }
        .badge-confirmed { background:rgba(16,185,129,0.1); color:#10b981; }
        .badge-pending { background:rgba(245,158,11,0.1); color:#f59e0b; }
        .badge-cancelled { background:rgba(239,68,68,0.1); color:#ef4444; }
        .price-big { font-size:1.5rem; font-weight:800; color:var(--primary); }
        .detail-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-top:20px; }
        .detail-item { background:var(--bg); border-radius:12px; padding:16px; }
        .detail-item .label { font-size:0.8rem; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
        .detail-item .value { font-weight:700; font-size:1rem; }
        .section { background:var(--card); border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.06); margin-bottom:20px; }
        .section h2 { font-size:1.1rem; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .section h2 i { color:var(--secondary); }
        .info-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border); }
        .info-row:last-child { border:none; }
        .info-label { color:var(--muted); font-size:0.9rem; }
        .info-value { font-weight:600; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../main/index.html" class="logo"><i class="fa-solid fa-paper-plane"></i> Trip<span style="color:var(--secondary)">Mate</span></a>
        <div class="nav-links">
            <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="my_trips.php"><i class="fas fa-suitcase"></i> My Trips</a>
        </div>
    </nav>
    <div class="main">
        <a href="my_trips.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Trips</a>
        <div class="booking-header">
            <?php if ($img): ?>
            <div class="booking-hero" style="background-image:url('<?php echo htmlspecialchars($img); ?>')">
                <div class="booking-hero-text">
                    <h1><?php echo htmlspecialchars($booking['booking_title']); ?></h1>
                    <div class="loc"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['destination_name'] ?? $booking['location'] ?? ''); ?></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="booking-body">
                <div class="status-bar">
                    <span class="badge badge-<?php echo $booking['booking_status']; ?>"><?php echo ucfirst($booking['booking_status']); ?></span>
                    <span class="price-big">₹<?php echo number_format($booking['total_amount'], 2); ?></span>
                </div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="label">Booking ID</div><div class="value">#<?php echo $booking_id; ?></div></div>
                    <div class="detail-item"><div class="label">Check-in</div><div class="value"><?php echo $booking['start_date'] ? date('M d, Y', strtotime($booking['start_date'])) : 'N/A'; ?></div></div>
                    <div class="detail-item"><div class="label">Check-out</div><div class="value"><?php echo $booking['end_date'] ? date('M d, Y', strtotime($booking['end_date'])) : 'N/A'; ?></div></div>
                    <div class="detail-item"><div class="label">Travelers</div><div class="value"><?php echo $booking['number_of_people']; ?> person(s)</div></div>
                    <div class="detail-item"><div class="label">Booking Type</div><div class="value"><?php echo ucfirst($booking['booking_type'] ?? 'package'); ?></div></div>
                    <div class="detail-item"><div class="label">Payment</div><div class="value"><?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?></div></div>
                </div>
            </div>
        </div>

        <?php if (!empty($details)): ?>
        <div class="section">
            <h2><i class="fas fa-info-circle"></i> Booking Details</h2>
            <?php if (!empty($details['hotel_name'])): ?>
            <div class="info-row"><span class="info-label">Hotel</span><span class="info-value"><?php echo htmlspecialchars($details['hotel_name']); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($details['airline'])): ?>
            <div class="info-row"><span class="info-label">Airline</span><span class="info-value"><?php echo htmlspecialchars($details['airline']); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($details['departure_from'])): ?>
            <div class="info-row"><span class="info-label">Departure From</span><span class="info-value"><?php echo htmlspecialchars($details['departure_from']); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($details['hotel_total'])): ?>
            <div class="info-row"><span class="info-label">Hotel Total</span><span class="info-value">₹<?php echo number_format($details['hotel_total'], 2); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($details['flight_total'])): ?>
            <div class="info-row"><span class="info-label">Flight Total</span><span class="info-value">₹<?php echo number_format($details['flight_total'], 2); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($details['booking_ref'])): ?>
            <div class="info-row"><span class="info-label">Reference</span><span class="info-value"><?php echo htmlspecialchars($details['booking_ref']); ?></span></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2><i class="fas fa-clock"></i> Timeline</h2>
            <div class="info-row"><span class="info-label">Booked on</span><span class="info-value"><?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?></span></div>
        </div>
    </div>
</body>
</html>
