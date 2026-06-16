<?php
ob_start();
session_start();

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

require_once '../database/dbconfig.php';

$dest_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($dest_id === 0) {
    $_SESSION['message'] = "Invalid submission ID.";
    header("Location: manage_contributor_submissions.php");
    exit();
}

// Fetch Destination Details
$dest_stmt = $conn->prepare("SELECT d.*, c.name AS contributor_name, c.email AS contributor_email FROM destinations d LEFT JOIN contributors c ON d.contributor_id = c.id WHERE d.id = ?");
$dest_stmt->bind_param("i", $dest_id);
$dest_stmt->execute();
$dest_result = $dest_stmt->get_result();

if ($dest_result->num_rows === 0) {
    $_SESSION['message'] = "Submission not found.";
    header("Location: manage_contributor_submissions.php");
    exit();
}
$destination = $dest_result->fetch_assoc();
$dest_stmt->close();

// Fetch Flights
$flights_stmt = $conn->prepare("SELECT * FROM flights WHERE destination_id = ?");
$flights_stmt->bind_param("i", $dest_id);
$flights_stmt->execute();
$flights_result = $flights_stmt->get_result();
$flights = [];
while($row = $flights_result->fetch_assoc()) {
    $flights[] = $row;
}
$flights_stmt->close();

// Fetch Hotels
$hotels_stmt = $conn->prepare("SELECT * FROM hotels WHERE destination_id = ?");
$hotels_stmt->bind_param("i", $dest_id);
$hotels_stmt->execute();
$hotels_result = $hotels_stmt->get_result();
$hotels = [];
while($row = $hotels_result->fetch_assoc()) {
    $hotels[] = $row;
}
$hotels_stmt->close();

?>
<?php include 'admin_header.php'; ?>

<style>
    :root {
        --text-primary: #2d3436;
        --text-muted: #7f8c8d;
        --card-bg: #ffffff;
        --accent: #3498db;
        --border: rgba(0,0,0,0.08);
    }
    body.dark-mode {
        --text-primary: #ecf0f1;
        --text-muted: #bdc3c7;
        --card-bg: #2d3436;
        --border: #3e5a6b;
    }
    .main-content { padding: 40px; max-width: 1200px; margin: 100 auto; }
    .review-card { background: var(--card-bg); border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border); }
    .section-title { font-size: 1.4rem; color: var(--text-primary); margin-bottom: 15px; border-bottom: 2px solid var(--border); padding-bottom: 10px; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 15px; }
    .info-item label { display: block; font-size: 0.85rem; color: var(--text-muted); font-weight: bold; margin-bottom: 5px; text-transform: uppercase;}
    .info-item div { font-size: 1.1rem; color: var(--text-primary); }
    .btn-action { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; color: white; display: inline-flex; align-items: center; gap: 8px; transition: opacity 0.2s; }
    .btn-action:hover { opacity: 0.8; }
    .status-badge { padding: 6px 12px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; text-transform: uppercase; display: inline-block; }
    .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 15px; }
    .sub-card { background: var(--bg-base); border: 1px dashed var(--border); padding: 15px; border-radius: 10px; }
</style>

