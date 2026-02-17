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

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        
        try {
            $update_stmt = $pdo->prepare("UPDATE admin SET name = ?, email = ? WHERE id = ?");
            $update_stmt->execute([$name, $email, $_SESSION['admin_id']]);
            $success_message = "Profile updated successfully!";
            $admin['name'] = $name;
            $admin['email'] = $email;
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        
        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= $max_size) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $filename = 'admin_profile_' . $SESSION['admin_id'] . '' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Delete old profile picture if exists
                    if (!empty($admin['profile_pic']) && file_exists($admin['profile_pic'])) {
                        unlink($admin['profile_pic']);
                    }
                    
                    // Update database
                    $update_stmt = $pdo->prepare("UPDATE admin SET profile_pic = ? WHERE id = ?");
                    $update_stmt->execute([$file_path, $_SESSION['admin_id']]);
                    $admin['profile_pic'] = $file_path;
                    $success_message = "Profile picture updated successfully!";
                } else {
                    $error_message = "Error uploading file. Please try again.";
                }
            } else {
                $error_message = "File size too large. Maximum size is 5MB.";
            }
        } else {
            $error_message = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }
    
    // Handle profile picture removal
    if (isset($_POST['remove_profile_picture'])) {
        if (!empty($admin['profile_pic']) && file_exists($admin['profile_pic'])) {
            unlink($admin['profile_pic']);
        }
        
        $update_stmt = $pdo->prepare("UPDATE admin SET profile_pic = NULL WHERE id = ?");
        $update_stmt->execute([$_SESSION['admin_id']]);
        $admin['profile_pic'] = NULL;
        $success_message = "Profile picture removed successfully!";
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $admin['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    try {
                        $update_stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
                        $update_stmt->execute([$hashed_password, $_SESSION['admin_id']]);
                        $success_message = "Password changed successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error changing password: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Password must be at least 8 characters long!";
                }
            } else {
                $error_message = "New passwords do not match!";
            }
        } else {
            $error_message = "Current password is incorrect!";
        }
    }
}

