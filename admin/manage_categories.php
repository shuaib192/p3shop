<?php
require_once '../includes/db.php';

// Security check for admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

// Handle additions
if (isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    if (!empty($name)) {
        if ($conn->query("INSERT INTO categories (name) VALUES ('$name')")) {
            $message = "<div class='alert alert-success'>Category added!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding category.</div>";
        }
    }
}

// Handle deletions
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Update products to Uncategorized (0)
    $conn->query("UPDATE products SET category_id = 0 WHERE category_id = $id");
    if ($conn->query("DELETE FROM categories WHERE id = $id")) {
        $message = "<div class='alert alert-success'>Category deleted!</div>";
    }
}

$categories = array();
$result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Categories</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
    <div class="container">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header"><h2>Add New Category</h2></div>
            <form action="manage_categories.php" method="POST">
                <div class="form-group"><label for="category_name">Category Name</label><input type="text" id="category_name" name="category_name" required></div>
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            </form>
        </div>
        <div class="card">
            <div class="card-header"><h2>Existing Categories</h2></div>
            <div class="table-wrapper">
                <table class="content-table">
                    <thead><tr><th>Name</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td>
                                    <a href="manage_categories.php?delete=<?php echo $cat['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this category? Products will be move to Uncategorized.')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
