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

// Get cart items
$cart_query = "SELECT c.*, p.name, p.price, p.stock 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();
$stmt->close();

$total = 0;
$cart_count = 0;

// Calculate total and validate stock
$valid_items = [];
while ($item = $cart_items->fetch_assoc()) {
    if ($item['stock'] >= $item['quantity']) {
        $subtotal = $item['price'] * $item['quantity'];
        $total += $subtotal;
        $valid_items[] = $item;
        $cart_count += $item['quantity'];
    }
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cart_count > 0) {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    
    if (empty($shipping_address)) {
        $_SESSION['error'] = 'Please provide a shipping address.';
    } else {
        // Create order
        $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("ids", $user_id, $total, $shipping_address);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Create order items and update stock
        foreach ($valid_items as $item) {
            // Insert order item
            $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($order_item_query);
            $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
            $stmt->close();
            
            // Update product stock
            $update_stock_query = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt = $conn->prepare($update_stock_query);
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Clear cart
        $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($clear_cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success'] = 'Order placed successfully! Order ID: #' . $order_id;
        header('Location: orders.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Grocery Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../user_css/checkout.css">
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
        <h2 class="mb-4">Checkout</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if ($cart_count > 0): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
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
                                    <?php foreach ($valid_items as $item): 
                                        $subtotal = $item['price'] * $item['quantity'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Total:</th>
                                        <th>$<?php echo number_format($total, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="shipping_address" class="form-label">Shipping Address *</label>
                                    <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-check-circle"></i> Place Order
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Total</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span>Free</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>$<?php echo number_format($total, 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <h4>Your cart is empty!</h4>
                <p>Add items to your cart before checkout.</p>
                <a href="dashboard.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>

