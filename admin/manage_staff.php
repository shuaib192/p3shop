<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_staff'])) {
        $u = mysqli_real_escape_string($conn, trim($_POST['username']));
        $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $r = $_POST['role'] === 'admin' ? 'admin' : 'staff';
        if ($conn->query("INSERT INTO users (username, password, role) VALUES ('$u', '$p', '$r')")) {
            $message = '<div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Staff account created!</div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="ri-error-warning-line"></i> Username may already exist.</div>';
        }
    }
    if (isset($_POST['delete_staff'])) {
        $did = (int)$_POST['delete_id'];
        if ($did != $_SESSION['user_id']) {
            $conn->query("DELETE FROM users WHERE id=$did");
            $message = '<div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Staff account removed.</div>';
        }
    }
}

$staff = [];
$sr = $conn->query("SELECT u.*,
    (SELECT COUNT(*) FROM sales WHERE user_id=u.id AND status='recorded') as total_sales,
    (SELECT SUM(total_price) FROM sales WHERE user_id=u.id AND status='recorded') as total_rev,
    (SELECT MAX(sale_date) FROM sales WHERE user_id=u.id) as last_sale
    FROM users u ORDER BY u.role ASC, u.username ASC");
while ($r = $sr->fetch_assoc()) $staff[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management — P3 Shop Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include 'includes/navbar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" onclick="openSidebar()"><i class="ri-menu-line"></i></button>
            <div><div class="page-title">Staff Management</div><div class="page-subtitle"><?= count($staff) ?> accounts</div></div>
        </div>
        <div class="topbar-right">
            <button onclick="document.getElementById('addStaffModal').classList.add('open')" class="btn btn-primary btn-sm"><i class="ri-user-add-line"></i> Add Staff</button>
        </div>
    </div>
    <div class="page-content">
        <?= $message ?>
        <div class="card">
            <div class="card-header"><h3><i class="ri-group-line"></i> All Staff Members</h3></div>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>User</th><th>Role</th><th>Total Sales</th><th>Revenue Generated</th><th>Last Active</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($staff as $s):
                        $init = strtoupper(substr($s['username'],0,1));
                    ?>
                    <tr>
                        <td data-label="User">
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <div style="width:36px;height:36px;background:<?= $s['role']==='admin'?'var(--primary-bg)':'var(--success-bg)' ?>;color:<?= $s['role']==='admin'?'var(--primary)':'var(--success)' ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;"><?= $init ?></div>
                                <strong><?= htmlspecialchars($s['username']) ?></strong>
                            </div>
                        </td>
                        <td data-label="Role"><span class="badge badge-<?= $s['role']==='admin'?'primary':'success' ?>"><?= ucfirst($s['role']) ?></span></td>
                        <td data-label="Sales" class="num fw-700"><?= number_format($s['total_sales']??0) ?></td>
                        <td data-label="Revenue" class="money">₦<?= number_format($s['total_rev']??0,0) ?></td>
                        <td data-label="Last Active"><?= $s['last_sale'] ? date('d M Y', strtotime($s['last_sale'])) : 'Never' ?></td>
                        <td data-label="Created"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td data-label="Actions">
                            <?php if ($s['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this staff member?');">
                                <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                <button type="submit" name="delete_staff" class="btn btn-danger btn-sm"><i class="ri-delete-bin-line"></i></button>
                            </form>
                            <?php else: ?>
                            <span class="badge badge-neutral">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Add Staff Modal -->
<div class="modal-overlay" id="addStaffModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="ri-user-add-line" style="color:var(--primary)"></i> Add Staff Member</h3>
            <button class="modal-close" onclick="document.getElementById('addStaffModal').classList.remove('open')"><i class="ri-close-line"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="e.g. john_doe">
                </div>
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="4" placeholder="Min 4 characters">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-control">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addStaffModal').classList.remove('open')">Cancel</button>
                <button type="submit" name="add_staff" class="btn btn-primary"><i class="ri-check-line"></i> Create Account</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>