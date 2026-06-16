<?php
session_start();
require_once __DIR__ . '/../database/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Get user's bookings
$bookings = [];
$stmt = $conn->prepare("
    SELECT b.*, d.name as destination_name, d.location, d.image_urls, d.latitude, d.longitude
    FROM bookings b 
    LEFT JOIN destinations d ON b.destination_id = d.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($row['image_urls']) && is_string($row['image_urls'])) {
        $row['image_urls'] = json_decode($row['image_urls'], true) ?: [];
    }
    if (isset($row['booking_details']) && is_string($row['booking_details'])) {
        $row['booking_details_parsed'] = json_decode($row['booking_details'], true) ?: [];
    }
    $bookings[] = $row;
}
$stmt->close();

// Get itineraries
$itineraries = [];
$itin_stmt = $conn->prepare("
    SELECT i.*, d.name as destination_name, d.location, d.image_urls
    FROM itineraries i 
    LEFT JOIN destinations d ON i.destination_id = d.id 
    WHERE i.user_id = ? 
    ORDER BY i.start_date DESC
");
$itin_stmt->bind_param("i", $user_id);
$itin_stmt->execute();
$itin_result = $itin_stmt->get_result();
while ($row = $itin_result->fetch_assoc()) {
    if (isset($row['image_urls']) && is_string($row['image_urls'])) {
        $row['image_urls'] = json_decode($row['image_urls'], true) ?: [];
    }
    $itineraries[] = $row;
}
$itin_stmt->close();
$conn->close();

$upcoming = array_filter($bookings, fn($b) => strtotime($b['start_date'] ?? $b['booking_date'] ?? '2000-01-01') >= time());
$past = array_filter($bookings, fn($b) => strtotime($b['start_date'] ?? $b['booking_date'] ?? '2000-01-01') < time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trips - TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5; --secondary: #06b6d4; --bg: #f1f5f9;
            --card: #fff; --text: #0f172a; --muted: #64748b; --border: #e2e8f0;
            --accent: #ff6600; --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .navbar { position:fixed; top:0; left:0; right:0; height:64px; background:linear-gradient(135deg,#16034f,#2a0a8a);
            display:flex; align-items:center; justify-content:space-between; padding:0 24px; z-index:1000; color:#fff; }
        .navbar .logo { display:flex; align-items:center; gap:10px; font-weight:800; font-size:1.3rem; text-decoration:none; color:#fff; }
        .navbar .logo i { color:var(--accent); }
        .nav-links { display:flex; gap:16px; align-items:center; }
        .nav-links a { color:rgba(255,255,255,0.85); text-decoration:none; font-weight:600; font-size:0.9rem; transition:color .2s; }
        .nav-links a:hover { color:#fff; }
        .main { max-width:1200px; margin:0 auto; padding:88px 20px 40px; }
        .page-header { margin-bottom:32px; }
        .page-header h1 { font-size:2rem; font-weight:800; margin-bottom:8px; }
        .page-header p { color:var(--muted); }
        .tabs { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
        .tab { padding:10px 20px; border-radius:10px; border:2px solid var(--border); background:var(--card); font-weight:600;
            cursor:pointer; transition:all .2s; font-size:0.9rem; }
        .tab.active { background:var(--primary); color:#fff; border-color:var(--primary); }
        .tab:hover:not(.active) { border-color:var(--primary); color:var(--primary); }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }
        .trip-card { background:var(--card); border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06);
            margin-bottom:20px; transition:transform .2s, box-shadow .2s; border:1px solid var(--border); }
        .trip-card:hover { transform:translateY(-3px); box-shadow:0 8px 30px rgba(0,0,0,0.1); }
        .trip-card-body { display:flex; gap:20px; padding:20px; }
        .trip-image { width:160px; height:120px; border-radius:12px; object-fit:cover; flex-shrink:0; background:#e2e8f0; }
        .trip-info { flex:1; min-width:0; }
        .trip-info h3 { font-size:1.2rem; font-weight:700; margin-bottom:6px; }
        .trip-info .location { color:var(--muted); font-size:0.85rem; margin-bottom:8px; }
        .trip-meta { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:10px; }
        .trip-meta span { font-size:0.8rem; color:var(--muted); display:flex; align-items:center; gap:4px; }
        .trip-meta span i { color:var(--secondary); }
        .badge { padding:4px 12px; border-radius:20px; font-size:0.75rem; font-weight:700; }
        .badge-confirmed { background:rgba(16,185,129,0.1); color:var(--success); }
        .badge-pending { background:rgba(245,158,11,0.1); color:var(--warning); }
        .badge-cancelled { background:rgba(239,68,68,0.1); color:var(--danger); }
        .trip-actions { display:flex; gap:8px; margin-top:10px; }
        .btn-sm { padding:6px 14px; border-radius:8px; border:none; font-weight:600; font-size:0.8rem; cursor:pointer; transition:all .2s; }
        .btn-view { background:var(--primary); color:#fff; }
        .btn-view:hover { background:#4338ca; }
        .btn-cancel { background:rgba(239,68,68,0.1); color:var(--danger); }
        .btn-cancel:hover { background:rgba(239,68,68,0.2); }
        .empty-state { text-align:center; padding:60px 20px; }
        .empty-state i { font-size:4rem; color:var(--border); margin-bottom:16px; }
        .empty-state h3 { font-size:1.3rem; margin-bottom:8px; color:var(--text); }
        .empty-state p { color:var(--muted); margin-bottom:20px; }
        .btn-primary { padding:12px 24px; background:linear-gradient(135deg,var(--primary),var(--secondary)); color:#fff;
            border:none; border-radius:10px; font-weight:700; cursor:pointer; transition:all .2s; text-decoration:none; display:inline-block; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(79,70,229,0.3); }
        .price { font-size:1.1rem; font-weight:800; color:var(--primary); }
        @media(max-width:640px) {
            .trip-card-body { flex-direction:column; }
            .trip-image { width:100%; height:180px; }
            .trip-meta { gap:10px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../main/index.html" class="logo"><i class="fa-solid fa-paper-plane"></i> Trip<span style="color:var(--secondary)">Mate</span></a>
        <div class="nav-links">
            <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="favourites.php"><i class="fas fa-heart"></i> Favorites</a>
            <a href="../search/search.html"><i class="fas fa-search"></i> Search</a>
        </div>
    </nav>

    <div class="main">
        <div class="page-header">
            <h1><i class="fas fa-suitcase" style="color:var(--secondary)"></i> My Trips</h1>
            <p>Manage your bookings and planned trips</p>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('all')">All Trips (<?php echo count($bookings); ?>)</button>
            <button class="tab" onclick="showTab('upcoming')">Upcoming (<?php echo count($upcoming); ?>)</button>
            <button class="tab" onclick="showTab('past')">Past (<?php echo count($past); ?>)</button>
            <button class="tab" onclick="showTab('itineraries')">Itineraries (<?php echo count($itineraries); ?>)</button>
        </div>

        <!-- All Trips -->
        <div class="tab-panel active" id="panel-all">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-plane"></i>
                    <h3>No trips yet</h3>
                    <p>Start planning your next adventure!</p>
                    <a href="../bookings/booking_page.php" class="btn-primary"><i class="fas fa-plus"></i> Book a Trip</a>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking):
                    $img = '';
                    if (!empty($booking['image_urls']) && is_array($booking['image_urls'])) {
                        $img = $booking['image_urls'][0] ?? '';
                    }
                    $status = $booking['booking_status'] ?? 'pending';
                    $statusClass = 'badge-' . $status;
                ?>
                <div class="trip-card">
                    <div class="trip-card-body">
                        <?php if ($img): ?>
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Destination" class="trip-image">
                        <?php else: ?>
                            <div class="trip-image" style="display:flex;align-items:center;justify-content:center;color:var(--muted);">
                                <i class="fas fa-image fa-2x"></i>
                            </div>
                        <?php endif; ?>
                        <div class="trip-info">
                            <h3><?php echo htmlspecialchars($booking['booking_title'] ?? 'Trip #'.$booking['id']); ?></h3>
                            <div class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['destination_name'] ?? $booking['location'] ?? 'N/A'); ?></div>
                            <div class="trip-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo $booking['start_date'] ? date('M d', strtotime($booking['start_date'])) : 'N/A'; ?> - <?php echo $booking['end_date'] ? date('M d, Y', strtotime($booking['end_date'])) : 'N/A'; ?></span>
                                <span><i class="fas fa-users"></i> <?php echo $booking['number_of_people']; ?> traveler(s)</span>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span>
                            </div>
                            <div class="price">₹<?php echo number_format($booking['total_amount'], 2); ?></div>
                            <div class="trip-actions">
                                <button class="btn-sm btn-view" onclick="viewBooking(<?php echo $booking['id']; ?>)"><i class="fas fa-eye"></i> View Details</button>
                                <?php if ($status === 'pending' || $status === 'confirmed'): ?>
                                <button class="btn-sm btn-cancel" onclick="cancelBooking(<?php echo $booking['id']; ?>)"><i class="fas fa-times"></i> Cancel</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Upcoming -->
        <div class="tab-panel" id="panel-upcoming">
            <?php if (empty($upcoming)): ?>
                <div class="empty-state"><i class="fas fa-calendar-plus"></i><h3>No upcoming trips</h3><p>Book your next adventure!</p>
                    <a href="../bookings/booking_page.php" class="btn-primary"><i class="fas fa-plus"></i> Book Now</a></div>
            <?php else: ?>
                <?php foreach ($upcoming as $b): ?>
                <div class="trip-card"><div class="trip-card-body"><div class="trip-info">
                    <h3><?php echo htmlspecialchars($b['booking_title'] ?? 'Trip #'.$b['id']); ?></h3>
                    <div class="trip-meta"><span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($b['start_date'] ?? $b['booking_date'])); ?></span></div>
                    <div class="price">₹<?php echo number_format($b['total_amount'], 2); ?></div>
                </div></div></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Past -->
        <div class="tab-panel" id="panel-past">
            <?php if (empty($past)): ?>
                <div class="empty-state"><i class="fas fa-history"></i><h3>No past trips</h3><p>Your completed trips will appear here.</p></div>
            <?php else: ?>
                <?php foreach ($past as $b): ?>
                <div class="trip-card"><div class="trip-card-body"><div class="trip-info">
                    <h3><?php echo htmlspecialchars($b['booking_title'] ?? 'Trip #'.$b['id']); ?></h3>
                    <div class="trip-meta"><span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($b['start_date'] ?? $b['booking_date'])); ?></span></div>
                    <span class="badge badge-confirmed">Completed</span>
                </div></div></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Itineraries -->
        <div class="tab-panel" id="panel-itineraries">
            <?php if (empty($itineraries)): ?>
                <div class="empty-state"><i class="fas fa-route"></i><h3>No itineraries</h3><p>Plan a detailed trip itinerary!</p>
                    <a href="trip_planner.php" class="btn-primary"><i class="fas fa-magic"></i> Plan a Trip</a></div>
            <?php else: ?>
                <?php foreach ($itineraries as $it): ?>
                <div class="trip-card"><div class="trip-card-body"><div class="trip-info">
                    <h3><?php echo htmlspecialchars($it['title']); ?></h3>
                    <div class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($it['destination_name'] ?? 'N/A'); ?></div>
                    <div class="trip-meta">
                        <span><i class="fas fa-calendar"></i> <?php echo date('M d', strtotime($it['start_date'])); ?> - <?php echo date('M d, Y', strtotime($it['end_date'])); ?></span>
                        <span><i class="fas fa-wallet"></i> ₹<?php echo number_format($it['budget']); ?></span>
                        <span><i class="fas fa-hiking"></i> <?php echo ucfirst($it['travel_style']); ?></span>
                    </div>
                </div></div></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
    }
    function viewBooking(id) {
        window.location.href = 'booking_details.php?id=' + id;
    }
    function cancelBooking(id) {
        if (confirm('Are you sure you want to cancel this booking?')) {
            fetch('../actions/cancel_booking.php', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({booking_id: id})
            }).then(r => r.json()).then(d => {
                if (d.status === 'success') location.reload();
                else alert(d.message || 'Failed to cancel');
            }).catch(() => alert('Error cancelling booking'));
        }
    }
    </script>
</body>
</html>
