<?php
session_start();

$host = 'localhost';
$dbname = 'tripmate';
$db_username = 'root';
$db_password = '';

$error = '';
$success = '';
$showOTPField = false;
$otpGenerated = false;

// Include PHPMailer
$phpmailer_path = 'smtp/PHPMailerAutoload.php';
if (!file_exists($phpmailer_path)) {
    die("PHPMailer not found at: $phpmailer_path");
}
include($phpmailer_path);

function debugLog($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

function generateOTP() {
    return rand(100000, 999999);
}

// Send OTP function with better error handling
function sendOTP($receiverEmail, $otp) {
    debugLog("Attempting to send OTP to: $receiverEmail");
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ranajitbarik071@gmail.com';
        $mail->Password = 'orjp uexj udjt garu'; // Your app password
        
        // Try TLS first (more reliable for Gmail)
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        $mail->CharSet = 'UTF-8';
        
        // Disable SSL verification for local testing (REMOVE in production)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->setFrom('ranajitbarik071@gmail.com', 'TripMate Security');
        $mail->addAddress($receiverEmail);
        
        $mail->isHTML(true);
        $mail->Subject = 'TripMate Admin Login OTP: ' . $otp;
        
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;">
            <div style="background: #4f46e5; color: white; padding: 20px; text-align: center;"><h2>TripMate Admin</h2></div>
            <div style="padding: 20px; text-align: center;">
                <p>Your login verification code is:</p>
                <h1 style="color: #4f46e5; letter-spacing: 5px;">' . $otp . '</h1>
                <p style="color: #777; font-size: 12px;">This code expires in 10 minutes.</p>
            </div>
        </div>';
        
        // Alternative plain text version
        $mail->AltBody = "Your TripMate OTP is: $otp. Valid for 10 minutes.";
        
        if ($mail->send()) {
            debugLog("OTP sent successfully to: $receiverEmail");
            return true;
        } else {
            $errorInfo = $mail->ErrorInfo;
            debugLog("PHPMailer error: $errorInfo");
            return "Mailer Error: " . $errorInfo;
        }
    } catch (phpmailerException $e) {
        debugLog("PHPMailer Exception: " . $e->errorMessage());
        return "PHPMailer Exception: " . $e->errorMessage();
    } catch (Exception $e) {
        debugLog("General Exception: " . $e->getMessage());
        return "Error: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $enteredOTP = trim($_POST['otp'] ?? '');
        $storedOTP = $_SESSION['login_otp'] ?? '';
        $adminId = $_SESSION['temp_admin_id'] ?? '';
        $adminName = $_SESSION['temp_admin_name'] ?? '';
        $adminEmail = $_SESSION['temp_admin_email'] ?? '';
        $otpTime = $_SESSION['otp_time'] ?? 0;
        
        if (empty($enteredOTP)) {
            $error = "Please enter the OTP";
            $showOTPField = true;
        } elseif ($enteredOTP == $storedOTP) {
            if (time() - $otpTime > 600) {
                $error = "OTP has expired. Please request a new one.";
                unset($_SESSION['login_otp'], $_SESSION['temp_admin_id'], $_SESSION['temp_admin_name'], $_SESSION['temp_admin_email'], $_SESSION['otp_time']);
                $showOTPField = false;
            } else {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $adminId;
                $_SESSION['admin_name'] = $adminName;
                $_SESSION['admin_email'] = $adminEmail;
                
                unset($_SESSION['login_otp'], $_SESSION['temp_admin_id'], $_SESSION['temp_admin_name'], $_SESSION['temp_admin_email'], $_SESSION['otp_time']);
                header("Location: admin_dasbord.php");
                exit;
            }
        } else {
            $error = "Invalid OTP. Please try again.";
            $showOTPField = true;
        }
    }
    elseif (isset($_POST['username'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($username && $password) {
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $pdo->prepare("SELECT id, name, email, password FROM admin WHERE name = ? OR email = ? LIMIT 1");
                $stmt->execute([$username, $username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    $otp = generateOTP();
                    $_SESSION['temp_admin_id'] = $admin['id'];
                    $_SESSION['temp_admin_name'] = $admin['name'];
                    $_SESSION['temp_admin_email'] = $admin['email'];
                    $_SESSION['login_otp'] = $otp;
                    $_SESSION['otp_time'] = time();
                    
                    $sendResult = sendOTP($admin['email'], $otp);
                    
                    if ($sendResult === true) {
                        $success = "✓ Verification code sent to: " . maskEmail($admin['email']);
                        $showOTPField = true;
                        $otpGenerated = true;
                    } else {
                        // Log the error but show user-friendly message
                        debugLog("Failed to send OTP to {$admin['email']}: $sendResult");
                        
                        // For development - show the actual OTP (REMOVE IN PRODUCTION)
                        $error = "⚠️ SMTP Error. For testing, use OTP: <strong>$otp</strong>";
                        
                        // Uncomment for production:
                        // $error = "Unable to send verification email. Please try again later.";
                        
                        unset($_SESSION['temp_admin_id'], $_SESSION['temp_admin_name'], $_SESSION['temp_admin_email'], $_SESSION['login_otp'], $_SESSION['otp_time']);
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } catch (Exception $e) {
                $error = "Database error. Please try again.";
                debugLog("Database error: " . $e->getMessage());
            }
        } else {
            $error = "Please fill both fields.";
        }
    }
}

// Helper function to mask email
function maskEmail($email) {
    $parts = explode("@", $email);
    $name = $parts[0];
    $domain = $parts[1];
    $maskedName = substr($name, 0, 3) . str_repeat("*", strlen($name) - 3);
    return $maskedName . "@" . $domain;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../loader/admin_loder/loader.css">

    <style>
        /* === CSS VARIABLES FOR THEMES (Matched from index.html) === */
        :root {
            --bg-base: #f8fafc;
            --bg-surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --primary: #4f46e5; 
            --secondary: #06b6d4; 
            --nav-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(79, 70, 229, 0.15);
            --shadow-color: rgba(15, 23, 42, 0.08);
            --glow-color: rgba(6, 182, 212, 0.4);
            
            --input-bg: #f8fafc;
            --input-border: rgba(79, 70, 229, 0.15);
            --icon-color: #64748b;
        }

        [data-theme="dark"] {
            --bg-base: #09090b; 
            --bg-surface: #18181b;
            --text-main: #f8fafc;
            --text-muted: #cbd5e1;
            --primary: #818cf8; 
            --secondary: #22d3ee; 
            --nav-bg: rgba(24, 24, 27, 0.75);
            --card-border: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.6);
            --glow-color: rgba(34, 211, 238, 0.3);

            --input-bg: #09090b;
            --input-border: rgba(255, 255, 255, 0.1);
            --icon-color: #94a3b8;
        }

        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Inter', sans-serif; text-decoration: none; list-style: none;
        }

        body {
            display: flex; flex-direction: column; min-height: 100vh;
            justify-content: center; align-items: center;
            background-color: var(--bg-base);
            color: var(--text-main);
            background-image: radial-gradient(circle at top right, rgba(6, 182, 212, 0.05), transparent 40%),
                              radial-gradient(circle at bottom left, rgba(79, 70, 229, 0.05), transparent 40%);
            transition: background-color 0.4s ease, color 0.4s ease;
            overflow: hidden;
        }

        /* --- FLOATING PILL HEADER --- */
        .admin-header {
            position: fixed; top: 20px; left: 5%; width: 90%; height: 70px;
            background: var(--nav-bg);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border); border-radius: 50px;
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 30px; z-index: 1000;
            box-shadow: 0 10px 30px var(--shadow-color);
            transition: all 0.4s ease;
        }

        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; color: var(--text-main); }
        .logo i { color: var(--primary); font-size: 1.5rem; transform: rotate(-10deg); transition: transform 0.3s; }
        .logo:hover i { transform: rotate(0deg) scale(1.1); }
        .brand-text .trip { color: var(--text-main); }
        .brand-text .mate { color: var(--secondary); }
        
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .admin-info { font-size: 14px; font-weight: 600; color: var(--text-muted); display: none; }
        
        @media screen and (min-width: 600px) {
            .admin-info { display: block; }
        }

        /* BUTTONS IN HEADER */
        .home-btn {
            display: flex; align-items: center; gap: 8px;
            background: var(--bg-surface); border: 1px solid var(--card-border);
            color: var(--text-main); padding: 8px 18px; border-radius: 25px;
            font-size: 13px; font-weight: 700; text-decoration: none;
            transition: all 0.3s; box-shadow: 0 4px 10px var(--shadow-color);
        }
        .home-btn:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; border-color: transparent;
            transform: translateY(-2px);
        }

        .theme-toggle {
            background: var(--bg-surface); border: 1px solid var(--card-border);
            color: var(--text-main); width: 44px; height: 44px; border-radius: 50%;
            cursor: pointer; display: flex; justify-content: center; align-items: center;
            transition: all 0.3s; box-shadow: 0 4px 10px var(--shadow-color);
        }
        .theme-toggle:hover { transform: rotate(20deg) scale(1.1); color: var(--primary); border-color: var(--primary); }

        /* --- MAIN CONTAINER --- */
        .container {
            position: relative; width: 900px; height: 580px;
            background: var(--bg-surface);
            margin: 80px 20px 20px 20px; border-radius: 30px;
            box-shadow: 0 20px 60px var(--shadow-color);
            border: 1px solid var(--card-border);
            overflow: hidden;
            transition: background 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
        }

        .container h1 { font-size: 32px; font-weight: 800; margin-bottom: 5px; color: var(--text-main); letter-spacing: -0.5px; transition: color 0.3s ease; }
        .container p { font-size: 14px; margin: 10px 0 25px; color: var(--text-muted); transition: color 0.3s ease; }

        .form-box {
            position: absolute; right: 0; width: 50%; height: 100%;
            background: var(--bg-surface);
            display: flex; align-items: center; text-align: center; padding: 50px;
            z-index: 1; transition: 0.6s ease-in-out 1.2s, visibility 0s 1s, background 0.3s ease;
        }

        .container.active .form-box { right: 50%; }
        .form-box.login { visibility: visible; }
        .container.active .form-box.login { visibility: hidden; }
        .form-box.otp { visibility: hidden; }
        .container.active .form-box.otp { visibility: visible; }

        /* INPUTS */
        .input-box { position: relative; margin: 20px 0; }
        .input-box input {
            width: 100%; padding: 15px 50px 15px 20px;
            background: var(--input-bg); color: var(--text-main);
            border: 1px solid var(--input-border); border-radius: 12px;
            outline: none; font-size: 15px; font-weight: 500;
            transition: all 0.3s ease;
        }
        .input-box input:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px var(--glow-color); }
        .input-box i {
            position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
            font-size: 20px; color: var(--icon-color);
        }

        .forgot-link { margin: -10px 0 20px; text-align: left; }
        .forgot-link a { font-size: 13px; color: var(--secondary); font-weight: 600; transition: color 0.2s; }
        .forgot-link a:hover { color: var(--primary); text-decoration: underline; }

        /* BUTTONS */
        .btn {
            width: 100%; height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 30px; border: none; cursor: pointer;
            font-size: 15px; color: #fff; font-weight: 700;
            box-shadow: 0 4px 15px var(--glow-color);
            transition: all 0.3s ease;
        }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px var(--glow-color); }

        /* GRADIENT TOGGLE OVERLAY */
        .toggle-box { position: absolute; width: 100%; height: 100%; }
        .toggle-box::before {
            content: ""; position: absolute; left: -250%; width: 300%; height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 150px; z-index: 2; transition: 1.8s ease-in-out;
            box-shadow: inset 0 0 50px rgba(0,0,0,0.2);
        }
        .container.active .toggle-box::before { left: 50%; }

        .toggle-panel {
            position: absolute; width: 50%; height: 100%; color: #fff;
            display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;
            padding: 40px; z-index: 2; transition: 0.6s ease-in-out;
        }
        .toggle-panel h1 { color: #fff; font-size: 36px; margin-bottom: 15px; }
        .toggle-panel p { color: rgba(255,255,255,0.8); font-size: 15px; margin-bottom: 30px; line-height: 1.6; }

        .toggle-panel.toggle-left { left: 0; transition-delay: 1.2s; }
        .container.active .toggle-panel.toggle-left { left: -50%; transition-delay: 0.6s; }
        .toggle-panel.toggle-right { right: -50%; transition-delay: 0.6s; }
        .container.active .toggle-panel.toggle-right { right: 0; transition-delay: 1.2s; }

        .toggle-panel .btn { 
            width: 160px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
            border: 2px solid #fff; box-shadow: none; 
        }
        .toggle-panel .btn:hover { background: #fff; color: var(--primary); transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }

        /* ERROR MESSAGE DISPLAY */
        .message { 
            padding: 12px 15px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 10px; text-align: left; word-break: break-word;
        }
        .message.success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .message.error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        .message.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }

        /* OTP display for testing */
        .test-otp {
            background: var(--input-bg);
            border: 1px dashed var(--secondary);
            padding: 10px;
            border-radius: 12px;
            text-align: center;
            font-size: 24px;
            letter-spacing: 5px;
            font-weight: 800;
            color: var(--secondary);
            margin: 10px 0;
        }

        /* FOOTER */
        .admin-footer {
            position: fixed; bottom: 20px; 
            color: var(--text-muted); padding: 10px 20px; font-size: 13px; font-weight: 500;
            transition: color 0.3s ease; text-align: center; width: 100%;
        }
        .admin-footer .brand { font-weight: 800; margin-left: 5px; }
        .admin-footer .trip { color: var(--text-main); }
        .admin-footer .mate { color: var(--secondary); }

        @media screen and (max-width: 768px) {
            .container { height: calc(100vh - 140px); margin-top: 100px; width: 90%; }
            .form-box { bottom: 0; width: 100%; height: 70%; padding: 30px; }
            .container.active .form-box { right: 0; bottom: 30%; }
            .toggle-box::before { left: 0; top: -270%; width: 100%; height: 300%; border-radius: 20vw; }
            .container.active .toggle-box::before { left: 0; top: 70%; }
            .container.active .toggle-panel.toggle-left { left: 0; top: -30%; }
            .toggle-panel { width: 100%; height: 30%; padding: 20px; }
            .toggle-panel h1 { font-size: 24px; }
            .toggle-panel p { font-size: 13px; margin-bottom: 15px; }
            .toggle-panel.toggle-left { top: 0; }
            .toggle-panel.toggle-right { right: 0; bottom: -30%; }
            .container.active .toggle-panel.toggle-right { bottom: 0; }
            .admin-header { top: 15px; height: 60px; width: 95%; left: 2.5%; padding: 0 15px; }
            .home-btn span { display: none; } /* Hide text on very small screens, keep icon */
        }
    </style>
</head>
<body>

<div class="admin-header">
    <div class="logo">
        <i class="fa-solid fa-paper-plane"></i>
        <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
    </div>
    <div class="header-actions">
        <div class="admin-info">Admin Portal</div>
        <a href="../main/index.html" class="home-btn" aria-label="Back to Home">
            <i class="fas fa-home"></i> <span>Home</span>
        </a>
        <div class="theme-toggle" id="theme-toggle">
            <i class='bx bx-moon' id="theme-icon"></i>
        </div>
    </div>
</div>

<div id="admin-lottie-loader" class="hidden" aria-hidden="true">
  <div id="admin-lottie-container" role="img" aria-label="Loading animation"></div>
</div>

<div class="container <?php echo $showOTPField ? 'active' : ''; ?>">
    <div class="form-box login">
        <form action="" method="POST" style="width: 100%;">
            <h1>Welcome Back</h1>
            <p>Enter your admin credentials to continue</p>
            <?php if ($error && !$showOTPField): ?>
                <div class="message error"><i class='bx bx-error-circle'></i><span><?= htmlspecialchars($error) ?></span></div>
            <?php endif; ?>
            <div class="input-box">
                <input type="text" name="username" placeholder="Username or Email" required>
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>
            <div class="forgot-link">
                <a href="#">Forgot Password?</a>
            </div>
            <button type="submit" class="btn">Authenticate</button>
        </form>
    </div>

    <div class="form-box otp">
        <form action="" method="POST" style="width: 100%;">
            <input type="hidden" name="verify_otp" value="1">
            <h1>Verification</h1>
            <p>Enter the 6-digit code sent to your email</p>
            <?php if ($success && $showOTPField): ?>
                <div class="message success"><i class='bx bx-check-circle'></i><span><?= htmlspecialchars($success) ?></span></div>
            <?php endif; ?>
            <?php if ($error && $showOTPField): ?>
                <?php if (strpos($error, 'OTP:') !== false): ?>
                    <div class="message warning">
                        <i class='bx bx-error-circle'></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php else: ?>
                    <div class="message error"><i class='bx bx-error-circle'></i><span><?= htmlspecialchars($error) ?></span></div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['login_otp']) && $showOTPField && strpos($error ?? '', 'OTP:') !== false): ?>
                <div class="test-otp">
                    <?= $_SESSION['login_otp'] ?>
                </div>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: -15px; margin-bottom: 10px;">(Test OTP - SMTP not working)</p>
            <?php endif; ?>
            
            <div class="input-box">
                <input type="text" name="otp" placeholder="Enter OTP" required maxlength="6" inputmode="numeric">
                <i class='bx bxs-key'></i>
            </div>
            <button type="submit" class="btn">Verify Securely</button>
        </form>
    </div>

    <div class="toggle-box">
        <div class="toggle-panel toggle-left">
            <h1>System Access</h1>
            <p>Manage destinations, monitor budgets, and oversee the TripMate community from the control panel.</p>
        </div>
        <div class="toggle-panel toggle-right">
            <h1>Verify Identity</h1>
            <p>Didn't receive the code or need to use a different admin account?</p>
            <button class="btn login-btn">Back to Login</button>
        </div>
    </div>
