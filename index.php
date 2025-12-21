<?php
// We include the db.php file to start the session.
require_once 'includes/db.php';

// Check if the session variable 'user_id' exists.
// This variable is only created when a user logs in successfully.
if (isset($_SESSION['user_id'])) {

    // If the user's role is 'admin', redirect them to the admin dashboard.
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/index.php");
        exit(); // Always call exit() after a header redirect.
    }
    // Otherwise (if the role is 'staff'), redirect them to the staff dashboard.
    else {
        header("Location: staff/index.php");
        exit();
    }

} else {
    // If 'user_id' doesn't exist, the user is not logged in.
    // Redirect them to the login page.
    header("Location: login.php");
    exit();
}
?>