<?php // Start of PHP code
// Start the session for user authentication
session_start();

// --- Breadcrumb handling (do not alter other functionality) ---
$current_page = ['name' => 'Admin Login', 'url' => basename($_SERVER['PHP_SELF'])];
$breadcrumb_prev = isset($_SESSION['admin_current_page']) ? $_SESSION['admin_current_page'] : ['name' => 'Dashboard', 'url' => 'admin_dasbord.php'];
$_SESSION['admin_current_page'] = $current_page;
// -------------------------------------------------------------

// Database configuration variables
$host = 'localhost'; // Hostname for MySQL server
$dbname = 'tripmate'; // Name of the database
$db_username = 'root'; // Username for database access
$db_password = '';     // Password for database access

// Check if the request method is POST (form submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get username/email from POST data
    $username = $_POST['username'] ?? '';
    // Get password from POST data
    $password = $_POST['password'] ?? '';

    try {
        // Create PDO connection to MySQL database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
        // Set PDO to throw exceptions on error
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare SQL query to find admin by name or email
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM admin WHERE name = ? OR email = ? LIMIT 1");
        // Execute query with username/email as parameters
        $stmt->execute([$username, $username]);
        // Fetch admin data from result
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // If admin found in database
        if ($admin) {
            // Password verification section
            // If passwords are hashed in database, use password_verify
            // If passwords are plain text, use direct comparison (not recommended)

            // For hashed passwords (recommended):
            if (password_verify($password, $admin['password'])) {
                // Set session variable for successful login
                $_SESSION['admin_logged_in'] = true;
                // Store admin id in session
                $_SESSION['admin_id'] = $admin['id'];
                // Store admin name in session
                $_SESSION['admin_name'] = $admin['name'];
                // Store admin email in session
                $_SESSION['admin_email'] = $admin['email'];
                // Redirect to admin_dasbord.php after login
                header('Location: admin_dasbord.php');
                // Stop further execution
                exit();
            } else {
                // Set error message for invalid password
                $error = "Invalid credentials. Please try again.";
            }

            // For plain text passwords (use only for testing):
            /*
            // Compare plain text password
            if ($password === $admin['password']) {
                // Set session variable for successful login
                $_SESSION['admin_logged_in'] = true;
                // Store admin id in session
                $_SESSION['admin_id'] = $admin['id'];
                // Store admin name in session
                $_SESSION['admin_name'] = $admin['name'];
                // Store admin email in session
                $_SESSION['admin_email'] = $admin['email'];
                // Redirect to admin.php after login
                header('Location: admin.php');
                // Stop further execution
                exit();
            } else {
                // Set error message for invalid password
                $error = "Invalid credentials. Please try again.";
            }
            */
        } else {
            // Set error message if admin not found
            $error = "Invalid credentials. Please try again.";
        }
    } catch (PDOException $e) {
        // Set error message for database connection failure
        $error = "Database connection failed. Please try again later.";
        // Log error in production environment
        // error_log($e->getMessage());
    }
}
?>

<!DOCTYPE html> <!-- Document type declaration -->
<html lang="en"> <!-- Start of HTML document, language set to English -->

