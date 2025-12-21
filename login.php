<?php
// Include our database connection file.
require_once 'includes/db.php';

$error_message = '';

// If user is already logged in, redirect them away from this page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if the form was submitted.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Find the user in the database.
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Verify the password against the stored hash.
        if (password_verify($password, $user['password'])) {
            // Password is correct. Store user info in the session.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect to the main index file, which will send them to the correct dashboard.
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Incorrect password. Please try again.";
        }
    } else {
        $error_message = "Username not found.";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login | P3 Shop Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="ri-store-2-fill" style="font-size: 3rem; color: var(--primary);"></i>
            <h2 style="margin-top: 1rem;">P3 Shop <span>Pro</span></h2>
            <p style="color: var(--text-muted);">Please enter your credentials to continue</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username"><i class="ri-user-line"></i> Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password"><i class="ri-lock-line"></i> Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">
                Login to Dashboard <i class="ri-arrow-right-line"></i>
            </button>
        </form>
    </div>
</body>
</html>