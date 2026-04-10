<?php
require_once 'includes/db.php';
$error_message = '';
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit();
        } else { $error_message = "Incorrect password."; }
    } else { $error_message = "Username not found."; }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — P3 Shop Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon"><i class="ri-store-2-fill"></i></div>
            <h1>P3 Shop <span>Pro</span></h1>
            <p>Retail Management System</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><i class="ri-error-warning-line"></i> <?= $error_message ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label"><i class="ri-user-line"></i> Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="ri-lock-line"></i> Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:1rem;">
                Login <i class="ri-arrow-right-line"></i>
            </button>
        </form>

        <div style="text-align:center; margin-top:1.5rem; font-size:0.78rem; color:var(--text-muted);">
            P3 Shop Pro &copy; <?= date('Y') ?> — Secure Access
        </div>
    </div>
</div>
</body>
</html>