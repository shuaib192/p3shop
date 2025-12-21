<?php
require_once '../includes/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

// === HANDLE ADD STAFF FORM ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($username) && !empty($password)) {
        // Check if username already exists
        $safe_username = mysqli_real_escape_string($conn, $username);
        $check_sql = "SELECT id FROM users WHERE username = '" . $safe_username . "'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows == 0) {
            // Username is available, hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $safe_hashed_password = mysqli_real_escape_string($conn, $hashed_password);

            // Insert new staff user into the database
            $insert_sql = "INSERT INTO users (username, password, role) VALUES ('" . $safe_username . "', '" . $safe_hashed_password . "', 'staff')";
            if ($conn->query($insert_sql)) {
                $message = "<div class='alert alert-success'>Staff account created successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error creating account.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Username already exists. Please choose another.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Please fill in both username and password.</div>";
    }
}

// === HANDLE DELETE STAFF ===
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    // You cannot delete the main admin account (assuming ID 1) or your own account
    if ($delete_id > 1 && $delete_id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = " . $delete_id . " AND role = 'staff'");
        // Redirect to clean the URL
        header('Location: manage_staff.php');
        exit;
    }
}

// === FETCH ALL STAFF MEMBERS ===
$staff_list = array();
$result = $conn->query("SELECT id, username, created_at FROM users WHERE role = 'staff' ORDER BY username ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $staff_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

    <div class="container">
        <?php echo $message; ?>

        <div class="card">
            <div class="card-header"><h2>Add New Staff Member</h2></div>
            <form action="manage_staff.php" method="POST">
                <div class="form-group">
                    <label for="username">Staff Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="add_staff" class="btn btn-primary">Create Account</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h2>Existing Staff Accounts</h2></div>
            <div class="table-wrapper">
                <table class="content-table">
                    <thead><tr><th>Username</th><th>Date Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($staff_list)): ?>
                            <tr><td colspan="3" style="text-align: center;">No staff accounts found.</td></tr>
                        <?php else: foreach ($staff_list as $staff): ?>
                            <tr>
                                <td data-label="Username"><?php echo htmlspecialchars($staff['username']); ?></td>
                                <td data-label="Date Created"><?php echo date("F d, Y", strtotime($staff['created_at'])); ?></td>
                                <td data-label="Actions">
                                    <a href="manage_staff.php?delete_id=<?php echo $staff['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this staff account? This cannot be undone.');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>