<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'tripmate';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get admin data
$admin_stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$admin_stmt->execute([$_SESSION['admin_id']]);
$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $message_id = $_POST['message_id'] ?? null;
    
    if ($message_id) {
        switch ($action) {
            case 'mark_read':
                $stmt = $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
                $stmt->execute([$message_id]);
                break;
                
            case 'mark_unread':
                $stmt = $pdo->prepare("UPDATE messages SET status = 'unread' WHERE id = ?");
                $stmt->execute([$message_id]);
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->execute([$message_id]);
                break;
                
            case 'reply':
                $reply_content = $_POST['reply_content'] ?? '';
                if (!empty($reply_content)) {
                    // Get message details
                    $msg_stmt = $pdo->prepare("SELECT user_id, subject FROM messages WHERE id = ?");
                    $msg_stmt->execute([$message_id]);
                    $message = $msg_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Insert reply as new message
                    $reply_stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message, message_type, status, created_at) VALUES (?, ?, ?, 'outgoing', 'sent', NOW())");
                    $subject = "Re: " . $message['subject'];
                    $reply_stmt->execute([$message['user_id'], $subject, $reply_content]);
                    
                    // Mark original as replied
                    $update_stmt = $pdo->prepare("UPDATE messages SET status = 'replied' WHERE id = ?");
                    $update_stmt->execute([$message_id]);
                }
                break;

            case 'send_message':
                $user_id = $_POST['user_id'] ?? null;
                $subject = $_POST['subject'] ?? '';
                $message_content = $_POST['message_content'] ?? '';
                
                if ($user_id && !empty($subject) && !empty($message_content)) {
                    $send_stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message, message_type, status, created_at) VALUES (?, ?, ?, 'outgoing', 'sent', NOW())");
                    $send_stmt->execute([$user_id, $subject, $message_content]);
                    $success_message = "Message sent successfully!";
                }
                break;
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_type = $_GET['type'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT m.*, u.name as user_name, u.email as user_email 
          FROM messages m 
          LEFT JOIN users u ON m.user_id = u.id 
          WHERE 1=1";

$params = [];

if ($filter_status !== 'all') {
    $query .= " AND m.status = ?";
    $params[] = $filter_status;
}

if ($filter_type !== 'all') {
    $query .= " AND m.message_type = ?";
    $params[] = $filter_type;
}

if (!empty($search_query)) {
    $query .= " AND (m.subject LIKE ? OR m.message LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$query .= " ORDER BY m.created_at DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count messages by status
$count_all = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$count_unread = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'")->fetchColumn();
$count_read = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'read'")->fetchColumn();
$count_replied = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'replied'")->fetchColumn();

// Get users for sending messages
$users = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get unread count for notifications
$unread_count = $count_unread;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin - Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #10b981;
            --accent: #f43f5e;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --warning: #f59e0b;
            --danger: #dc2626;
            --success: #16a34a;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .back-btn {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            z-index: 100;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .messages-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .sidebar {
            background: var(--light);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            height: fit-content;
        }

        .filters-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-size: 0.875rem;
        }

        .filter-select, .filter-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: white;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: white;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .stat-item.unread {
            border-left-color: var(--accent);
        }

        .stat-item.read {
            border-left-color: var(--success);
        }

        .stat-item.replied {
            border-left-color: var(--warning);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray);
            font-weight: 600;
        }

        .main-content {
            background: var(--light);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .messages-header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #15803d;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--dark);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .messages-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .message-item {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 1.5rem 2rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .message-item:hover {
            background: #f8fafc;
        }

        .message-item.unread {
            background: #f0f9ff;
            border-left: 4px solid var(--primary);
        }

        .message-item.read {
            border-left: 4px solid transparent;
        }

        .message-item.replied {
            border-left: 4px solid var(--success);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .message-sender {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .message-email {
            color: var(--gray);
            font-size: 0.875rem;
        }

        .message-subject {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .message-preview {
            color: var(--gray);
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .message-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-time {
            color: var(--gray);
            font-size: 0.875rem;
        }

        .message-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-unread {
            background: #fef2f2;
            color: var(--danger);
        }

        .badge-read {
            background: #f0fdf4;
            color: var(--success);
        }

        .badge-replied {
            background: #fffbeb;
            color: var(--warning);
        }

        .badge-outgoing {
            background: #eff6ff;
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--border);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .empty-description {
            margin-bottom: 2rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 2rem;
        }

        .message-detail {
            margin-bottom: 2rem;
        }

        .detail-row {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .detail-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .detail-value {
            color: var(--gray);
            line-height: 1.6;
        }

        .reply-form, .send-form {
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 1024px) {
            .messages-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .back-btn {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 1rem;
                display: inline-block;
            }
            
            .messages-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .message-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .message-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .message-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <a href="admin_dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Messages Center</h1>
            <p>Manage user inquiries and communications</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <div class="messages-container">
            <!-- Sidebar with Filters -->
            <div class="sidebar">
                <div class="filters-section">
                    <h3 class="section-title">
                        <i class="fas fa-filter"></i>
                        Filters
                    </h3>
                    
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search messages..." 
                               value="<?= htmlspecialchars($search_query) ?>" 
                               onkeyup="if(event.key === 'Enter') applyFilters()">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" id="statusFilter" onchange="applyFilters()">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="unread" <?= $filter_status === 'unread' ? 'selected' : '' ?>>Unread</option>
                            <option value="read" <?= $filter_status === 'read' ? 'selected' : '' ?>>Read</option>
                            <option value="replied" <?= $filter_status === 'replied' ? 'selected' : '' ?>>Replied</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Message Type</label>
                        <select class="filter-select" id="typeFilter" onchange="applyFilters()">
                            <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>All Types</option>
                            <option value="incoming" <?= $filter_type === 'incoming' ? 'selected' : '' ?>>Incoming</option>
                            <option value="outgoing" <?= $filter_type === 'outgoing' ? 'selected' : '' ?>>Outgoing</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-outline" onclick="clearFilters()" style="width: 100%;">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
                
                <div class="stats-section">
                    <h3 class="section-title">
                        <i class="fas fa-chart-bar"></i>
                        Overview
                    </h3>
                    
                    <div class="stats-grid">
                        <div class="stat-item" onclick="setFilter('status', 'all')">
                            <div class="stat-number"><?= $count_all ?></div>
                            <div class="stat-label">Total Messages</div>
                        </div>
                        <div class="stat-item unread" onclick="setFilter('status', 'unread')">
                            <div class="stat-number"><?= $count_unread ?></div>
                            <div class="stat-label">Unread</div>
                        </div>
                        <div class="stat-item read" onclick="setFilter('status', 'read')">
                            <div class="stat-number"><?= $count_read ?></div>
                            <div class="stat-label">Read</div>
                        </div>
                        <div class="stat-item replied" onclick="setFilter('status', 'replied')">
                            <div class="stat-number"><?= $count_replied ?></div>
                            <div class="stat-label">Replied</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="messages-header">
                    <div class="header-title">
                        Messages (<?= count($messages) ?>)
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-outline" onclick="refreshMessages()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-success" onclick="openSendMessageModal()">
                            <i class="fas fa-paper-plane"></i> New Message
                        </button>
                        <button class="btn btn-primary" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </div>
                </div>
                
                <div class="messages-list">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-envelope-open"></i>
                            </div>
                            <h3 class="empty-title">No messages found</h3>
                            <p class="empty-description">
                                <?php if ($filter_status !== 'all' || $filter_type !== 'all' || !empty($search_query)): ?>
                                    Try adjusting your filters to see more results.
                                <?php else: ?>
                                    All caught up! No messages to display.
                                <?php endif; ?>
                            </p>
                            <?php if ($filter_status !== 'all' || $filter_type !== 'all' || !empty($search_query)): ?>
                                <button class="btn btn-primary" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Clear Filters
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item <?= $message['status'] ?>" 
                                 onclick="openMessageModal(<?= $message['id'] ?>)">
                                <div class="message-header">
                                    <div style="flex: 1;">
                                        <div class="message-sender">
                                            <?= htmlspecialchars($message['user_name'] ?? 'Unknown User') ?>
                                            <?php if ($message['message_type'] === 'outgoing'): ?>
                                                <span class="status-badge badge-outgoing">Outgoing</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-email">
                                            <?= htmlspecialchars($message['user_email'] ?? 'No email') ?>
                                        </div>
                                    </div>
                                    <div class="status-badge badge-<?= $message['status'] ?>">
                                        <?= ucfirst($message['status']) ?>
                                    </div>
                                </div>
                                
                                <div class="message-subject">
                                    <?= htmlspecialchars($message['subject']) ?>
                                </div>
                                
                                <div class="message-preview">
                                    <?= htmlspecialchars(substr($message['message'], 0, 150)) ?>
                                    <?= strlen($message['message']) > 150 ? '...' : '' ?>
                                </div>
                                
                                <div class="message-footer">
                                    <div class="message-time">
                                        <i class="far fa-clock"></i>
                                        <?= date('M j, Y g:i A', strtotime($message['created_at'])) ?>
                                    </div>
                                    <div class="message-actions">
                                        <?php if ($message['status'] === 'unread' && $message['message_type'] === 'incoming'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="event.stopPropagation();">
                                                <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                <input type="hidden" name="action" value="mark_read">
                                                <button type="submit" class="action-btn btn-success">
                                                    <i class="fas fa-check"></i> Mark Read
                                                </button>
                                            </form>
                                        <?php elseif ($message['message_type'] === 'incoming'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="event.stopPropagation();">
                                                <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                <input type="hidden" name="action" value="mark_unread">
                                                <button type="submit" class="action-btn btn-warning">
                                                    <i class="fas fa-envelope"></i> Mark Unread
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($message['message_type'] === 'incoming'): ?>
                                            <button type="button" class="action-btn btn-primary" 
                                                    onclick="event.stopPropagation(); openReplyModal(<?= $message['id'] ?>)">
                                                <i class="fas fa-reply"></i> Reply
                                            </button>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="event.stopPropagation();">
                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="action-btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this message?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Detail Modal -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Message Details</h3>
                <button class="close-btn" onclick="closeMessageModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="message-detail" id="messageDetail">
                    <!-- Message details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal" id="replyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Reply to Message</h3>
                <button class="close-btn" onclick="closeReplyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="replyForm">
                    <input type="hidden" name="message_id" id="replyMessageId">
                    <input type="hidden" name="action" value="reply">
                    
                    <div class="form-group">
                        <label class="form-label" for="reply_content">Your Response</label>
                        <textarea class="form-textarea" id="reply_content" name="reply_content" 
                                  placeholder="Type your response here..." required></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeReplyModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Send Message Modal -->
    <div class="modal" id="sendMessageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Send New Message</h3>
                <button class="close-btn" onclick="closeSendMessageModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="sendMessageForm">
                    <input type="hidden" name="action" value="send_message">
                    
                    <div class="form-group">
                        <label class="form-label" for="user_id">Select User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="subject">Subject</label>
                        <input type="text" class="form-input" id="subject" name="subject" 
                               placeholder="Enter message subject..." required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="message_content">Message</label>
                        <textarea class="form-textarea" id="message_content" name="message_content" 
                                  placeholder="Type your message here..." required></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeSendMessageModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const type = document.getElementById('typeFilter').value;
            const search = document.querySelector('.search-input').value;
            
            const params = new URLSearchParams();
            if (status !== 'all') params.append('status', status);
            if (type !== 'all') params.append('type', type);
            if (search) params.append('search', search);
            
            window.location.href = 'messages.php?' + params.toString();
        }

        function clearFilters() {
            window.location.href = 'messages.php';
        }

        function setFilter(type, value) {
            if (type === 'status') {
                document.getElementById('statusFilter').value = value;
            }
            applyFilters();
        }

        function refreshMessages() {
            window.location.reload();
        }

        function markAllAsRead() {
            if (confirm('Are you sure you want to mark all messages as read?')) {
                // This would typically be implemented with an AJAX call
                showNotification('All messages marked as read!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        }

        function openMessageModal(messageId) {
            const modal = document.getElementById('messageModal');
            const detailDiv = document.getElementById('messageDetail');
            
            // Show loading state
            detailDiv.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                    <p style="margin-top: 1rem; color: var(--gray);">Loading message details...</p>
                </div>
            `;
            
            // Show modal
            modal.style.display = 'block';
            
            // Simulate loading message details
            setTimeout(() => {
                detailDiv.innerHTML = `
                    <div class="detail-row">
                        <div class="detail-label">From</div>
                        <div class="detail-value">User Name (user@example.com)</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Subject</div>
                        <div class="detail-value">Message Subject</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Message</div>
                        <div class="detail-value">
                            This is the full message content that would be loaded from the server.
                            In a real implementation, this would be fetched via AJAX call to get actual message details.
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Received</div>
                        <div class="detail-value"><?= date('M j, Y g:i A') ?></div>
                    </div>
                `;
            }, 500);
        }

        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        function openReplyModal(messageId) {
            document.getElementById('replyMessageId').value = messageId;
            document.getElementById('replyModal').style.display = 'block';
        }

        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
        }

        function openSendMessageModal() {
            document.getElementById('sendMessageModal').style.display = 'block';
        }

        function closeSendMessageModal() {
            document.getElementById('sendMessageModal').style.display = 'none';
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '1000';
            notification.style.minWidth = '300px';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['messageModal', 'replyModal', 'sendMessageModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Handle form submissions
        document.getElementById('replyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                showNotification('Reply sent successfully!', 'success');
                closeReplyModal();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Refresh the page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }, 1500);
        });

        document.getElementById('sendMessageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                showNotification('Message sent successfully!', 'success');
                closeSendMessageModal();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Refresh the page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }, 1500);
        });

        // Auto-focus search input when pressing Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('.search-input').focus();
            }
        });
    </script>
</body>
</html>