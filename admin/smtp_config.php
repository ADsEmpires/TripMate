<?php
// SMTP Configuration File for XAMPP with Gmail
// IMPORTANT: Configure XAMPP's php.ini and sendmail.ini as shown in the guide

// Gmail SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com'); // SMTP server
define('SMTP_PORT', 587); // Port for TLS
define('SMTP_USERNAME', 'ranajitbarik071@gmail.com'); // Your Gmail
define('SMTP_PASSWORD', 'xfey imnj vqbk qprz'); // Your App Password
define('SMTP_FROM', 'ranajitbarik071@gmail.com'); // From email
define('SMTP_FROM_NAME', 'TripMate Admin'); // From name
define('SMTP_REPLY_TO', 'no-reply@tripmate.com'); // Reply-to address
define('SMTP_SECURE', 'tls'); // Encryption: tls for port 587
define('SMTP_AUTH', true); // Enable authentication
?>