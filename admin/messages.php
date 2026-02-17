<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

include '../database/dbconfig.php';
include 'admin_header.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];

$admin_name = $admin['name'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'] ?: 'https://via.placeholder.com/100';

// Database connection for messages
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

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $message_id = $_POST['message_id'] ?? null;
    
    if ($action === 'send_message') {
        // Handle sending new email
        $user_id = $_POST['user_id'] ?? null;
        $subject = $_POST['subject'] ?? '';
        $message_content = $_POST['message_content'] ?? '';
        $email_type = $_POST['email_type'] ?? 'private';
        
        if (!empty($subject) && !empty($message_content)) {
            if ($email_type === 'private' && $user_id) {
                // Send to one user
                $send_stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message, message_type, status, created_at) VALUES (?, ?, ?, 'outgoing', 'sent', NOW())");
                $send_stmt->execute([$user_id, $subject, $message_content]);
                $success_message = "Email sent to user successfully!";
            } 
            elseif ($email_type === 'broadcast') {
                // Send to all users
                $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_ASSOC);
                $sent_count = 0;
                
                foreach ($users as $user) {
                    $send_stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message, message_type, status, created_at) VALUES (?, ?, ?, 'outgoing', 'sent', NOW())");
                    $send_stmt->execute([$user['id'], $subject, $message_content]);
                    $sent_count++;
                }
                
                $success_message = "Broadcast email sent to $sent_count users successfully!";
            } else {
                $error_message = "Please select a user for private email!";
            }
        } else {
            $error_message = "Please fill all fields!";
        }
    }
    elseif ($message_id) {
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
                    $msg_stmt = $pdo->prepare("SELECT user_id, subject FROM messages WHERE id = ?");
                    $msg_stmt->execute([$message_id]);
                    $message = $msg_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $reply_stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message, message_type, status, created_at) VALUES (?, ?, ?, 'outgoing', 'sent', NOW())");
                    $subject = "Re: " . $message['subject'];
                    $reply_stmt->execute([$message['user_id'], $subject, $reply_content]);
                    
                    $update_stmt = $pdo->prepare("UPDATE messages SET status = 'replied' WHERE id = ?");
                    $update_stmt->execute([$message_id]);
                    
                    $success_message = "Reply sent successfully!";
                }
                break;
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
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

// Get users for sending messages
$users = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get total users count for broadcast
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Get unread count for notifications
$unread_count = $count_unread;
?>

