<?php
require_once 'config/database.php';
$conn = getDBConnection();

// Get featured products
$products_query = "SELECT p.*, c.name as category_name FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.stock > 0 
                   ORDER BY p.created_at DESC 
                   LIMIT 8";
$products_result = $conn->query($products_query);

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grocery Store - Fresh Products Delivered</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cart-check"></i> Grocery Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'; ?>">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Welcome to Grocery Store</h1>
            <p class="lead mb-4">Fresh products delivered to your doorstep</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-light btn-lg">Get Started</a>
            <?php else: ?>
                <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'; ?>" class="btn btn-light btn-lg">Go to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Shop by Category</h2>
        <div class="row g-4">
            <?php while ($category = $categories_result->fetch_assoc()): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="category-card border">
                        <i class="bi bi-tag fs-1 text-primary"></i>
                        <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Featured Products -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Featured Products</h2>
        <div class="row g-4">
            <?php while ($product = $products_result->fetch_assoc()): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card product-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars($product['category_name']); ?></p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 text-primary mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                                <span class="badge bg-success">In Stock</span>
                            </div>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
                                <form method="POST" action="user/add_to_cart.php" class="mt-3">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2024 Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>

