<?php
ob_start();
session_start();

// Force Error Reporting (Crucial for Mac local environments to avoid white screens)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

require_once '../database/dbconfig.php';

// ==================== SCHEMA MAINTENANCE ====================
$columns_check = $conn->query("SHOW COLUMNS FROM `destinations` LIKE 'submitted_by_type'");
if ($columns_check && $columns_check->num_rows === 0) {
    $sql = "ALTER TABLE `destinations` 
            ADD COLUMN `submitted_by_type` VARCHAR(20) DEFAULT 'admin',
            ADD COLUMN `submitted_by_id` INT(11) DEFAULT NULL,
            ADD COLUMN `submission_status` VARCHAR(20) DEFAULT 'pending',
            ADD COLUMN `contributor_id` INT(11) DEFAULT NULL,
            ADD INDEX (`contributor_id`),
            ADD INDEX (`submission_status`)";
    $conn->query($sql);
}

// ==================== HANDLE ACTIONS ====================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $dest_id = (int)$_GET['id'];
    $action  = $_GET['action'];

    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';

        $stmt = $conn->prepare("UPDATE destinations SET submission_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $dest_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Destination " . ucfirst($new_status) . " successfully!";
    }
    header("Location: manage_contributor_submissions.php");
    exit();
}

// ==================== FETCH DATA ====================
$status_filter = $_GET['status'] ?? 'all';
$valid_statuses = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($status_filter, $valid_statuses)) $status_filter = 'all';

$sql = "SELECT d.*, c.name AS contributor_name, c.email AS contributor_email 
        FROM destinations d
        LEFT JOIN contributors c ON d.contributor_id = c.id
        WHERE d.submitted_by_type = 'contributor'";

if ($status_filter !== 'all') {
    $sql .= " AND d.submission_status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= " ORDER BY d.id DESC";

$result = $conn->query($sql);

// Count stats
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$count_res = $conn->query("SELECT submission_status, COUNT(*) as cnt FROM destinations WHERE submitted_by_type = 'contributor' GROUP BY submission_status");
while ($r = $count_res->fetch_assoc()) {
    $counts[$r['submission_status']] = $r['cnt'];
}
?>

<?php include 'admin_header.php'; ?>

<div class="main-content">
    <div class="header-section" style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.2rem; color: var(--text-primary);"><i class="fas fa-user-check" style="color: var(--accent);"></i> Contributor Submissions</h1>
        <p style="color: var(--text-muted);">Manage and update the status of content submitted by contributors.</p>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert" style="background: var(--card-bg); padding: 15px; border: 1px solid var(--accent); border-radius: 8px; margin-bottom: 20px; color: var(--text-primary); font-weight: 600;">
            <i class="fas fa-info-circle" style="color: var(--accent);"></i> <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="stats-container" style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
        <div class="stat-box" style="flex: 1; min-width: 150px; padding: 20px; border-radius: 12px; background: var(--card-bg); text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid var(--border);">
            <h3 style="font-size: 24px; margin: 0; color: #f39c12;"><?= $counts['pending'] ?></h3>
            <p style="margin: 5px 0 0; color: var(--text-muted);">Pending</p>
        </div>
        <div class="stat-box" style="flex: 1; min-width: 150px; padding: 20px; border-radius: 12px; background: var(--card-bg); text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid var(--border);">
            <h3 style="font-size: 24px; margin: 0; color: #2ecc71;"><?= $counts['approved'] ?></h3>
            <p style="margin: 5px 0 0; color: var(--text-muted);">Approved</p>
        </div>
        <div class="stat-box" style="flex: 1; min-width: 150px; padding: 20px; border-radius: 12px; background: var(--card-bg); text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid var(--border);">
            <h3 style="font-size: 24px; margin: 0; color: #e74c3c;"><?= $counts['rejected'] ?></h3>
            <p style="margin: 5px 0 0; color: var(--text-muted);">Rejected</p>
        </div>
    </div>

    <div class="filter-bar" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <?php foreach ($valid_statuses as $s): ?>
            <a href="?status=<?= $s ?>" class="tab <?= $status_filter == $s ? 'active' : '' ?>" style="text-decoration: none; padding: 8px 16px; border-radius: 20px; font-weight: 500; color: <?= $status_filter == $s ? '#fff' : 'var(--text-primary)' ?>; background: <?= $status_filter == $s ? 'var(--accent)' : 'transparent' ?>;">
                <?= ucfirst($s) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="submission-card" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="list-item" style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 15px;">
                    <div class="item-info" style="display: flex; flex-direction: column; gap: 8px;">
                        <strong style="font-size: 1.1rem; color: var(--text-primary);"><?= htmlspecialchars($row['name'] ?? 'Untitled Destination') ?></strong>
                        
                        <?php 
                        $badge_bg = $row['submission_status'] == 'approved' ? 'rgba(46, 204, 113, 0.15)' : ($row['submission_status'] == 'rejected' ? 'rgba(231, 76, 60, 0.15)' : 'rgba(241, 196, 15, 0.15)');
                        $badge_color = $row['submission_status'] == 'approved' ? '#2ecc71' : ($row['submission_status'] == 'rejected' ? '#e74c3c' : '#f39c12');
                        ?>
                        <span style="padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; width: fit-content; background: <?= $badge_bg ?>; color: <?= $badge_color ?>;">
                            <?= strtoupper($row['submission_status']) ?>
                        </span>
                        
                        <div class="meta" style="font-size: 0.85rem; color: var(--text-muted); display: flex; gap: 15px;">
                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($row['contributor_name'] ?? 'Guest') ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['location'] ?? 'Global') ?></span>
                        </div>
                    </div>
                    
                    <div class="item-actions" style="display: flex; gap: 8px;">
                        <?php if ($row['submission_status'] === 'pending'): ?>
                            <a href="?action=approve&id=<?= $row['id'] ?>" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; color: white; background: #27ae60; transition: opacity 0.2s;"><i class="fas fa-check"></i> Approve</a>
                            <a href="?action=reject&id=<?= $row['id'] ?>" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; color: white; background: #e74c3c; transition: opacity 0.2s;"><i class="fas fa-times"></i> Reject</a>
                        <?php endif; ?>
                        <a href="view_contributor_submission.php?id=<?= $row['id'] ?>" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; color: white; background: #3498db; transition: opacity 0.2s;"><i class="fas fa-eye"></i> View</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px; opacity: 0.5;"></i>
                <p>No submissions found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    :root {
        --text-primary: #2d3436;
        --text-muted: #7f8c8d;
        --card-bg: #ffffff;
        --accent: #4f46e5; /* Adjusted to match admin header primary */
        --border: rgba(0,0,0,0.08);
    }
    
    /* FIXED: Added body.dark-theme here so it matches 
      exactly what the admin_header.php dark mode button generates 
    */
    body.dark-mode, body.dark-theme {
        --text-primary: #f8fafc;
        --text-muted: #cbd5e1;
        --card-bg: #1e293b;
        --accent: #818cf8;
        --border: rgba(255,255,255,0.1);
    }
    
    .main-content { padding: 30px; max-width: 1200px; margin: 1 auto; }
    .item-actions a:hover { opacity: 0.8; }
</style>
 
<?php 
include 'admin_footer.php'; 
$conn->close();
ob_end_flush();
?>