<?php
session_start();

$host = 'localhost';
$dbname = 'tripmate';
$db_username = 'root';
$db_password = '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];

                if (file_exists('ip_tracking.php')) {
                    include_once 'ip_tracking.php';
                    trackUserIP($admin['id'], $pdo);
                }

                header("Location: admin_dasbord.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } catch (Exception $e) {
            $error = "Server error. Try again.";
        }
    } else {
        $error = "Please fill both fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TripMate</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #ecfeff 0%, #fef3c7 100%);
            color: #2d3748;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 80px;
            overflow-x: hidden;
        }

        /* === ORIGINAL TOP NAVBAR === */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            z-index: 1000;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 1.5rem;
            color: #14b8a6;
        }
        .logo i {
            color: #f87171;
            font-size: 1.8rem;
        }
        .back-btn {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .back-btn:hover {
            background: rgba(20, 184, 166, 0.1);
            color: #14b8a6;
        }

        /* === FLOATING 3D CARD (UNIQUE EFFECT) === */
        .login-container {
            margin: 2rem auto;
            width: 100%;
            max-width: 380px;
            perspective: 1000px;
        }
        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(20, 184, 166, 0.08);
            transition: all 0.4s ease;
            transform-style: preserve-3d;
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #14b8a6, #f87171);
            border-radius: 16px 16px 0 0;
        }
        .login-card:hover {
            transform: translateY(-8px) rotateX(4deg);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.12),
                0 0 0 1px rgba(20, 184, 166, 0.15);
        }

        h1 {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #1a202c;
            font-weight: 700;
        }
        p {
            text-align: center;
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .error {
            background: #fee2e2;
            color: #c53030;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid #feb2b2;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
            font-size: 0.95rem;
        }
        input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: #fbfcfd;
        }
        input:focus {
            outline: none;
            border-color: #14b8a6;
            background: white;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15);
        }

        /* === UNIQUE PULSE BUTTON === */
        button {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        button:active::before {
            width: 300px;
            height: 300px;
        }
        button:hover {
            background: linear-gradient(135deg, #0d9488, #0d7a6e);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(20, 184, 166, 0.3);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .navbar { padding: 1rem; }
            .login-container { margin: 1rem; }
            .login-card { padding: 2rem; }
        }
    </style>
</head>
<body>

    <!-- ORIGINAL NAVBAR -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-compass"></i>
            <span>TripMate</span>
        </div>
        <a href="../main/index.html" class="back-btn">Back to Home</a>
    </nav>

    <!-- FLOATING CARD WITH UNIQUE EFFECT -->
    <div class="login-container">
        <div class="login-card">
            <h1>Admin Portal</h1>
            <p>Secure access to TripMate dashboard</p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>Username or Email</label>
                <input type="text" name="username" required autofocus>

                <label>Password</label>
                <input type="password" name="password" required>

                <button type="submit">Secure Login</button>
            </form>
        </div>
    </div>

</body>
</html>