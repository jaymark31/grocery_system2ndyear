<?php
require_once '../config/auth.php';
requireLogin();

$conn = getDBConnection();
$user_id = getUserId();

// Handle remove from cart
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = 'Item removed from cart.';
    header('Location: cart.php');
    exit();
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cart_id => $quantity) {
        $cart_id = intval($cart_id);
        $quantity = intval($quantity);
        
        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    $_SESSION['success'] = 'Cart updated.';
    header('Location: cart.php');
    exit();
}

// Get cart items
$cart_query = "SELECT c.*, p.name, p.price, p.stock, p.description 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();
$stmt->close();

$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Grocery Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../user_css/cart.css">
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
                        <a class="nav-link active" href="cart.php">
                            <i class="bi bi-cart"></i> Cart
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
        <h2 class="mb-4">Shopping Cart</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if ($cart_items->num_rows > 0): ?>
            <form method="POST" action="">
                <input type="hidden" name="update_cart" value="1">
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $cart_items->fetch_assoc()): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total += $subtotal;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <input type="number" name="quantities[<?php echo $item['id']; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock']; ?>" 
                                                   class="form-control" style="width: 80px;">
                                        </td>
                                        <td>$<?php echo number_format($subtotal, 2); ?></td>
                                        <td>
                                            <a href="?remove=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th>$<?php echo number_format($total, 2); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Update Cart</button>
                    <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                    <a href="dashboard.php" class="btn btn-secondary">Continue Shopping</a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <h4>Your cart is empty!</h4>
                <p>Start shopping to add items to your cart.</p>
                <a href="dashboard.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>