<style>
    /* Email System Styles - Fixed for admin template */
    .email-wrapper {
        width: 100%;
        padding: 1rem;
    }
    
    .email-container {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
        height: calc(100vh - 180px);
        margin-top: 1rem;
    }

    /* Inbox Section */
    .inbox-section {
        background: var(--card-bg);
        border-radius: 10px;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .inbox-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid rgba(0,0,0,0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .inbox-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--dark);
    }

    .inbox-stats {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .inbox-controls {
        padding: 1rem 2rem;
        border-bottom: 1px solid rgba(0,0,0,0.08);
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .search-box {
        flex: 1;
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 6px;
        font-size: 0.9rem;
        background: var(--muted);
        color: var(--text);
    }

    .search-box i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
    }

    .filter-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .filter-btn {
        padding: 0.5rem 1rem;
        border: 1px solid rgba(0,0,0,0.1);
        background: var(--muted);
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        color: var(--text);
    }

    .filter-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .email-list {
        flex: 1;
        overflow-y: auto;
    }

    .email-item {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }

    .email-item:hover {
        background: var(--muted);
    }

    .email-item.unread {
        background: rgba(67, 97, 238, 0.05);
        border-left: 3px solid var(--primary);
    }

    .email-checkbox {
        margin-top: 0.25rem;
    }

    .email-content {
        flex: 1;
    }

    .email-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }

    .email-sender {
        font-weight: 600;
        color: var(--dark);
    }

    .email-time {
        color: var(--gray);
        font-size: 0.85rem;
    }

    .email-subject {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.25rem;
    }

    .email-preview {
        color: var(--gray);
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .email-actions {
        display: flex;
        gap: 0.5rem;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .email-item:hover .email-actions {
        opacity: 1;
    }

    .email-action-btn {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 4px;
        background: transparent;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-reply {
        color: var(--primary);
    }

    .btn-reply:hover {
        background: var(--primary);
        color: white;
    }

    .btn-delete {
        color: var(--danger);
    }

    .btn-delete:hover {
        background: var(--danger);
        color: white;
    }

    /* Compose Section */
    .compose-section {
        background: var(--card-bg);
        border-radius: 10px;
        box-shadow: var(--shadow);
        padding: 2rem;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .compose-header {
        margin-bottom: 2rem;
    }

    .compose-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.5rem;
    }

    .compose-subtitle {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .compose-form {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .email-type-selector {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 0.5rem;
        background: var(--muted);
    }

    .type-option {
        flex: 1;
        text-align: center;
        padding: 0.75rem;
        border: 2px solid transparent;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        color: var(--text);
    }

    .type-option.active {
        background: var(--card-bg);
        border-color: var(--primary);
        color: var(--primary);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .type-option i {
        margin-right: 0.5rem;
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

    .form-select, .form-input, .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 6px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: var(--muted);
        color: var(--text);
    }

    .form-textarea {
        resize: vertical;
        min-height: 200px;
        font-family: inherit;
    }

    .form-select:focus, .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }

    .user-selection {
        display: block;
    }

    .broadcast-info {
        display: none;
        background: rgba(67, 97, 238, 0.1);
        padding: 1rem;
        border-radius: 6px;
        border-left: 4px solid var(--primary);
        margin-bottom: 1rem;
        color: var(--text);
    }

    .broadcast-info i {
        color: var(--primary);
        margin-right: 0.5rem;
    }

    .compose-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn-send {
        padding: 0.75rem 2rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-send:hover {
        background: var(--secondary);
        transform: translateY(-1px);
    }

    .btn-cancel {
        padding: 0.75rem 2rem;
        background: var(--gray);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #5a6268;
    }

    /* Alert */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-success {
        background: rgba(76, 201, 240, 0.2);
        color: var(--success);
        border: 1px solid rgba(76, 201, 240, 0.3);
    }

    .alert-error {
        background: rgba(247, 37, 133, 0.1);
        color: var(--danger);
        border: 1px solid rgba(247, 37, 133, 0.2);
    }

    /* Empty State */
    .empty-inbox {
        text-align: center;
        padding: 3rem;
        color: var(--gray);
    }

    .empty-inbox i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .email-container {
            grid-template-columns: 1fr;
        }
        
        .compose-section {
            height: 500px;
        }
    }

    @media (max-width: 768px) {
        .inbox-controls {
            flex-direction: column;
            gap: 1rem;
        }
        
        .filter-buttons {
            width: 100%;
            justify-content: space-between;
        }
        
        .email-header {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .email-type-selector {
            flex-direction: column;
        }
    }
</style>

<div class="main-content">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="email-container">
        <!-- Inbox Section -->
        <div class="inbox-section">
            <div class="inbox-header">
                <div class="inbox-title">
                    <i class="fas fa-inbox"></i> Inbox
                </div>
                <div class="inbox-stats">
                    <?= count($messages) ?> messages • <?= $count_unread ?> unread
                </div>
            </div>

            <div class="inbox-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search emails..." id="searchInput" 
                           value="<?= htmlspecialchars($search_query) ?>"
                           onkeyup="if(event.key === 'Enter') searchEmails()">
                </div>
                <div class="filter-buttons">
                    <button class="filter-btn <?= $filter_status === 'all' ? 'active' : '' ?>" onclick="filterEmails('all')">All</button>
                    <button class="filter-btn <?= $filter_status === 'unread' ? 'active' : '' ?>" onclick="filterEmails('unread')">Unread</button>
                    <button class="filter-btn <?= $filter_status === 'read' ? 'active' : '' ?>" onclick="filterEmails('read')">Read</button>
                </div>
            </div>

            <div class="email-list" id="emailList">
                <?php if (empty($messages)): ?>
                    <div class="empty-inbox">
                        <i class="fas fa-inbox"></i>
                        <h3>No emails</h3>
                        <p>Your inbox is empty</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="email-item <?= $message['status'] === 'unread' ? 'unread' : '' ?>" 
                             onclick="viewEmail(<?= $message['id'] ?>)">
                            <div class="email-checkbox">
                                <input type="checkbox" onclick="event.stopPropagation()">
                            </div>
                            <div class="email-content">
                                <div class="email-header">
                                    <div class="email-sender">
                                        <?= htmlspecialchars($message['user_name'] ?? 'Unknown User') ?>
                                    </div>
                                    <div class="email-time">
                                        <?= date('M j, g:i A', strtotime($message['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="email-subject">
                                    <?= htmlspecialchars($message['subject']) ?>
                                </div>
                                <div class="email-preview">
                                    <?= htmlspecialchars(substr($message['message'], 0, 100)) ?>
                                    <?= strlen($message['message']) > 100 ? '...' : '' ?>
                                </div>
                            </div>
                            <div class="email-actions">
                                <button class="email-action-btn btn-reply" onclick="event.stopPropagation(); replyEmail(<?= $message['id'] ?>)">
                                    <i class="fas fa-reply"></i>
                                </button>
                                <form method="POST" onsubmit="event.stopPropagation(); return confirm('Delete this email?');">
                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="email-action-btn btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Compose Section -->
        <div class="compose-section">
            <div class="compose-header">
                <h3 class="compose-title">Send Email</h3>
                <p class="compose-subtitle">Choose email type and send</p>
            </div>

            <form method="POST" class="compose-form" id="composeForm">
                <input type="hidden" name="action" value="send_message">
                
                <!-- Email Type Selector -->
                <div class="email-type-selector">
                    <div class="type-option active" data-type="private" onclick="selectEmailType('private')">
                        <i class="fas fa-user"></i>
                        Private Email
                    </div>
                    <div class="type-option" data-type="broadcast" onclick="selectEmailType('broadcast')">
                        <i class="fas fa-bullhorn"></i>
                        Broadcast Email
                    </div>
                </div>
                <input type="hidden" name="email_type" id="emailType" value="private">

                <!-- Broadcast Info -->
                <div class="broadcast-info" id="broadcastInfo">
                    <i class="fas fa-info-circle"></i>
                    This email will be sent to all <?= $total_users ?> users
                </div>

                <!-- User Selection (Private Email) -->
                <div class="form-group user-selection" id="userSelection">
                    <label class="form-label">To (User)</label>
                    <select class="form-select" name="user_id" id="userSelect">
                        <option value="">Select a user...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-input" name="subject" placeholder="Enter email subject..." required>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Message</label>
                    <textarea class="form-textarea" name="message_content" placeholder="Type your message here..." required></textarea>
                </div>

                <div class="compose-actions">
                    <button type="button" class="btn-cancel" onclick="clearForm()">Clear</button>
                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane"></i> 
                        <span id="sendButtonText">Send Private Email</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Email Type Selection
    function selectEmailType(type) {
        // Update active button
        document.querySelectorAll('.type-option').forEach(option => {
            option.classList.remove('active');
        });
        document.querySelector(`[data-type="${type}"]`).classList.add('active');
        
        // Update hidden field
        document.getElementById('emailType').value = type;
        
        // Show/hide user selection
        const userSelection = document.getElementById('userSelection');
        const broadcastInfo = document.getElementById('broadcastInfo');
        const sendButtonText = document.getElementById('sendButtonText');
        
        if (type === 'private') {
            userSelection.style.display = 'block';
            broadcastInfo.style.display = 'none';
            sendButtonText.textContent = 'Send Private Email';
            document.getElementById('userSelect').required = true;
        } else {
            userSelection.style.display = 'none';
            broadcastInfo.style.display = 'block';
            sendButtonText.textContent = 'Send Broadcast Email';
            document.getElementById('userSelect').required = false;
        }
    }

    // View Email
    function viewEmail(emailId) {
        // Mark as read when viewing
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="message_id" value="${emailId}">
            <input type="hidden" name="action" value="mark_read">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // Reply to Email
    function replyEmail(emailId) {
        const replyContent = prompt('Type your reply:');
        if (replyContent && replyContent.trim() !== '') {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="message_id" value="${emailId}">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="reply_content" value="${replyContent}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Filter Emails
    function filterEmails(status) {
        window.location.href = `messages.php?status=${status}`;
    }

    // Search Emails
    function searchEmails() {
        const searchTerm = document.getElementById('searchInput').value;
        window.location.href = `messages.php?search=${encodeURIComponent(searchTerm)}`;
    }

    // Clear Compose Form
    function clearForm() {
        document.getElementById('composeForm').reset();
        selectEmailType('private'); // Reset to private email
    }

    // Auto-hide success message
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);
</script>

<?php include 'admin_footer.php'; ?>