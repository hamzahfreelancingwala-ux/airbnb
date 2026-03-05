<?php
/**
 * Database Configuration File - db.php
 * Connects to the MySQL database.
 */

// Your provided database credentials
$DB_HOST = 'localhost'; // Replace with your actual host if different
$DB_USER = 'rsoa_rsoa112_4';
$DB_PASS = '654321#';
$DB_NAME = 'rsoa_rsoa112_4';

// Attempt to establish a connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Stop execution and show the error if connection fails
    die("❌ Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4 for better emoji and character support
$conn->set_charset("utf8mb4");

// Function to safely close the connection (optional, good practice)
// function close_db_connection($conn) {
//     $conn->close();
// }
?>
