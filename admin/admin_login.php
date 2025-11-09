<?php // Start of PHP code
// Start the session for user authentication
session_start();

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
    /* Internal CSS styles start */

        :root { /* CSS variables for theme colors */
            --primary: #0056b3;
            --primary-dark: #003d82;
            --accent: #ff7e33;
            --error: #d32f2f;
            --text: #2d3748;
            --text-light: #4a5568;
            --border: #e2e8f0;
            --bg: #f8fafc;
        }
        
        body { /* Body styling */
            font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: grid;
            place-items: center;
            min-height: 100vh;
            margin: 0;
            line-height: 1.5;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100" opacity="0.03"><path d="M50 0 L100 50 L50 100 L0 50 Z" fill="%230056b3"/></svg>');
            background-size: 120px;
        }
        
        .login-container { /* Login box styling */
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before { /* Top gradient bar */
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .login-header { /* Header section styling */
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .login-header h1 { /* Title styling */
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .login-header p { /* Subtitle styling */
            color: var(--text-light);
            font-size: 0.9375rem;
        }
        
        .error-message { /* Error message styling */
            background-color: #fde8e8;
            color: var(--error);
            padding: 0.875rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
            text-align: center;
            border: 1px solid #f8c6c6;
        }
        
        .form-group { /* Form group spacing */
            margin-bottom: 1.5rem;
        }
        
        .form-group label { /* Label styling */
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text);
        }
        
        .form-control { /* Input field styling */
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus { /* Input focus effect */
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }
        
        .btn { /* Button styling */
            width: 100%;
            padding: 0.875rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:hover { /* Button hover effect */
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .security-footer { /* Footer styling */
            margin-top: 2rem;
            font-size: 0.8125rem;
            color: var(--text-light);
            text-align: center;
            border-top: 1px solid var(--border);
            padding-top: 1.5rem;
        }
        
        .travel-icon { /* Travel icon styling */
            display: inline-block;
            margin: 0 5px;
            vertical-align: middle;
        }
    </style> <!-- End of internal CSS -->
</head> <!-- End of head section -->
<body> <!-- Body starts -->
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
            <span class="travel-icon">✈</span> <!-- Airplane icon -->
            Authorized access only. All activities are monitored. <!-- Security message -->
            <span class="travel-icon">🌍</span> <!-- Globe icon -->
        </div>
    </div>
</body> <!-- End of body -->
</html> <!-- End of HTML document -->