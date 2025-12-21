<?php
// This is the most important part. It forces PHP to show the real error.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnostic Test Page</h1>";
echo "<p>This test will find the error.</p>";
echo "<hr>";

// Now, we will include the broken file to see what error it produces.
echo "<p>Attempting to load 'daily_summary.php'...</p>";

require_once 'daily_summary.php';

echo "<hr>";
echo "<p style='color:green;'><b>If you see this message, the file loaded without a fatal error, which is unexpected.</b></p>";
?>