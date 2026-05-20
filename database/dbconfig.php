<?php

// Database connection details
// Use 127.0.0.1 to force TCP (avoids common localhost/socket issues in XAMPP setups)
$host = '127.0.0.1'; // Hostname only (no port)
$port = 3306; // Change if your MySQL uses a different port (e.g. 3307)
$dbname = 'tripmate';
$username = 'root';
$password = ''; // Use your actual MySQL password

// Backward-compat: if someone sets host like "localhost:3306", split it safely
if (strpos($host, ':') !== false) {
    [$h, $p] = array_pad(explode(':', $host, 2), 2, null);
    if (!empty($h)) $host = $h;
    if (is_numeric($p)) $port = (int)$p;
}

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    // IMPORTANT: don't echo/print JSON here; this file is included by both HTML and JSON endpoints.
    // Any output would corrupt API responses and break redirects/headers.
    die('Database connection failed: ' . $conn->connect_error);
}


// Set charset
$conn->set_charset("utf8mb4");



/*
// Database connection details
$host = 'localhost';
$dbname = 'tripmate';  // Changed from 'TripMate' 
$username = 'root';
$password = ''; // use your actual MySQL password

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
*/

/*

// Database connection details
$host = '127.0.0.1';      // Use IP instead of 'localhost' to avoid socket issues
$port = '3307';           // Custom port
$dbname = 'tripmate';     // Your database name
$username = 'root';       // Your MySQL username
$password = 'Ranajit@sql2005';           // Your MySQL password

// Create connection using MySQLi with port
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully on port 3307!";
*/
?>