<?php
require_once '../config/auth.php';
requireLogin();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = getUserId();
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($product_id > 0 && $quantity > 0) {
        $conn = getDBConnection();
        
        // Check if product exists and has stock
        $product_query = "SELECT stock, price FROM products WHERE id = ?";
        $stmt = $conn->prepare($product_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($product && $product['stock'] >= $quantity) {
            // Check if item already in cart
            $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($existing) {
                // Update quantity
                $new_quantity = $existing['quantity'] + $quantity;
                if ($new_quantity <= $product['stock']) {
                    $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ii", $new_quantity, $existing['id']);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                // Insert new cart item
                $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $stmt->execute();
                $stmt->close();
            }
            
            $_SESSION['success'] = 'Product added to cart!';
        } else {
            $_SESSION['error'] = 'Insufficient stock or product not found.';
        }
        
        $conn->close();
    }
}

header('Location: dashboard.php');
exit();
?>

