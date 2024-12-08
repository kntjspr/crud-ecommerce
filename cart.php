<?php
session_start();
require_once 'config/database.php';

// Handle add to cart
if (isset($_POST['add_to_cart']) && isset($_SESSION['customer_id'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Cart (Customer_ID, Product_ID, Quantity)
            VALUES (:customer_id, :product_id, :quantity)
            ON DUPLICATE KEY UPDATE Quantity = Quantity + VALUES(Quantity)
        ");
        
        $stmt->execute([
            ':customer_id' => $_SESSION['customer_id'],
            ':product_id' => $_POST['product_id'],
            ':quantity' => $_POST['quantity']
        ]);

        $_SESSION['success_message'] = "Product added to cart successfully!";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to add product to cart: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

// Handle remove from cart
if (isset($_POST['remove_from_cart']) && isset($_SESSION['customer_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE Customer_ID = ? AND Product_ID = ?");
        $stmt->execute([$_SESSION['customer_id'], $_POST['product_id']]);
        
        $_SESSION['success_message'] = "Product removed from cart!";
        header("Location: cart.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to remove product: " . $e->getMessage();
    }
}

// Handle update quantity
if (isset($_POST['update_quantity']) && isset($_SESSION['customer_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE Cart SET Quantity = ? WHERE Customer_ID = ? AND Product_ID = ?");
        $stmt->execute([$_POST['quantity'], $_SESSION['customer_id'], $_POST['product_id']]);
        
        $_SESSION['success_message'] = "Cart updated successfully!";
        header("Location: cart.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to update cart: " . $e->getMessage();
    }
}

// Get cart items with product details
$cart_items = [];
$total = 0;

if (isset($_SESSION['customer_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.*, p.Product_Name, p.Price, p.Stock,
               (SELECT Image_Path FROM ProductImage pi WHERE pi.Product_ID = p.Product_ID LIMIT 1) as Image_Path
        FROM Cart c
        JOIN Product p ON c.Product_ID = p.Product_ID
        WHERE c.Customer_ID = ?
    ");
    $stmt->execute([$_SESSION['customer_id']]);
    $cart_items = $stmt->fetchAll();

    // Calculate total
    foreach ($cart_items as $item) {
        $total += $item['Price'] * $item['Quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'favicon.php'; ?>
    <title>Shopping Cart - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .cart-page {
            margin: 2rem auto;
        }
        .cart-header {
            background-color: #f05537;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .cart-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 4px;
        }
        .product-name {
            font-weight: 500;
            color: #333;
            text-decoration: none;
        }
        .product-name:hover {
            color: #f05537;
        }
        .quantity-input {
            width: 80px;
        }
        .item-price {
            font-weight: 500;
            color: #f05537;
        }
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
        }
        .btn-primary:hover {
            background-color: #d64426;
            border-color: #d64426;
        }
        .cart-summary {
            position: sticky;
            top: 2rem;
        }
        .employee-notice {
            padding: 2rem;
        }
        .employee-notice i {
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="cart-page">
        <div class="container">
            <div class="cart-header">
                <h2 class="m-0">Shopping Cart</h2>
                <p class="mb-0">Review your items and proceed to checkout</p>
            </div>

            <?php if(isset($_SESSION['employee_id'])): ?>
                <div class="cart-card text-center">
                    <div class="employee-notice">
                        <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Employee Account Notice</h4>
                        <p class="text-muted">Shopping cart and purchasing features are disabled for employee accounts.</p>
                        <p class="text-muted mb-4">Please use a customer account to make purchases.</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="products.php" class="btn btn-outline-primary">Browse Products</a>
                            <a href="logout.php" class="btn btn-primary">Switch Account</a>
                        </div>
                    </div>
                </div>
            <?php elseif(!isset($_SESSION['customer_id'])): ?>
                <div class="cart-card text-center">
                    <h4>Please login to view your cart</h4>
                    <p>You need to be logged in to manage your shopping cart.</p>
                    <a href="login.php" class="btn btn-primary">Login</a>
                </div>
            <?php elseif(empty($cart_items)): ?>
                <div class="cart-card text-center">
                    <h4>Your cart is empty</h4>
                    <p>Looks like you haven't added any items to your cart yet.</p>
                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Cart Items -->
                    <div class="col-md-8">
                        <div class="cart-card">
                            <?php foreach($cart_items as $item): ?>
                                <div class="cart-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="<?php echo htmlspecialchars($item['Image_Path'] ?? 'uploads/products/default.jpg'); ?>" 
                                                 class="product-image" 
                                                 alt="<?php echo htmlspecialchars($item['Product_Name']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <a href="product_details.php?id=<?php echo $item['Product_ID']; ?>" 
                                               class="product-name">
                                                <?php echo htmlspecialchars($item['Product_Name']); ?>
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <form method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="product_id" value="<?php echo $item['Product_ID']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item['Quantity']; ?>" 
                                                       min="1" max="<?php echo $item['Stock']; ?>" 
                                                       class="form-control quantity-input me-2">
                                                <button type="submit" name="update_quantity" class="btn btn-sm btn-primary">
                                                    Update
                                                </button>
                                            </form>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="item-price">
                                                $<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-1">
                                            <form method="POST">
                                                <input type="hidden" name="product_id" value="<?php echo $item['Product_ID']; ?>">
                                                <button type="submit" name="remove_from_cart" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Cart Summary -->
                    <div class="col-md-4">
                        <div class="cart-card cart-summary">
                            <h4 class="mb-4">Order Summary</h4>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal</span>
                                <span class="fw-bold">$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping</span>
                                <span>Calculated at checkout</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold fs-5">$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-grid">
                                <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 