<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Check if product exists and has enough stock
    $stmt = $pdo->prepare("SELECT Product_ID, Stock FROM Product WHERE Product_ID = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product && $quantity <= $product['Stock']) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        // Check if quantity exceeds stock
        if ($_SESSION['cart'][$product_id] > $product['Stock']) {
            $_SESSION['cart'][$product_id] = $product['Stock'];
        }
    }
    
    header("Location: cart.php");
    exit();
}

// Remove from cart
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    unset($_SESSION['cart'][$product_id]);
    header("Location: cart.php");
    exit();
}

// Get cart items
$cart_items = [];
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT * FROM Product 
        WHERE Product_ID IN ($placeholders)
    ");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $product_id = $product['Product_ID'];
        $cart_items[] = array_merge($product, [
            'Quantity' => $_SESSION['cart'][$product_id]
        ]);
    }
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
                <form id="cartForm" action="update_cart.php" method="POST">
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
                                        <div class="d-flex" style="max-width: 150px;">
                                            <input type="number" 
                                                   name="cart_items[<?php echo $item['Product_ID']; ?>]" 
                                                   value="<?php echo $item['Quantity']; ?>" 
                                                   min="0" 
                                                   max="<?php echo $item['Stock']; ?>" 
                                                   class="form-control me-2">
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></td>
                                    <td>
                                        <a href="cart.php?action=remove&id=<?php echo $item['Product_ID']; ?>" 
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
                                <td>
                                    <button type="submit" class="btn btn-primary btn-sm">Update Cart</button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </form>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
                <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('cartForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch('update_cart.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.messages.join('\n'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating cart');
        });
    });
    </script>
</body>
</html> 