// Get system statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_destinations = $pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM admin")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin - Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --secondary: #10b981;
            --accent: #f59e0b;
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #f59e0b;
            --dark: #1f2937;
            --gray: #6b7280;
            --light-gray: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
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

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-card {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
            height: fit-content;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: bold;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar .initials {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .profile-email {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .profile-badge {
            background: linear-gradient(135deg, var(--accent), #f97316);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }

        .profile-picture-actions {
            margin: 1.5rem 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .btn-file {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-file:hover {
            background: var(--primary-dark);
        }

        .btn-remove {
            background: var(--danger);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-remove:hover {
            background: #dc2626;
        }

        .btn-remove:disabled {
            background: var(--gray);
            cursor: not-allowed;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-item {
            background: var(--light-gray);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        .settings-panel {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .panel-tabs {
            display: flex;
            background: var(--light-gray);
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--gray);
        }

        .tab-btn.active {
            background: var(--white);
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
        }

        .tab-btn:hover:not(.active) {
            background: rgba(59, 130, 246, 0.1);
        }

        .tab-content {
            display: none;
            padding: 2rem;
        }

        .tab-content.active {
            display: block;
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

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

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

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .danger-zone {
            background: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .danger-title {
            color: var(--danger);
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .system-info {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            margin-top: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .info-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .info-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .info-label {
            color: var(--gray);
            font-size: 0.875rem;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin: 1rem 0;
            display: none;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
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
            
            .panel-tabs {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a href="admin_dasbord.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> Admin Settings</h1>
            <p>Manage your account and system preferences</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <?php if (!empty($admin['profile_pic']) && file_exists($admin['profile_pic'])): ?>
                        <img src="<?= htmlspecialchars($admin['profile_pic']) ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="initials"><?= strtoupper(substr($admin['name'], 0, 2)) ?></div>
                    <?php endif; ?>
                </div>
                <h2 class="profile-name"><?= htmlspecialchars($admin['name']) ?></h2>
                <p class="profile-email"><?= htmlspecialchars($admin['email']) ?></p>
                <span class="profile-badge">Administrator</span>
                
                <!-- Profile Picture Actions -->
                <div class="profile-picture-actions">
                    <form method="POST" enctype="multipart/form-data" id="profilePictureForm">
                        <div class="file-input-wrapper">
                            <button type="button" class="btn-file">
                                <i class="fas fa-camera"></i> Change Photo
                            </button>
                            <input type="file" name="profile_picture" accept="image/*" onchange="previewImage(this)">
                        </div>
                    </form>
                    
                    <form method="POST">
                        <button type="submit" name="remove_profile_picture" class="btn-remove" <?= empty($admin['profile_pic']) ? 'disabled' : '' ?>>
                            <i class="fas fa-trash"></i> Remove Photo
                        </button>
                    </form>
                </div>
                
                <img id="imagePreview" class="preview-image" alt="Preview">
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= $total_users ?></div>
                        <div class="stat-label">Users</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $total_destinations ?></div>
                        <div class="stat-label">Destinations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $total_admins ?></div>
                        <div class="stat-label">Admins</div>
                    </div>
                </div>
            </div>

            <!-- Settings Panel -->
            <div class="settings-panel">
                <div class="panel-tabs">
                    <button class="tab-btn active" onclick="switchTab('profile')">
                        <i class="fas fa-user"></i> Profile
                    </button>
                    <button class="tab-btn" onclick="switchTab('security')">
                        <i class="fas fa-shield-alt"></i> Security
                    </button>
                    <button class="tab-btn" onclick="switchTab('system')">
                        <i class="fas fa-server"></i> System
                    </button>
                </div>

                <!-- Profile Tab -->
                <div class="tab-content active" id="profile-tab">
                    <h3 class="section-title">
                        <i class="fas fa-user-edit"></i>
                        Profile Information
                    </h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-input" 
                                   value="<?= htmlspecialchars($admin['name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?= htmlspecialchars($admin['email']) ?>" required>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>

                <!-- Security Tab -->
                <div class="tab-content" id="security-tab">
                    <h3 class="section-title">
                        <i class="fas fa-key"></i>
                        Change Password
                    </h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label" for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-input" required minlength="8">
                            <small style="color: var(--gray); margin-top: 0.5rem; display: block;">
                                Password must be at least 8 characters long
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-input" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-shield-alt"></i> Change Password
                        </button>
                    </form>
                </div>

                <!-- System Tab -->
                <div class="tab-content" id="system-tab">
                    <h3 class="section-title">
                        <i class="fas fa-database"></i>
                        System Management
                    </h3>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-primary" onclick="clearCache()">
                            <i class="fas fa-broom"></i> Clear Cache
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-primary" onclick="exportData()">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                    </div>
                    
                    <div class="danger-zone">
                        <h4 class="danger-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Danger Zone
                        </h4>
                        <p style="margin-bottom: 1rem; color: var(--gray);">
                            These actions are irreversible. Please proceed with caution.
                        </p>
                        <button type="button" class="btn btn-danger" onclick="resetSystem()">
                            <i class="fas fa-trash-alt"></i> Reset All Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="system-info">
            <h3 class="section-title">
                <i class="fas fa-info-circle"></i>
                System Information
            </h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-value"><?= number_format($total_users) ?></div>
                    <div class="info-label">Total Users</div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-value"><?= number_format($total_destinations) ?></div>
                    <div class="info-label">Destinations</div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="info-value"><?= phpversion() ?></div>
                    <div class="info-label">PHP Version</div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="info-value"><?= date('Y-m-d') ?></div>
                    <div class="info-label">Today's Date</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity("Passwords don't match");
            } else {
                this.setCustomValidity('');
            }
        });

        // Image preview functionality
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Auto-submit the form when file is selected
                    document.getElementById('profilePictureForm').submit();
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        function clearCache() {
            if (confirm('Are you sure you want to clear the system cache?')) {
                showNotification('Cache cleared successfully!', 'success');
            }
        }

        function exportData() {
            showNotification('Data export started. You will receive an email when complete.', 'success');
        }

        function resetSystem() {
            if (confirm('⚠ WARNING: This will delete ALL data permanently. Are you absolutely sure?')) {
                if (confirm('This action cannot be undone. Type "DELETE" to confirm.')) {
                    showNotification('System reset initiated. Please wait...', 'error');
                }
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className =    `alert alert-${type}`;
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
                notification.remove();
            }, 5000);
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>