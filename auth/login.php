<?php
session_start();
include '../database/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Set PHP session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        // Return JSON to frontend
        echo json_encode([
            'status' => 'success',
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'redirect' => $_POST['redirect'] ?? ''
        ]);
        exit;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password'
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="login-container">
        <form id="loginForm" method="POST" action="">
            <h2>User Login</h2>
            <div class="form-group">
                <label for="email_field">Email:</label>
                <input type="email" id="email_field" name="email" required>
            </div>
            <div class="form-group">
                <label for="password_field">Password:</label>
                <input type="password" id="password_field" name="password" required>
            </div>
            <button type="submit">Login</button>
            <div class="error-message" id="errorMessage"></div>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const messageDiv = document.getElementById('errorMessage');

            // If a user is already active on this device, block login attempts until logout
            const activeId = localStorage.getItem('tripmate_active_user_id');
            if (activeId) {
                messageDiv.textContent = 'A user is already logged in on this device. Please logout before attempting to log in with a different account.';
                messageDiv.style.color = 'red';
                return;
            }

            try {
                const formData = new FormData(this);
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                if (data.status === 'success') {
                    // Persist user data across tab (session) and device (localStorage)
                    sessionStorage.setItem('user_id', data.user_id);
                    sessionStorage.setItem('user_name', data.user_name);
                    localStorage.setItem('tripmate_active_user_id', data.user_id);
                    localStorage.setItem('tripmate_active_user_name', data.user_name);

                    // If opened as popup and opener exists, notify opener and close
                    if (window.opener && !window.opener.closed) {
                        try {
                            window.opener.postMessage({
                                type: 'user-login',
                                user_id: data.user_id,
                                user_name: data.user_name
                            }, window.location.origin || '*');
                        } catch (e) {
                            // ignore cross-origin issues
                        }
                        window.close();
                        return;
                    }

                    if (data.redirect && data.redirect.length) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = '../user/user_dashboard.php';
                    }
                } else {
                    messageDiv.textContent = data.message || 'Login failed';
                    messageDiv.style.color = 'red';
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.textContent = 'Error submitting form. Please try again.';
                messageDiv.style.color = 'red';
            }
        });

        // If already logged in (device-wide), optionally redirect or mark UI
        if (localStorage.getItem('tripmate_active_user_id')) {
            // keep on page, but mark
            document.body.classList.add('user-logged-in');
        }
    </script>
</body>
</html>