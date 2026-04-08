<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$logfile = __DIR__ . '/user_removal_log.txt';
$logs = [];

if (file_exists($logfile)) {
    $lines = file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = array_reverse($lines); // Show newest first
}

include 'admin_header.php';
?>

<style>
.log-container {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.log-entry {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    line-height: 1.6;
}

.log-entry:hover {
    background: #f8f9fa;
}

.email-sent {
    color: #28a745;
    font-weight: bold;
}

.email-failed {
    color: #dc3545;
    font-weight: bold;
}

.log-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.log-date {
    color: #666;
    font-size: 0.85rem;
}

.log-info {
    margin: 5px 0;
}

.log-reason {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    border-left: 3px solid #007bff;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 1rem;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    gap: 6px;
    background: var(--primary);
    color: white;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.85rem;
    white-space: nowrap;
}

.btn-secondary {
    background: #6c757d;
}

.btn-secondary:hover {
    background: #5a6268;
}

.no-logs {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
    background: #f9f9f9;
    border-radius: 8px;
    margin-top: 1.5rem;
}

.log-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.8rem;
    font-weight: bold;
    margin-left: 10px;
}
</style>

<div class="main-content">
    <div class="log-container">
        <div class="log-header">
            <h1><i class="fas fa-history"></i> User Removal Log</h1>
            <a href="user_present_chack_on_admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to User Management
            </a>
        </div>
        
        <?php if (empty($logs)): ?>
            <div class="no-logs">
                <i class="fas fa-history" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                <h3>No Removal Logs Found</h3>
                <p>No users have been removed yet.</p>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 1rem; color: #6c757d; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> Showing <?= count($logs) ?> removal log entries (newest first)
            </div>
            <div style="max-height: 600px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px;">
                <?php foreach ($logs as $log): ?>
                    <?php
                    // Parse log entry
                    $parts = explode(' | ', $log);
                    $logData = [];
                    foreach ($parts as $part) {
                        if (strpos($part, ':') !== false) {
                            list($key, $value) = explode(':', $part, 2);
                            $logData[$key] = $value;
                        } else {
                            $logData['timestamp'] = $part;
                        }
                    }
                    ?>
                    <div class="log-entry">
                        <div class="log-date">
                            <i class="fas fa-calendar-alt"></i> <?= $logData['timestamp'] ?? 'Unknown date' ?>
                            <?php if (isset($logData['email_sent'])): ?>
                                <span class="log-status <?= $logData['email_sent'] === 'yes' ? 'email-sent' : 'email-failed' ?>">
                                    <?= $logData['email_sent'] === 'yes' ? '✓ Email Sent' : '✗ Email Failed' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="log-info">
                            <strong>Admin:</strong> <?= htmlspecialchars($logData['admin_name'] ?? 'Unknown') ?> (ID: <?= $logData['admin_id'] ?? '?' ?>)
                        </div>
                        
                        <div class="log-info">
                            <strong>User:</strong> <?= htmlspecialchars($logData['user_name'] ?? 'Unknown') ?> 
                            (ID: <?= $logData['user_id'] ?? '?' ?>, Email: <?= htmlspecialchars($logData['user_email'] ?? 'Unknown') ?>)
                        </div>
                        
                        <?php if (isset($logData['reason']) && !empty($logData['reason'])): ?>
                            <div class="log-reason">
                                <strong>Reason:</strong> <?= htmlspecialchars($logData['reason']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="log-info">
                            <strong>IP Address:</strong> <?= $logData['ip'] ?? 'Unknown' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 1rem; text-align: center; color: #6c757d; font-size: 0.85rem;">
                <i class="fas fa-database"></i> Log file: <?= basename($logfile) ?> 
                (Last modified: <?= file_exists($logfile) ? date('Y-m-d H:i:s', filemtime($logfile)) : 'N/A' ?>)
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'admin_footer.php'; ?>