<head> <!-- Head section starts -->
    <meta charset="UTF-8"> <!-- Character encoding set to UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive viewport settings -->
    <title>TripMate Admin Portal</title> <!-- Page title -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- Font Awesome icons -->
    <link rel="stylesheet" href="../main/style.css"> <!-- External stylesheet link -->
    <style>
        :root {
            --primary: #0078d4;
            --primary-dark: #0056b3;
            --accent: #ff6600;
            --white: #ffffff;
            --muted: #f1f5f9;
            --border: rgba(0, 120, 212, 0.08);
            --card-shadow: 0 6px 24px rgba(3, 37, 76, 0.08);
            --breadcrumb-bg: linear-gradient(90deg, rgba(0, 120, 212, 0.06), rgba(255, 102, 0, 0.03));
            --breadcrumb-accent: var(--primary);
        }

        /* Page background: image behind the login box, hazy/blurred */
        body {
            font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            line-height: 1.5;
            color: #10233b;
            position: relative;
            z-index: 1;
            background: var(--muted);
        }

        /* Background image layer (behind content) - hazy */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            /* use the provided image URL as the background */
            background-image: url('https://www.google.com/url?sa=i&url=https%3A%2F%2Funsplash.com%2Fs%2Fphotos%2Ftravel-tour&psig=AOvVaw2w6nWvq7m4WH96GIHNWbrc&ust=1761717228534000&source=images&cd=vfe&opi=89978449&ved=0CBUQjRxqFwoTCPCFy6maxpADFQAAAAAdAAAAABAb');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(6px) brightness(0.55);
            transform: scale(1.03);
            z-index: 0;
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 480px;
            padding: 2.25rem;
            /* make the box slightly translucent so the hazy background shows through */
            background: rgba(255, 255, 255, 0.92);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            z-index: 2;
            /* ensure it sits above the background layer */
            backdrop-filter: blur(2px);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .login-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0 0 0.25rem;
            color: var(--primary);
        }

        .login-header p {
            color: #556b7a;
            margin: 0;
            font-size: 0.95rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--breadcrumb-bg);
            padding: 8px 12px;
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .breadcrumb a {
            color: var(--breadcrumb-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .breadcrumb .sep {
            color: #94a3b8;
        }

        .breadcrumb .current {
            color: #324a5f;
            font-weight: 700;
            background: rgba(3, 37, 76, 0.03);
            padding: 4px 8px;
            border-radius: 6px;
        }

        .error-message {
            background-color: #fff1f0;
            color: #bf3b2b;
            padding: 0.9rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #ffd6d0;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #2b4053;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.98rem;
            transition: box-shadow .15s ease, border-color .15s ease;
            background: #ffffff;
            color: #133149;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 6px 18px rgba(0, 120, 212, 0.08);
        }

        .btn {
            width: 100%;
            padding: 0.85rem;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .08s ease, background .12s ease;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .back-btn {
            display: inline-block;
            background: var(--accent);
            color: var(--white);
            padding: 0.45rem 0.9rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background .12s ease;
        }

        .back-btn:hover {
            background: #e65c00;
        }

        .security-footer {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #556b7a;
            text-align: center;
            border-top: 1px solid rgba(15, 33, 55, 0.04);
            padding-top: 1rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .login-header h1 {
                font-size: 1.35rem;
            }
        }
    </style> 
</head> 

<body> 
    <nav class="navbar"> <!-- Navigation bar -->
        <div class="logo"> <!-- Logo section -->
            <i class="fas fa-compass"></i> <!-- Compass icon -->
            <span>TripMate</span> <!-- Brand name -->
        </div>
        <div class="nav-right"> <!-- Navigation right section -->
            <a href="../main/index.html" class="back-btn">Back to Home</a> <!-- Back to home link -->
        </div>
    </nav>

    <div class="login-container"> <!-- Login container -->
        <div class="login-header"> <!-- Header inside login box -->
            <h1>TripMate Admin Portal</h1> <!-- Main heading -->
            <p>Access the travel management system</p> <!-- Subheading -->
        </div>

        <!-- Breadcrumb (stylish, shows previous page) -->
        <div class="breadcrumb" aria-label="Breadcrumb">
            <a href="../main/index.html"><i class="fas fa-home"></i> Home</a>
            <?php if ($breadcrumb_prev && $breadcrumb_prev['name'] !== 'Dashboard' && $breadcrumb_prev['url'] !== $current_page['url']): ?>
                <span class="sep">›</span>
                <a href="<?= htmlspecialchars($breadcrumb_prev['url']) ?>"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($breadcrumb_prev['name']) ?></a>
            <?php endif; ?>
            <span class="sep">›</span>
            <span class="current"><i class="fas fa-sign-in-alt"></i> <?= htmlspecialchars($current_page['name']) ?></span>
        </div>

        <?php if (isset($error)): ?> <!-- Show error if exists -->
            <div class="error-message"><?= htmlspecialchars($error) ?></div> <!-- Error message -->
        <?php endif; ?>

        <form method="POST" autocomplete="off"> <!-- Login form -->
            <div class="form-group"> <!-- Username field group -->
                <label for="username">Username or Email</label> <!-- Username label -->
                <input type="text" id="username" name="username" class="form-control" required autofocus> <!-- Username input -->
            </div>

            <div class="form-group"> <!-- Password field group -->
                <label for="password">Password</label> <!-- Password label -->
                <input type="password" id="password" name="password" class="form-control" required> <!-- Password input -->
            </div>

            <button type="submit" class="btn">Sign In</button> <!-- Submit button -->
        </form>

        <div class="security-footer"> <!-- Security footer -->
            Authorized access only. All activities are monitored. <!-- Security message -->
            <!-- <span class="travel-icon">✈</span>  Airplane icon
            <span class="travel-icon">🌍</span> Globe icon -->
        </div>
    </div>
</body>

</html>