<div class="main-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <a href="manage_contributor_submissions.php" style="color: var(--accent); text-decoration: none; font-weight: bold;"><i class="fas fa-arrow-left"></i> Back to Submissions</a>
            <h1 style="font-size: 2.2rem; color: var(--text-primary); margin-top: 10px;">Package Review: <?= htmlspecialchars($destination['name'] ?? 'Unknown') ?></h1>
        </div>
        <div>
            <?php 
            $status = $destination['submission_status'] ?? 'pending';
            $bg = $status == 'approved' ? '#55efc4' : ($status == 'rejected' ? '#ff7675' : '#ffeaa7');
            $col = $status == 'approved' ? '#00b894' : ($status == 'rejected' ? '#d63031' : '#d35400');
            ?>
            <div class="status-badge" style="background: <?= $bg ?>; color: <?= $col ?>;">
                <?= $status ?>
            </div>
        </div>
    </div>

    <div class="review-card">
        <h2 class="section-title"><i class="fas fa-map-marked-alt" style="color: #3498db;"></i> 1. Destination Overview</h2>
        
        <div style="display: flex; gap: 25px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px; max-width: 400px;">
                <?php 
                $images = json_decode($destination['image_urls'] ?? '[]', true);
                if (!is_array($images) && !empty($destination['image_urls'])) {
                    $images = [$destination['image_urls']]; // Fallback just in case
                }
                
                if (!empty($images) && isset($images[0])): ?>
                    <img src="../<?= htmlspecialchars($images[0]) ?>" alt="Preview" style="width: 100%; height: 250px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border);">
                <?php else: ?>
                    <div style="width: 100%; height: 250px; background: #eee; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #999;">No Image Available</div>
                <?php endif; ?>
            </div>
            
            <div style="flex: 2; min-width: 300px;">
                <div class="info-grid">
                    <div class="info-item"><label>Contributor</label><div><?= htmlspecialchars($destination['contributor_name'] ?? 'Unknown') ?></div></div>
                    <div class="info-item"><label>Type</label><div><?= ucfirst($destination['type'] ?? 'Unknown') ?></div></div>
                    <div class="info-item"><label>Location</label><div><?= htmlspecialchars($destination['location'] ?? 'Unknown') ?></div></div>
                    <div class="info-item"><label>Budget (Per Day)</label><div>₹<?= number_format((float)($destination['budget'] ?? 0)) ?></div></div>
                    <div class="info-item"><label>Best Season</label><div><?= ucwords(str_replace(',', ', ', $destination['season'] ?? '')) ?></div></div>
                    <div class="info-item"><label>Recommended For</label><div><?= str_replace(['["','"]','","'], ['', '', ', '], $destination['people'] ?? '') ?></div></div>
                </div>
                <div class="info-item" style="margin-top: 15px;">
                    <label>Description</label>
                    <div style="font-size: 1rem; line-height: 1.6; padding: 10px; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <?= nl2br(htmlspecialchars($destination['description'] ?? 'No description provided.')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="review-card">
        <h2 class="section-title"><i class="fas fa-plane" style="color: #9b59b6;"></i> 2. Associated Flights (<?= count($flights) ?>)</h2>
        <?php if (empty($flights)): ?>
            <p style="color: var(--text-muted); font-style: italic;">No flights were added to this package.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($flights as $f): ?>
                    <div class="sub-card">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 10px;">
                            <strong style="color: var(--text-primary); font-size: 1.1rem;"><?= htmlspecialchars($f['airline'] ?? 'Unknown Airline') ?></strong>
                            <span style="color: #27ae60; font-weight: bold;">₹<?= number_format((float)($f['price'] ?? 0)) ?></span>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                            <strong>From:</strong> <?= htmlspecialchars($f['from_city'] ?? 'Unknown City') ?><br>
                            <strong>Type:</strong> <?= ucfirst($f['flight_type'] ?? 'Unknown') ?> | <strong>Class:</strong> <?= htmlspecialchars($f['flight_class'] ?? 'Unknown') ?><br>
                            <strong>Time:</strong> <?= htmlspecialchars($f['departure_time'] ?? '') ?> to <?= htmlspecialchars($f['arrival_time'] ?? '') ?><br>
                            <strong>Duration:</strong> <?= htmlspecialchars($f['duration'] ?? '0') ?> hrs (<?= htmlspecialchars($f['stops'] ?? '0') ?> stops)<br>
                            <div style="margin-top: 5px;">
                                <?= !empty($f['refundable']) ? '<span style="background: #d4efdf; color: #00b894; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">Refundable</span>' : '' ?>
                                <?= !empty($f['meal_included']) ? '<span style="background: #ffeaa7; color: #d35400; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">Meal</span>' : '' ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="review-card">
        <h2 class="section-title"><i class="fas fa-hotel" style="color: #f39c12;"></i> 3. Associated Hotels (<?= count($hotels) ?>)</h2>
        <?php if (empty($hotels)): ?>
            <p style="color: var(--text-muted); font-style: italic;">No hotels were added to this package.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($hotels as $h): ?>
                    <div class="sub-card">
                        <div style="display: flex; gap: 15px;">
                            <div style="flex-shrink: 0;">
                                <?php if(!empty($h['image_url'])): ?>
                                    <img src="../<?= htmlspecialchars($h['image_url']) ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                                <?php else: ?>
                                    <div style="width: 100px; height: 100px; background: #eee; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.8rem;">No Img</div>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 5px; margin-bottom: 8px;">
                                    <strong style="color: var(--text-primary); font-size: 1.1rem;"><?= htmlspecialchars($h['hotel_name'] ?? 'Unknown Hotel') ?> <span style="color: #f1c40f; font-size: 0.9rem;"><i class="fas fa-star"></i> <?= htmlspecialchars($h['hotel_rating'] ?? '0') ?></span></strong>
                                    <span style="color: #27ae60; font-weight: bold;">₹<?= number_format((float)($h['price_per_night'] ?? 0)) ?>/nt</span>
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                                    <strong>Type:</strong> <?= ucfirst($h['hotel_type'] ?? 'Unknown') ?><br>
                                    <strong>Address:</strong> <?= htmlspecialchars($h['address'] ?? 'No address') ?><br>
                                    <strong>Contact:</strong> <?= htmlspecialchars($h['contact_number'] ?? 'No contact') ?><br>
                                    <strong>Times:</strong> In: <?= substr($h['check_in_time'] ?? '00:00', 0, 5) ?> | Out: <?= substr($h['check_out_time'] ?? '00:00', 0, 5) ?><br>
                                    <div style="margin-top: 5px;">
                                        <?= !empty($h['free_cancellation']) ? '<span style="background: #d4efdf; color: #00b894; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">Free Cancel</span>' : '' ?>
                                        <?= !empty($h['breakfast_included']) ? '<span style="background: #ffeaa7; color: #d35400; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">Breakfast</span>' : '' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px;">
        <?php if (($destination['submission_status'] ?? '') === 'pending'): ?>
            <a href="manage_contributor_submissions.php?action=reject&id=<?= $dest_id ?>" class="btn-action" style="background: #e74c3c;" onclick="return confirm('Are you sure you want to REJECT this package?');">
                <i class="fas fa-times"></i> Reject Package
            </a>
            <a href="manage_contributor_submissions.php?action=approve&id=<?= $dest_id ?>" class="btn-action" style="background: #27ae60;" onclick="return confirm('Are you sure you want to APPROVE this package?');">
                <i class="fas fa-check"></i> Approve Package
            </a>
        <?php else: ?>
            <span style="color: var(--text-muted); font-style: italic;">This package has already been <?= htmlspecialchars($destination['submission_status'] ?? 'processed') ?>.</span>
        <?php endif; ?>
    </div>

</div>

<?php 
include 'admin_footer.php'; 
ob_end_flush();
?>