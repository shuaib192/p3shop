<?php
// Start a session on every page. This lets us use the $_SESSION variable
// to remember if a user is logged in.
session_start();

// --- Database Connection Details ---
$db_host = 'localhost'; // The server where the database is
$db_user = 'root';      // The default username for XAMPP MySQL
$db_pass = '';          // The default password for XAMPP MySQL is empty
$db_name = 'p3shop'; // The name of the database we created

// --- Create the Connection ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// --- Check for Connection Errors ---
// If the connection fails for any reason, the script will stop and show an error.
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>