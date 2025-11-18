<?php
require_once '../config/auth.php';
requireAdmin();

$conn = getDBConnection();

$success = '';
$error = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Category deleted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to delete category.';
    }
    $stmt->close();
    header('Location: categories.php');
    exit();
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id = intval($_POST['id'] ?? 0);
    
    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        if ($id > 0) {
            // Update
            $update_query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $name, $description, $id);
        } else {
            // Insert
            $insert_query = "INSERT INTO categories (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ss", $name, $description);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = $id > 0 ? 'Category updated successfully!' : 'Category added successfully!';
            header('Location: categories.php');
            exit();
        } else {
            $error = 'Failed to save category.';
        }
        $stmt->close();
    }
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get category to edit
$edit_category = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $cat_query = "SELECT * FROM categories WHERE id = ?";
    $stmt = $conn->prepare($cat_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_category = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get all categories
$categories_query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     GROUP BY c.id 
                     ORDER BY c.name";
$categories_result = $conn->query($categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-categories.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-cart-check"></i> Grocery Store - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4">Manage Categories</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><?php echo $edit_category ? 'Edit' : 'Add'; ?> Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="id" value="<?php echo $edit_category['id'] ?? 0; ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><?php echo $edit_category ? 'Update' : 'Add'; ?> Category</button>
                            <?php if ($edit_category): ?>
                                <a href="categories.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($categories_result->num_rows > 0): ?>
                                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $category['id']; ?></td>
                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                <td><?php echo htmlspecialchars($category['description'] ?? 'N/A'); ?></td>
                                                <td><?php echo $category['product_count']; ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <a href="?delete=<?php echo $category['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this category?')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No categories found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>

