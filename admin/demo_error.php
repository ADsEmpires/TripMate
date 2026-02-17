<?php include 'admin_header.php'; ?>
<?php if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit(); } ?>
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
            --info: #0891b2;
            --sidebar-bg: #0f172a;
            --card-bg: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.5;
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: var(--gray);
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .error-test-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 0.75rem;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .card h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card p {
            margin-bottom: 1.5rem;
            color: var(--gray);
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .btn-warning {
            background: var(--warning);
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-success:hover {
            background: #15803d;
        }
        
        .error-results {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--card-bg);
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
        }
        
        .error-results h2 {
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .log-entry {
            padding: 1rem;
            border-left: 4px solid var(--danger);
            background: #fef2f2;
            margin-bottom: 1rem;
            border-radius: 0 0.375rem 0.375rem 0;
        }
        
        .log-entry.success {
            border-left: 4px solid var(--success);
            background: #f0fdf4;
        }
        
        .log-entry.warning {
            border-left: 4px solid var(--warning);
            background: #fffbeb;
        }
        
        .log-time {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        .log-message {
            font-weight: 500;
        }
        
        .hidden {
            display: none;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem;
            background: var(--success);
            color: white;
            border-radius: 0.375rem;
            box-shadow: var(--shadow);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.error {
            background: var(--danger);
        }
        
        .notification.warning {
            background: var(--warning);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>TripMate Admin - Error Testing</h1>
            <p class="subtitle">Test your error handling system with various error scenarios</p>
        </header>
        
        <div class="error-test-section">
            <div class="card">
                <h2><i class="fas fa-database"></i> Database Errors</h2>
                <p>Test database connection issues, query failures, and table creation errors.</p>
                <button class="btn btn-danger" onclick="testDatabaseError()">Simulate Database Error</button>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-code"></i> PHP Errors</h2>
                <p>Test PHP syntax errors, warnings, notices, and fatal errors.</p>
                <button class="btn btn-warning" onclick="testPhpError()">Simulate PHP Error</button>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-network-wired"></i> Network Errors</h2>
                <p>Test API connection failures, timeout errors, and CORS issues.</p>
                <button class="btn" onclick="testNetworkError()">Simulate Network Error</button>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-file"></i> File System Errors</h2>
                <p>Test file upload failures, missing files, and permission errors.</p>
                <button class="btn" onclick="testFileError()">Simulate File Error</button>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-user"></i> Authentication Errors</h2>
                <p>Test login failures, session expiration, and permission issues.</p>
                <button class="btn" onclick="testAuthError()">Simulate Auth Error</button>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-bug"></i> Custom Error Logging</h2>
                <p>Test your custom error logging function with various error types.</p>
                <button class="btn btn-success" onclick="testCustomError()">Test Custom Error Logging</button>
            </div>
        </div>
        
        <div class="error-results">
            <h2>Error Log Results</h2>
            <div id="errorLogs">
                <div class="log-entry success">
                    <div class="log-time" id="currentTime">Current time will appear here</div>
                    <div class="log-message">No errors logged yet. Click the buttons above to test error scenarios.</div>
                </div>
            </div>
            <button class="btn" onclick="clearLogs()">Clear Logs</button>
            <button class="btn btn-success" onclick="testAll()">Test All Scenarios</button>
        </div>
    </div>
    
    <div class="notification" id="notification">
        <span id="notificationMessage">Notification message</span>
    </div>

    <script>
        // Display current time
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleString();
        }
        
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime();
        
        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const messageEl = document.getElementById('notificationMessage');
            
            messageEl.textContent = message;
            notification.className = 'notification ' + type;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        // Add log entry
        function addLogEntry(message, type = 'error') {
            const logContainer = document.getElementById('errorLogs');
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry ' + type;
            
            const timeEl = document.createElement('div');
            timeEl.className = 'log-time';
            timeEl.textContent = new Date().toLocaleString();
            
            const messageEl = document.createElement('div');
            messageEl.className = 'log-message';
            messageEl.textContent = message;
            
            logEntry.appendChild(timeEl);
            logEntry.appendChild(messageEl);
            logContainer.appendChild(logEntry);
            
            // Scroll to bottom
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        // Clear logs
        function clearLogs() {
            document.getElementById('errorLogs').innerHTML = '';
            addLogEntry('Logs cleared. Ready for new tests.', 'success');
            showNotification('Logs cleared successfully');
        }
        
        // Test functions
        function testDatabaseError() {
            addLogEntry('Simulating database connection error: Cannot connect to MySQL server on localhost:3306');
            addLogEntry('Query failed: SELECT * FROM non_existent_table. Error: Table tripmate.non_existent_table doesn\'t exist');
            addLogEntry('Database error: Lost connection to MySQL server during query');
            showNotification('Database error simulated', 'error');
        }
        
        function testPhpError() {
            addLogEntry('PHP Warning: Undefined variable undefined_variable in /var/www/html/admin/test.php on line 42');
            addLogEntry('PHP Notice: Trying to access array offset on value of type null in /var/www/html/admin/test.php on line 67');
            addLogEntry('PHP Fatal error: Uncaught Error: Call to undefined function undefined_function() in /var/www/html/admin/test.php:78');
            showNotification('PHP error simulated', 'warning');
        }
        
        function testNetworkError() {
            addLogEntry('Network Error: Failed to fetch API endpoint https://api.tripmate.com/destinations. Status: 500');
            addLogEntry('CORS Error: Blocked by CORS policy: No Access-Control-Allow-Origin header');
            addLogEntry('Timeout Error: Network request timed out after 30000ms');
            showNotification('Network error simulated');
        }
        
        function testFileError() {
            addLogEntry('File Error: Failed to open stream: Permission denied in /var/www/html/admin/upload.php on line 33');
            addLogEntry('Upload Error: File size exceeds maximum allowed limit of 5MB');
            addLogEntry('File Error: Cannot modify header information - headers already sent');
            showNotification('File error simulated');
        }
        
        function testAuthError() {
            addLogEntry('Authentication Error: Invalid credentials for user admin@example.com');
            addLogEntry('Session Error: User session expired. Please log in again');
            addLogEntry('Permission Error: User does not have sufficient privileges to access this resource');
            showNotification('Authentication error simulated');
        }
        
        function testCustomError() {
            addLogEntry('Custom error logged: User with ID 123 attempted to access unauthorized resource');
            addLogEntry('Custom error logged: Invalid destination data submitted - missing required fields');
            addLogEntry('Custom error logged: Failed to generate PDF report - template file missing');
            showNotification('Custom error logging tested', 'success');
        }
        
        function testAll() {
            // Clear existing logs
            clearLogs();
            
            // Add a small delay between each test
            setTimeout(testDatabaseError, 100);
            setTimeout(testPhpError, 300);
            setTimeout(testNetworkError, 500);
            setTimeout(testFileError, 700);
            setTimeout(testAuthError, 900);
            setTimeout(testCustomError, 1100);
            
            showNotification('All error scenarios tested', 'success');
        }
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('Error testing page loaded successfully. Click buttons to test error scenarios.');
        });
    </script>
<?php include 'admin_footer.php'; ?>