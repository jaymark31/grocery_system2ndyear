<?php
require_once __DIR__ . '/config/database.php';

$conn = getDBConnection();

$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Grocery Store</title>
    <link rel="stylesheet" href="/css/products-grid.css">
</head>
<body>
    <header class="site-header">
        <h1>Products</h1>
    </header>

    <main class="container">
        <?php if (empty($products)): ?>
            <p class="empty">No products found.</p>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($products as $product): 
                    $imgPath = $product['image'] ?? '';
                    // Resolve relative path to be used in src attribute. If empty or file missing, use placeholder
                    $imgSrc = '';
                    if (!empty($imgPath)) {
                        // If stored path is already absolute (starts with http), use it directly
                        if (preg_match('#^https?://#i', $imgPath)) {
                            $imgSrc = $imgPath;
                        } else {
                            // Normalize leading slash
                            $candidate = preg_replace('#^/+#', '', $imgPath);
                            if (file_exists(__DIR__ . '/' . $candidate)) {
                                $imgSrc = '/' . $candidate;
                            }
                        }
                    }
                    if (empty($imgSrc)) {
                        $imgSrc = 'https://via.placeholder.com/420x280?text=No+Image';
                    }
                ?>
                    <article class="card">
                        <div class="card-media">
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="card-desc"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            <div class="card-footer">
                                <div class="price">$<?php echo number_format($product['price'], 2); ?></div>
                                <form method="POST" action="user/add_to_cart.php" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
