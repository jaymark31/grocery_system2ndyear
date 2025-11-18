<?php
require_once '../config/auth.php';
requireLogin();

$conn = getDBConnection();
$user_id = getUserId();

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get cart count
$cart_count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($cart_count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get products
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? '';

$products_query = "SELECT p.*, c.name as category_name FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.stock > 0";
$params = [];
$types = "";

if ($search) {
    $products_query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category_id) {
    $products_query .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$products_query .= " ORDER BY p.name";

$stmt = $conn->prepare($products_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();
$stmt->close();

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Grocery Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../user_css/dashboard.css">
    <style>
        .product-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card .product-img img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 6px;
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-cart-check"></i> Grocery Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart"></i> Cart 
                            <?php if ($cart_count > 0): ?>
                                <span class="badge bg-danger"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php 
                            $categories_result->data_seek(0);
                            while ($cat = $categories_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
<!-- Products Grid -->
<div class="row products-grid">
    <?php if ($products_result->num_rows > 0): ?>
        <?php while ($product = $products_result->fetch_assoc()): ?>
            <div class="col-md-3 col-sm-6">
                <div class="card product-card shadow-sm">
                    <div class="card-body">
                        <div class="product-img">
                            <?php 
                                $imgSrc = !empty($product['image']) ? '../' . $product['image'] : 'https://via.placeholder.com/420x280?text=No+Image';
                            ?>
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="h5 text-primary mb-0">₱<?php echo number_format($product['price'], 2); ?></span>
                            <span class="badge bg-<?php echo $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                Stock: <?php echo $product['stock']; ?>
                            </span>
                        </div>
                        <form method="POST" action="add_to_cart.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="input-group mb-2">
                                <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cart-plus"></i> Add
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">No products found.</div>
        </div>
    <?php endif; ?>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
