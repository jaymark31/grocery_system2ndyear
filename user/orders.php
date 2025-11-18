<?php
require_once '../config/auth.php';
requireLogin();

$conn = getDBConnection();
$user_id = getUserId();

// Get orders
$orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Grocery Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../user_css/orders.css">
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
                        <a class="nav-link" href="cart.php">Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">My Orders</a>
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
        <h2 class="mb-4">My Orders</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if ($orders_result->num_rows > 0): ?>
            <div class="row">
                <?php while ($order = $orders_result->fetch_assoc()): 
                    // Get order items
                    $items_query = "SELECT oi.*, p.name FROM order_items oi 
                                   JOIN products p ON oi.product_id = p.id 
                                   WHERE oi.order_id = ?";
                    $stmt = $conn->prepare($items_query);
                    $stmt->bind_param("i", $order['id']);
                    $stmt->execute();
                    $items_result = $stmt->get_result();
                    $stmt->close();
                ?>
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Order #<?php echo $order['id']; ?></strong>
                                    <span class="badge bg-<?php 
                                        echo $order['status'] === 'delivered' ? 'success' : 
                                            ($order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                    ?> ms-2">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div>
                                    <strong>Total: $<?php echo number_format($order['total_amount'], 2); ?></strong>
                                </div>
                            </div>
                            <div class="card-body">
                                <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                
                                <h6 class="mt-3">Order Items:</h6>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($item = $items_result->fetch_assoc()): 
                                            $subtotal = $item['price'] * $item['quantity'];
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                <td>$<?php echo number_format($subtotal, 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <h4>No orders yet!</h4>
                <p>Start shopping to place your first order.</p>
                <a href="dashboard.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>

