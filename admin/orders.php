<?php
require_once '../config/auth.php';
requireAdmin();

$conn = getDBConnection();

$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Order status updated successfully.';
    } else {
        $_SESSION['error'] = 'Failed to update order status.';
    }
    $stmt->close();
    header('Location: orders.php');
    exit();
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get order to view details
$view_order = null;
if (isset($_GET['view'])) {
    $order_id = intval($_GET['view']);
    $order_query = "SELECT o.*, u.full_name, u.email, u.phone 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $view_order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($view_order) {
        // Get order items
        $items_query = "SELECT oi.*, p.name FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?";
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order_items = $stmt->get_result();
        $stmt->close();
    }
}

// Get all orders
$orders_query = "SELECT o.*, u.full_name, u.email 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 ORDER BY o.created_at DESC";
$orders_result = $conn->query($orders_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-orders.css">
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
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">Orders</a>
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
        <h2 class="mb-4">Manage Orders</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($view_order): ?>
            <!-- Order Details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Order #<?php echo $view_order['id']; ?></h5>
                    <a href="orders.php" class="btn btn-sm btn-secondary">Back to Orders</a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($view_order['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($view_order['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($view_order['phone'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($view_order['created_at'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $view_order['status'] === 'delivered' ? 'success' : 
                                        ($view_order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($view_order['status']); ?>
                                </span>
                            </p>
                            <p><strong>Total:</strong> $<?php echo number_format($view_order['total_amount'], 2); ?></p>
                        </div>
                    </div>
                    
                    <h6>Shipping Address</h6>
                    <p><?php echo nl2br(htmlspecialchars($view_order['shipping_address'])); ?></p>
                    
                    <h6 class="mt-3">Order Items</h6>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $order_items->fetch_assoc()): 
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
                    
                    <form method="POST" action="" class="mt-3">
                        <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-select" name="status" required>
                                    <option value="pending" <?php echo $view_order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $view_order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $view_order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $view_order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $view_order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders_result->num_rows > 0): ?>
                                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($order['full_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                            </td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $order['status'] === 'delivered' ? 'success' : 
                                                        ($order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>

