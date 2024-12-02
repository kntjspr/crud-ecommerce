<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in as customer
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Check if it's a POST request and cart_items is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_items'])) {
    try {
        $pdo->beginTransaction();
        $success = true;
        $messages = [];

        foreach ($_POST['cart_items'] as $product_id => $quantity) {
            // Validate quantity
            $quantity = intval($quantity);
            if ($quantity < 0) {
                $messages[] = "Invalid quantity for product ID: $product_id";
                continue;
            }

            // Check product exists and has enough stock
            $stmt = $pdo->prepare("SELECT Product_ID, Stock FROM Product WHERE Product_ID = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                $messages[] = "Product not found: $product_id";
                continue;
            }

            if ($quantity > $product['Stock']) {
                $messages[] = "Not enough stock for product ID: $product_id. Available: {$product['Stock']}";
                $success = false;
                continue;
            }

            // If quantity is 0, remove from cart
            if ($quantity === 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                // Update cart quantity
                $_SESSION['cart'][$product_id] = $quantity;
            }
        }

        $pdo->commit();

        // Send response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'messages' => $messages,
            'cart' => isset($_SESSION['cart']) ? $_SESSION['cart'] : []
        ]);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'messages' => ["Error updating cart: " . $e->getMessage()]
        ]);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'messages' => ["Invalid request"]
    ]);
    exit();
} 