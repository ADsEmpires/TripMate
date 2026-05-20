<?php
session_start();

if (isset($_SESSION['contributor_id'])) {
    header('Location: contributor_dashboard.php');
    exit();
}

include '../database/dbconfig.php';

$error   = '';
$success = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Account details
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    
    // Personal details
    $mobile   = trim($_POST['mobile'] ?? '');
    $dob      = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $gender   = trim($_POST['gender'] ?? 'Prefer not to say');

    // Global Address details
    $address_line_1 = trim($_POST['address_line_1'] ?? '');
    $address_line_2 = trim($_POST['address_line_2'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $state_region   = trim($_POST['state_region'] ?? '');
    $postal_code    = trim($_POST['postal_code'] ?? '');
    $country        = trim($_POST['country'] ?? '');
    
    // Contributor Profile Details
    $social_link    = trim($_POST['social_link'] ?? '');
    $bio            = trim($_POST['bio'] ?? '');

    // Validation
    if (!$name || !$username || !$email || !$password || !$confirm) {
        $error = 'Full Name, Username, Email, and Password fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        if (empty($error)) {
            $check_email = $conn->prepare("SELECT id FROM contributors WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $check_email->store_result();

            $check_user = $conn->prepare("SELECT id FROM contributors WHERE username = ?");
            $check_user->bind_param("s", $username);
            $check_user->execute();
            $check_user->store_result();

            if ($check_email->num_rows > 0) {
                $error = 'This email is already registered. <a href="contributor_login.php">Login here</a>.';
            } elseif ($check_user->num_rows > 0) {
                $error = 'This username is already taken. Please choose another one.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                
                $sql = "INSERT INTO contributors (name, username, email, password, mobile, dob, gender, address_line_1, address_line_2, city, state_region, postal_code, country, social_link, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                $stmt->bind_param("sssssssssssssss", $name, $username, $email, $hash, $mobile, $dob, $gender, $address_line_1, $address_line_2, $city, $state_region, $postal_code, $country, $social_link, $bio);

                if ($stmt->execute()) {
                    $success = 'Account created successfully! <a href="contributor_login.php">Login here</a>.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                $stmt->close();
            }
            $check_email->close();
            $check_user->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contributor Registration · TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* TripMate Global Design System */
        :root {
            --bg-base: #f8fafc; --bg-surface: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --primary: #4f46e5; --secondary: #06b6d4; --nav-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(79, 70, 229, 0.15); --shadow-color: rgba(15, 23, 42, 0.08); --glow-color: rgba(6, 182, 212, 0.4);
            --danger: #ef4444; --danger-bg: rgba(239, 68, 68, 0.1);
            --success: #10b981; --success-bg: rgba(16, 185, 129, 0.1);
        }
        body.dark-mode {
            --bg-base: #09090b; --bg-surface: #18181b; --text-main: #f8fafc; --text-muted: #cbd5e1;
            --primary: #818cf8; --secondary: #22d3ee; --nav-bg: rgba(24, 24, 27, 0.75);
            --card-border: rgba(255, 255, 255, 0.1); --shadow-color: rgba(0, 0, 0, 0.6); --glow-color: rgba(34, 211, 238, 0.3);
            --danger: #f87171; --danger-bg: rgba(248, 113, 113, 0.1);
            --success: #34d399; --success-bg: rgba(52, 211, 153, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 120px 1rem 3rem 1rem; position: relative; overflow-x: hidden; transition: background-color 0.4s ease, color 0.4s ease; }
        body::before { content: ''; position: fixed; top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, var(--glow-color) 0%, transparent 60%); opacity: 0.5; pointer-events: none; }
        
        /* Floating Pill Navbar */
        .navbar { position: fixed; top: 20px; left: 5%; width: 90%; height: 70px; background: var(--nav-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--card-border); border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; z-index: 1000; box-shadow: 0 10px 30px var(--shadow-color); }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; text-decoration: none; color: var(--text-main); }
        .logo i { color: var(--primary); font-size: 1.5rem; transform: rotate(-10deg); transition: transform 0.3s; }
        .logo:hover i { transform: rotate(0deg) scale(1.1); }
        .brand-text .trip { color: var(--text-main); } .brand-text .mate { color: var(--secondary); }
        
        .nav-links { display: flex; align-items: center; gap: 20px; list-style: none; margin-left: auto; margin-right: 20px; }
        .nav-links a { color: var(--text-main); text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .nav-links a:hover { color: var(--secondary); }
        .nav-btn { background: linear-gradient(135deg, var(--primary), var(--secondary)) !important; color: white !important; padding: 10px 24px; border-radius: 30px; box-shadow: 0 4px 15px var(--glow-color); transition: all 0.3s !important; border: none; white-space: nowrap; text-decoration: none; font-weight: 700; }
        .nav-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px var(--glow-color); }
        
        .theme-toggle { background: var(--bg-surface); border: 1px solid var(--card-border); color: var(--text-main); width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: all 0.3s; box-shadow: 0 4px 10px var(--shadow-color); flex-shrink: 0; }
        .theme-toggle:hover { transform: rotate(20deg) scale(1.1); color: var(--primary); border-color: var(--primary); }

        @media (max-width: 768px) {
            .navbar { width: 95%; left: 2.5%; padding: 0 20px; }
            .nav-links { display: none; }
        }

        /* Form Card Layout */
        .auth-card { width: 100%; max-width: 700px; background: var(--bg-surface); padding: 45px 40px; border-radius: 24px; box-shadow: 0 20px 40px var(--shadow-color); border: 1px solid var(--card-border); position: relative; animation: fadeUp 0.5s ease both; z-index: 1; }
        .auth-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: linear-gradient(90deg, var(--primary), var(--secondary)); }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        h1 { font-size: 1.8rem; font-weight: 800; text-align: center; margin-bottom: 0.3rem; letter-spacing: -0.5px; }
        .subtitle { color: var(--text-muted); font-size: 0.95rem; text-align: center; margin-bottom: 2.5rem; }
        .section-title { font-size: 1.05rem; font-weight: 700; margin-bottom: 1.2rem; color: var(--text-main); display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--card-border); padding-bottom: 10px; margin-top: 10px; }
        .section-title i { color: var(--secondary); }

        .alert { padding: 12px 15px; border-radius: 12px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 10px; font-weight: 500; }
        .alert a { color: inherit; font-weight: 700; text-decoration: underline; }
        .alert-error { background: var(--danger-bg); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); }
        .alert-success { background: var(--success-bg); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.4rem; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.4rem; }
        @media (max-width: 600px) { .form-grid, .form-grid-3 { grid-template-columns: 1fr; gap: 0; } .auth-card { padding: 30px 20px; } }

        .form-group { margin-bottom: 1.4rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; }
        .input-wrap { position: relative; }
        input[type="text"], input[type="password"], input[type="email"], input[type="date"], select, textarea { width: 100%; background: var(--bg-base); color: var(--text-main); border: 1px solid var(--card-border); padding: 12px 15px 12px 42px; border-radius: 12px; font-size: 0.95rem; font-family: 'Inter', sans-serif; outline: none; transition: all 0.3s; }
        input[type="password"] { padding-right: 45px; }
        textarea { padding: 12px 15px 12px 42px; min-height: 100px; resize: vertical; }
        select { appearance: none; cursor: pointer; }
        select option { background: var(--bg-surface); color: var(--text-main); }
        
        ::-webkit-calendar-picker-indicator { filter: invert(0); cursor: pointer; opacity: 0.6; }
        body.dark-mode ::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.8; }

        .input-wrap i.icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; pointer-events: none; transition: color 0.3s, transform 0.3s; }
        .input-wrap textarea ~ i.icon { top: 15px; transform: none; }
        .input-wrap i.toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; cursor: pointer; pointer-events: auto; transition: color 0.3s; }
        .input-wrap i.toggle-password:hover { color: var(--primary); }
        
        input:focus, select:focus, textarea:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px var(--glow-color); }
        input:focus ~ i.icon, select:focus ~ i.icon, textarea:focus ~ i.icon { color: var(--primary); transform: translateY(-50%) scale(1.1); }
        .input-wrap textarea:focus ~ i.icon { transform: scale(1.1); }
        input::placeholder, textarea::placeholder { color: #94a3b8; font-weight: 400; }
        body.dark-mode input::placeholder, body.dark-mode textarea::placeholder { color: #64748b; }

        .strength-bar { height: 4px; background: var(--card-border); border-radius: 4px; margin-top: 10px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 4px; width: 0%; transition: width 0.3s, background 0.3s; }
        .btn-submit { width: 100%; border: none; padding: 14px; font-size: 1.05rem; font-weight: 700; border-radius: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; box-shadow: 0 10px 20px rgba(0,0,0,0.2); transition: all 0.3s; margin-top: 1rem; }
        .btn-submit:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 30px var(--glow-color); }
        .divider { border: none; border-top: 1px solid var(--card-border); margin: 2rem 0; }
        .footer-links { text-align: center; font-size: 0.95rem; color: var(--text-muted); font-weight: 500; }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .footer-links a:hover { color: var(--secondary); }
    </style>
</head>
<body>

    <nav class="navbar" role="navigation">
        <a href="../main/index.html" class="logo">
            <i class="fa-solid fa-paper-plane"></i>
            <span class="brand-text"><span class="trip">Trip</span><span class="mate">Mate</span></span>
        </a>
        <ul class="nav-links">
            <li><a href="../main/index.html">Home</a></li>
            <li><a href="contributor_login.php" class="nav-btn">Log In</a></li>
        </ul>
        <button class="theme-toggle" id="themeToggle" aria-label="Switch mode">
            <i class="fas fa-moon"></i>
        </button>
    </nav>

    <div class="auth-card">
        <h1>Create Contributor Profile</h1>
        <p class="subtitle">Join our community and share your travel experiences with the world.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" style="margin-top: 2px;"></i><span><?= $error ?></span></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check" style="margin-top: 2px;"></i><span><?= $success ?></span></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <h2 class="section-title"><i class="fa-solid fa-user-shield"></i> Account Details</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <div class="input-wrap">
                        <input type="text" id="name" name="name" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        <i class="fa-solid fa-user icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="username">Username *</label>
                    <div class="input-wrap">
                        <input type="text" id="username" name="username" placeholder="e.g. john_doe" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <i class="fa-solid fa-at icon"></i>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <div class="input-wrap">
                    <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <i class="fa-solid fa-envelope icon"></i>
                </div>
            </div>

            <h2 class="section-title"><i class="fa-solid fa-address-card"></i> Personal Details</h2>
            <div class="form-grid-3">
                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <div class="input-wrap">
                        <input type="text" id="mobile" name="mobile" placeholder="+1..." value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
                        <i class="fa-solid fa-phone icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <div class="input-wrap">
                        <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
                        <i class="fa-solid fa-calendar icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <div class="input-wrap">
                        <select id="gender" name="gender">
                            <option value="Prefer not to say" <?= (($_POST['gender'] ?? '') == 'Prefer not to say') ? 'selected' : '' ?>>Prefer not to say</option>
                            <option value="Male" <?= (($_POST['gender'] ?? '') == 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (($_POST['gender'] ?? '') == 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (($_POST['gender'] ?? '') == 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                        <i class="fa-solid fa-venus-mars icon"></i>
                    </div>
                </div>
            </div>

            <h2 class="section-title"><i class="fa-solid fa-map-location-dot"></i> Address Details</h2>
            <div class="form-group">
                <label for="address_line_1">Address Line 1</label>
                <div class="input-wrap">
                    <input type="text" id="address_line_1" name="address_line_1" placeholder="Street address, P.O. box, etc." value="<?= htmlspecialchars($_POST['address_line_1'] ?? '') ?>">
                    <i class="fa-solid fa-map-pin icon"></i>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="country">Country</label>
                    <div class="input-wrap">
                        <select id="country" name="country">
                            <option value="" disabled selected>Select a country</option>
                            <option value="India">India</option>
                            <option value="United States">United States</option>
                            <option value="Canada">Canada</option>
                            <option value="Australia">Australia</option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="" disabled>-------------------</option>
                            <option value="Afghanistan">Afghanistan</option><option value="Brazil">Brazil</option><option value="China">China</option><option value="France">France</option><option value="Germany">Germany</option><option value="Italy">Italy</option><option value="Japan">Japan</option><option value="Mexico">Mexico</option><option value="New Zealand">New Zealand</option><option value="South Africa">South Africa</option><option value="Spain">Spain</option><option value="Other">Other...</option>
                        </select>
                        <i class="fa-solid fa-earth-americas icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label id="state_label" for="state_region">State / Province</label>
                    <div class="input-wrap" id="state_container">
                        <input type="text" id="state_region" name="state_region" placeholder="State or region" value="<?= htmlspecialchars($_POST['state_region'] ?? '') ?>">
                        <i class="fa-solid fa-map icon"></i>
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="city">City</label>
                    <div class="input-wrap">
                        <input type="text" id="city" name="city" placeholder="City or town" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                        <i class="fa-solid fa-city icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label id="postal_label" for="postal_code">Postal Code</label>
                    <div class="input-wrap">
                        <input type="text" id="postal_code" name="postal_code" placeholder="Postal code" value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                        <i class="fa-solid fa-envelopes-bulk icon"></i>
                    </div>
                </div>
            </div>

            <h2 class="section-title"><i class="fa-solid fa-camera-retro"></i> Public Profile Details</h2>
            <div class="form-group">
                <label for="social_link">Social Media or Website Link</label>
                <div class="input-wrap">
                    <input type="text" id="social_link" name="social_link" placeholder="https://instagram.com/yourhandle" value="<?= htmlspecialchars($_POST['social_link'] ?? '') ?>">
                    <i class="fa-solid fa-link icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label for="bio">Short Bio / About Me</label>
                <div class="input-wrap">
                    <textarea id="bio" name="bio" placeholder="Tell us a little about your travel style..."><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
                    <i class="fa-solid fa-pen-nib icon"></i>
                </div>
            </div>

            <h2 class="section-title"><i class="fa-solid fa-lock"></i> Security</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
                        <i class="fa-solid fa-key icon"></i>
                        <i class="fa-solid fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <div class="input-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                        <i class="fa-solid fa-check-double icon"></i>
                        <i class="fa-solid fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Create Account <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <hr class="divider">
        <div class="footer-links">
            Already have an account? <a href="contributor_login.php">Login here</a>
        </div>
    </div>

    <script>
        // 1. Theme Logic
        const toggleBtn = document.getElementById('themeToggle');
        const icon = toggleBtn.querySelector('i');
        if (localStorage.getItem('tripmate-theme') === 'dark') {
            document.body.classList.add('dark-mode');
            icon.className = 'fas fa-sun';
        }
        toggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            localStorage.setItem('tripmate-theme', isDark ? 'dark' : 'light');
        });

        // 2. Password Show/Hide Toggle
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });

        // 3. Password Strength Meter
        const pw = document.getElementById('password');
        const fill = document.getElementById('strengthFill');
        pw.addEventListener('input', () => {
            const v = pw.value;
            let s = 0;
            if (v.length >= 8) s++;
            if (/[A-Z]/.test(v)) s++;
            if (/[0-9]/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            const colors = ['var(--danger)', '#f59e0b', 'var(--success)', 'var(--secondary)'];
            fill.style.width = (s * 25) + '%';
            fill.style.background = colors[s - 1] || 'transparent';
        });

        // 4. Dynamic Country -> States & Postal Code Logic
        const stateData = {
            "India": ["Andhra Pradesh","Arunachal Pradesh","Assam","Bihar","Chhattisgarh","Goa","Gujarat","Haryana","Himachal Pradesh","Jharkhand","Karnataka","Kerala","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram","Nagaland","Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura","Uttar Pradesh","Uttarakhand","West Bengal","Andaman and Nicobar Islands","Chandigarh","Dadra and Nagar Haveli","Daman and Diu","Delhi","Lakshadweep","Puducherry"],
            "United States": ["Alabama","Alaska","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","Florida","Georgia","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Carolina","North Dakota","Ohio","Oklahoma","Oregon","Pennsylvania","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virginia","Washington","West Virginia","Wisconsin","Wyoming"],
            "Canada": ["Alberta","British Columbia","Manitoba","New Brunswick","Newfoundland and Labrador","Nova Scotia","Ontario","Prince Edward Island","Quebec","Saskatchewan","Northwest Territories","Nunavut","Yukon"],
            "Australia": ["New South Wales","Victoria","Queensland","Western Australia","South Australia","Tasmania","Australian Capital Territory","Northern Territory"],
            "United Kingdom": ["England","Scotland","Wales","Northern Ireland"]
        };

        const countrySelect = document.getElementById('country');
        const stateContainer = document.getElementById('state_container');
        const stateLabel = document.getElementById('state_label');
        const postalInput = document.getElementById('postal_code');
        const postalLabel = document.getElementById('postal_label');

        countrySelect.addEventListener('change', function() {
            const country = this.value;
            
            if(country === "India") {
                postalLabel.innerText = "PIN Code";
                postalInput.placeholder = "e.g. 110001";
            } else if (country === "United States") {
                postalLabel.innerText = "ZIP Code";
                postalInput.placeholder = "e.g. 90210";
            } else if (country === "United Kingdom") {
                postalLabel.innerText = "Postcode";
                postalInput.placeholder = "e.g. SW1A 1AA";
            } else {
                postalLabel.innerText = "Postal Code";
                postalInput.placeholder = "Postal code";
            }

            if (stateData[country]) {
                stateLabel.innerText = "State";
                let selectHTML = `<select id="state_region" name="state_region" required>
                                    <option value="" disabled selected>Select State</option>`;
                stateData[country].forEach(state => {
                    selectHTML += `<option value="${state}">${state}</option>`;
                });
                selectHTML += `</select><i class="fa-solid fa-map icon"></i>`;
                stateContainer.innerHTML = selectHTML;
            } else {
                stateLabel.innerText = "State / Province / Region";
                stateContainer.innerHTML = `<input type="text" id="state_region" name="state_region" placeholder="State or region">
                                            <i class="fa-solid fa-map icon"></i>`;
            }
        });

        if(countrySelect.value) {
            countrySelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>