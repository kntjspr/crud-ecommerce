<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Create order if doesn't exist
    if (!isset($_SESSION['order_id'])) {
        $stmt = $pdo->prepare("INSERT INTO `Order` (Customer_ID, Order_Date, Total_Amount) VALUES (?, NOW(), 0)");
        $stmt->execute([$_SESSION['customer_id']]);
        $_SESSION['order_id'] = $pdo->lastInsertId();
    }
    
    // Add to transaction
    $stmt = $pdo->prepare("INSERT INTO Transaction (Order_ID, Product_ID) VALUES (?, ?)");
    $stmt->execute([$_SESSION['order_id'], $product_id]);
    
    header("Location: cart.php");
    exit();
}

// Remove from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $transaction_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Transaction WHERE Transaction_ID = ? AND Order_ID = ?");
    $stmt->execute([$transaction_id, $_SESSION['order_id']]);
    
    header("Location: cart.php");
    exit();
}

// Get cart items
$cart_items = [];
if (isset($_SESSION['order_id'])) {
    $stmt = $pdo->prepare("
        SELECT t.Transaction_ID, p.*, t.Quantity 
        FROM Transaction t 
        JOIN Product p ON t.Product_ID = p.Product_ID 
        WHERE t.Order_ID = ?
    ");
    $stmt->execute([$_SESSION['order_id']]);
    $cart_items = $stmt->fetchAll();
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['Price'] * $item['Quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Shoepee</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="products.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
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
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <a href="product_details.php?id=<?php echo $item['Product_ID']; ?>">
                                        <?php echo htmlspecialchars($item['Product_Name']); ?>
                                    </a>
                                </td>
                                <td>$<?php echo number_format($item['Price'], 2); ?></td>
                                <td>
                                    <form action="update_cart.php" method="POST" class="d-flex" style="max-width: 150px;">
                                        <input type="hidden" name="transaction_id" value="<?php echo $item['Transaction_ID']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['Quantity']; ?>" 
                                               min="1" max="<?php echo $item['Stock']; ?>" class="form-control me-2">
                                        <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                                    </form>
                                </td>
                                <td>$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?php echo $item['Transaction_ID']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to remove this item?')">
                                        Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
                <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 