</div>

<div class="admin-footer">
    © <?= date('Y') ?> 
    <span class="brand">
        <span class="trip">Trip</span><span class="mate">Mate</span>
    </span> · Admin Portal
</div>

<script>
    // Theme Toggle Logic matched to index.html style
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const htmlElement = document.documentElement;

    // Check Local Storage for saved theme
    const savedTheme = localStorage.getItem('tripmate-theme') || localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        htmlElement.setAttribute('data-theme', 'dark');
        themeIcon.className = 'bx bx-sun';
    }

    themeToggle.addEventListener('click', () => {
        if (htmlElement.getAttribute('data-theme') === 'dark') {
            htmlElement.removeAttribute('data-theme');
            localStorage.setItem('tripmate-theme', 'light');
            localStorage.setItem('theme', 'light'); // Keep compatibility with older scripts
            themeIcon.className = 'bx bx-moon';
        } else {
            htmlElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('tripmate-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            themeIcon.className = 'bx bx-sun';
        }
    });

    // Login Panel Toggle
    const container = document.querySelector('.container');
    const loginBtn = document.querySelector('.login-btn');

    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            container.classList.remove('active');
            setTimeout(() => {
                window.location.href = window.location.pathname;
            }, 600);
        });
    }

    // Loader logic
    document.querySelectorAll('form').forEach((form) => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (typeof window.showAdminLoader === 'function') {
          try { window.showAdminLoader(); } catch (err) {}
        } else {
          const loader = document.getElementById('admin-lottie-loader');
          if (loader) loader.classList.remove('hidden');
        }
        setTimeout(() => {
          try { form.submit(); } catch (err) {}
        }, 80);
      });
    });
</script>

<script src="../loader/admin_loder/loader.js" defer></script>
<script src="../loader/admin_loder/admin_login_loader/login_loader.js" defer></script>

</body>
</html>