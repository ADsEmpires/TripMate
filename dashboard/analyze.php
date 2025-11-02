<?php
// Connect to MySQL
$host = "localhost";
$user = "root";
$password = "Avirup@1000";
$dbname = "tripmate";  // Change to your DB name

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get user registrations per day
$sql = "SELECT DATE(created_at) as reg_date, COUNT(*) as total_users 
        FROM users 
        GROUP BY DATE(created_at) 
        ORDER BY reg_date ASC";

$result = $conn->query($sql);

$dates = [];
$counts = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $dates[] = $row['reg_date'];
        $counts[] = $row['total_users'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Analyze User Growth</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>User Registration Growth (Day-by-Day)</h2>
    <canvas id="userChart" width="800" height="400"></canvas>

<script>
    const ctx = document.getElementById('userChart').getContext('2d');
    const userChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dates); ?>,
            datasets: [{
                label: 'Users Registered',
                data: <?= json_encode($counts); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'category',  // ✅ Force categorical labels
                    title: {
                        display: true,
                        text: 'Date'
                    },
                    ticks: {
                        autoSkip: false,  // ✅ Show all dates (disable skipping)
                        maxRotation: 90,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Users'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>

</body>
</html>
