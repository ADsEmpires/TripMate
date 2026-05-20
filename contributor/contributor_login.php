<?php
session_start();

if (isset($_SESSION['contributor_id'])) {
    header('Location: contributor_dashboard.php');
    exit();
}

include '../database/dbconfig.php';

$error = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error = 'Please enter both your Email/Username and Password.';
    } else {
        $sql = "SELECT id, name, username, password FROM contributors WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $login_id, $login_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['contributor_id']   = $user['id'];
                $_SESSION['contributor_name'] = $user['name'];
                $_SESSION['contributor_username'] = $user['username'];
                
                header('Location: contributor_dashboard.php');
                exit();
            } else {
                $error = 'Invalid password. Please try again.';
            }
        } else {
            $error = 'No account found with that email or username.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contributor Login · TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* TripMate Global Design System */
        :root {
            --bg-base: #f8fafc; --bg-surface: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --primary: #4f46e5; --secondary: #06b6d4; --nav-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(79, 70, 229, 0.15); --shadow-color: rgba(15, 23, 42, 0.08); --glow-color: rgba(6, 182, 212, 0.4);
            --danger: #ef4444; --danger-bg: rgba(239, 68, 68, 0.1);
        }
        body.dark-mode {
            --bg-base: #09090b; --bg-surface: #18181b; --text-main: #f8fafc; --text-muted: #cbd5e1;
            --primary: #818cf8; --secondary: #22d3ee; --nav-bg: rgba(24, 24, 27, 0.75);
            --card-border: rgba(255, 255, 255, 0.1); --shadow-color: rgba(0, 0, 0, 0.6); --glow-color: rgba(34, 211, 238, 0.3);
            --danger: #f87171; --danger-bg: rgba(248, 113, 113, 0.1);
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
        .auth-card { width: 100%; max-width: 420px; background: var(--bg-surface); padding: 40px 35px; border-radius: 24px; box-shadow: 0 20px 40px var(--shadow-color); border: 1px solid var(--card-border); position: relative; overflow: hidden; animation: fadeUp 0.5s ease both; z-index: 1; }
        .auth-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: linear-gradient(90deg, var(--primary), var(--secondary)); }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        h1 { font-size: 1.8rem; font-weight: 800; text-align: center; margin-bottom: 0.3rem; letter-spacing: -0.5px; }
        .subtitle { color: var(--text-muted); font-size: 0.95rem; text-align: center; margin-bottom: 2rem; }
        
        .alert { padding: 12px 15px; border-radius: 12px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 10px; font-weight: 500; }
        .alert-error { background: var(--danger-bg); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); }

        .form-group { margin-bottom: 1.4rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; }
        .input-wrap { position: relative; }
        .input-wrap i.icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; pointer-events: none; transition: color 0.3s; }
        
        input[type="text"], input[type="password"] { width: 100%; background: var(--bg-base); color: var(--text-main); border: 1px solid var(--card-border); padding: 12px 15px 12px 42px; border-radius: 12px; font-size: 0.95rem; font-family: 'Inter', sans-serif; outline: none; transition: all 0.3s; }
        input[type="password"] { padding-right: 45px; }
        
        input:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px var(--glow-color); }
        input:focus ~ i.icon { color: var(--primary); }
        input::placeholder { color: #94a3b8; }
        
        .input-wrap i.toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; cursor: pointer; pointer-events: auto; transition: color 0.3s; }
        .input-wrap i.toggle-password:hover { color: var(--primary); }

        .btn-submit { width: 100%; border: none; padding: 14px; font-size: 1rem; font-weight: 700; border-radius: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; box-shadow: 0 10px 20px rgba(0,0,0,0.2); transition: all 0.3s cubic-bezier(0.25,1,0.5,1); margin-top: 1rem; }
        .btn-submit:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 30px var(--glow-color); }
        .divider { border: none; border-top: 1px solid var(--card-border); margin: 1.8rem 0; }
        .footer-links { text-align: center; font-size: 0.9rem; color: var(--text-muted); font-weight: 500; }
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
            <li><a href="contributor_register.php" class="nav-btn">Register</a></li>
        </ul>
        <button class="theme-toggle" id="themeToggle" aria-label="Switch mode">
            <i class="fas fa-moon"></i>
        </button>
    </nav>

    <div class="auth-card">
        <h1>Welcome Back</h1>
        <p class="subtitle">Log in to your Contributor Dashboard</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation" style="margin-top: 2px;"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="login_id">Email or Username</label>
                <div class="input-wrap">
                    <input type="text" id="login_id" name="login_id" placeholder="Enter email or username" required
                        value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>">
                    <i class="fa-solid fa-user-astronaut icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fa-solid fa-lock icon"></i>
                    <i class="fa-solid fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Log In <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <hr class="divider">
        <div class="footer-links">
            Don't have an account? <a href="contributor_register.php">Register here</a>
        </div>
    </div>

    <script>
        // Theme Logic
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

        // Password Show/Hide Toggle
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
    </script>
</body>
</html>