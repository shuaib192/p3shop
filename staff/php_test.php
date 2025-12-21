<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Version and Feature Test</h1>";
echo "<hr>";

// Test 1: PHP Version
echo "<h2>Test 1: PHP Version</h2>";
echo "<p>Your server is running PHP version: <strong>" . phpversion() . "</strong></p>";
if (version_compare(phpversion(), '7.1', '<')) {
    echo "<p style='color:red;'><b>WARNING:</b> Your PHP version is very old. This is likely the cause of the problems. The code requires PHP 7.1 or newer.</p>";
} else {
    echo "<p style='color:green;'><b>OK:</b> Your PHP version is modern enough.</p>";
}
echo "<hr>";

// Test 2: Database Connection
echo "<h2>Test 2: Database Connection</h2>";
require_once 'includes/db.php';
if ($conn->connect_error) {
    echo "<p style='color:red;'><b>ERROR:</b> Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green;'><b>OK:</b> Database connection was successful.</p>";
}
echo "<hr>";

// Test 3: Prepared Statements (the most likely point of failure)
echo "<h2>Test 3: Prepared Statement Execution</h2>";
$test_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
if ($test_stmt === false) {
    echo "<p style='color:red;'><b>FATAL ERROR:</b> The function `\$conn->prepare()` failed. This is the main problem. Your MySQLi extension might not be configured correctly in XAMPP.</p>";
} else {
    echo "<p style='color:green;'><b>OK:</b> The function `\$conn->prepare()` is working correctly.</p>";
    $test_stmt->close();
}
echo "<hr>";

echo "<h2>Test Complete</h2>";
?>