<?php
// We must start the session to be able to access and destroy it.
session_start();

// Unset all of the session variables.
$_SESSION = array();

// Finally, destroy the session.
session_destroy();

// Redirect to the login page after logging out.
header("location: login.php");
exit;
?>