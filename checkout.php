<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in as customer
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.Product_Name, p.Price, p.Stock,
           (SELECT Image_Path FROM ProductImage pi WHERE pi.Product_ID = p.Product_ID LIMIT 1) as Image_Path
    FROM Cart c
    JOIN Product p ON c.Product_ID = p.Product_ID
    WHERE c.Customer_ID = ?
");
$stmt->execute([$_SESSION['customer_id']]);
$cart_items = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['Price'] * $item['Quantity'];
}

// Get settings for shipping and tax
$stmt = $pdo->query("SELECT shipping_fee, free_shipping_threshold, tax_rate FROM Settings WHERE id = 1");
$settings = $stmt->fetch();

// Get shipping methods
$shipping_methods = $pdo->query("SELECT * FROM Shipping_Method ORDER BY Cost")->fetchAll();

// Get payment methods
$payment_methods = $pdo->query("SELECT * FROM Payment_Method ORDER BY Method_Name")->fetchAll();

// Get customer details
$stmt = $pdo->prepare("SELECT * FROM Customer WHERE Customer_ID = ?");
$stmt->execute([$_SESSION['customer_id']]);
$customer = $stmt->fetch();

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Create shipping address
        $stmt = $pdo->prepare("
            INSERT INTO Shipping_Address (
                Street, Barangay, Town_City, Province, Region, Postal_Code
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['street'],
            $_POST['barangay'],
            $_POST['city'],
            $_POST['province'],
            $_POST['region'],
            $_POST['postal_code']
        ]);
        $shipping_address_id = $pdo->lastInsertId();

        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO `Order` (Customer_ID, Order_Date, Total_Amount)
            VALUES (?, NOW(), ?)
        ");
        $stmt->execute([$_SESSION['customer_id'], $subtotal]);
        $order_id = $pdo->lastInsertId();

        // Insert order items
        $stmt_order_items = $pdo->prepare("
            INSERT INTO OrderItem (Order_ID, Product_ID, Quantity, Price)
            VALUES (?, ?, ?, ?)
        ");

        // Create shipping record
        $stmt = $pdo->prepare("
            INSERT INTO Shipping (Shipping_Status, Shipping_Address_ID, Shipping_Method_ID)
            VALUES ('Pending', ?, ?)
        ");
        $stmt->execute([$shipping_address_id, $_POST['shipping_method']]);
        $shipping_id = $pdo->lastInsertId();

        // Create payment record
        $stmt = $pdo->prepare("
            INSERT INTO Payment (Payment_Method_ID, Payment_Date, Payment_Status, Amount, Order_ID)
            VALUES (?, NOW(), 'Pending', ?, ?)
        ");
        $stmt->execute([$_POST['payment_method'], $subtotal, $order_id]);
        $payment_id = $pdo->lastInsertId();

        // Create transaction record
        $stmt = $pdo->prepare("
            INSERT INTO Transaction (Order_ID, Product_ID, Shipping_ID, Receipt_ID, Payment_ID)
            VALUES (?, ?, ?, NULL, ?)
        ");

        // Create order items and update stock
        foreach ($cart_items as $item) {
            // Insert order items
            $stmt_order_items->execute([
                $order_id,
                $item['Product_ID'],
                $item['Quantity'],
                $item['Price']
            ]);

            // Insert transaction
            $stmt->execute([
                $order_id,
                $item['Product_ID'],
                $shipping_id,
                $payment_id
            ]);

            // Update product stock
            $update_stock = $pdo->prepare("
                UPDATE Product 
                SET Stock = Stock - ? 
                WHERE Product_ID = ?
            ");
            $update_stock->execute([$item['Quantity'], $item['Product_ID']]);
        }

        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE Customer_ID = ?");
        $stmt->execute([$_SESSION['customer_id']]);

        $pdo->commit();
        $_SESSION['success_message'] = "Order placed successfully! Order ID: " . str_pad($order_id, 8, '0', STR_PAD_LEFT);
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Failed to place order: " . $e->getMessage();
    }
}

$shipping_fee = $subtotal >= ($settings['free_shipping_threshold'] ?? 0) ? 0 : ($settings['shipping_fee'] ?? 0);
$tax = $subtotal * (($settings['tax_rate'] ?? 0) / 100);
$total = $subtotal + $shipping_fee + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'favicon.php'; ?>
    <title>Checkout - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .checkout-page {
            margin: 2rem auto;
        }
        .checkout-header {
            background-color: #f05537;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .checkout-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #f05537;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .cart-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 4px;
        }
        .product-name {
            font-weight: 500;
            color: #333;
        }
        .item-price {
            color: #f05537;
            font-weight: 500;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .summary-total {
            font-size: 1.2rem;
            font-weight: 600;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 2px solid #f0f0f0;
        }
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
        }
        .btn-primary:hover {
            background-color: #d64426;
            border-color: #d64426;
        }
        .shipping-method, .payment-method {
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: block;
        }
        .shipping-method:hover, .payment-method:hover {
            border-color: #f05537;
            background-color: #fff5f3;
        }
        .shipping-method input:checked + div,
        .payment-method input:checked + div {
            color: #f05537;
        }
        .shipping-method input:checked + div strong,
        .payment-method input:checked + div strong {
            color: #f05537;
        }
        .shipping-method input:checked + div .text-muted,
        .payment-method input:checked + div .text-muted {
            color: #f05537 !important;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="checkout-page">
        <div class="container">
            <div class="checkout-header">
                <h2 class="m-0">Checkout</h2>
                <p class="mb-0">Complete your order</p>
            </div>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(empty($cart_items)): ?>
                <div class="checkout-card text-center">
                    <h4>Your cart is empty</h4>
                    <p>Add some products to your cart before checking out.</p>
                    <a href="products.php" class="btn btn-primary">Browse Products</a>
                </div>
            <?php else: ?>
                <form method="POST" class="row">
                    <div class="col-md-8">
                        <!-- Shipping Address -->
                        <div class="checkout-card">
                            <h4 class="section-title">Shipping Address</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Street Address</label>
                                    <input type="text" class="form-control" name="street" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Barangay</label>
                                    <input type="text" class="form-control" name="barangay" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Province</label>
                                    <input type="text" class="form-control" name="province" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Region</label>
                                    <input type="text" class="form-control" name="region" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" name="postal_code" required>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Method -->
                        <div class="checkout-card">
                            <h4 class="section-title">Shipping Method</h4>
                            <?php foreach($shipping_methods as $method): ?>
                                <label class="shipping-method w-100" for="shipping_<?php echo $method['Shipping_Method_ID']; ?>">
                                    <input class="form-check-input visually-hidden" type="radio" 
                                           name="shipping_method" 
                                           id="shipping_<?php echo $method['Shipping_Method_ID']; ?>"
                                           value="<?php echo $method['Shipping_Method_ID']; ?>" required>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($method['Method_Name']); ?></strong>
                                            <div class="text-muted">
                                                Estimated delivery: <?php echo htmlspecialchars($method['Estimated_Delivery_Time']); ?>
                                            </div>
                                        </div>
                                        <div class="text-primary">
                                            $<?php echo number_format($method['Cost'], 2); ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Payment Method -->
                        <div class="checkout-card">
                            <h4 class="section-title">Payment Method</h4>
                            <?php foreach($payment_methods as $method): ?>
                                <label class="payment-method w-100" for="payment_<?php echo $method['Payment_Method_ID']; ?>">
                                    <input class="form-check-input visually-hidden" type="radio" 
                                           name="payment_method" 
                                           id="payment_<?php echo $method['Payment_Method_ID']; ?>"
                                           value="<?php echo $method['Payment_Method_ID']; ?>" required>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($method['Method_Name']); ?></strong>
                                            <div class="text-muted">
                                                Provider: <?php echo htmlspecialchars($method['Provider']); ?>
                                            </div>
                                        </div>
                                        <?php if($method['Transaction_Fee'] > 0): ?>
                                            <div class="text-primary">
                                                Fee: $<?php echo number_format($method['Transaction_Fee'], 2); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Order Items -->
                        <div class="checkout-card">
                            <h4 class="section-title">Order Items</h4>
                            <?php foreach($cart_items as $item): ?>
                                <div class="cart-item">
                                    <div class="row align-items-center">
                                        <div class="col-2">
                                            <img src="<?php echo htmlspecialchars($item['Image_Path'] ?? 'uploads/products/default.jpg'); ?>" 
                                                 class="product-image" 
                                                 alt="<?php echo htmlspecialchars($item['Product_Name']); ?>">
                                        </div>
                                        <div class="col-6">
                                            <div class="product-name"><?php echo htmlspecialchars($item['Product_Name']); ?></div>
                                            <small class="text-muted">Quantity: <?php echo $item['Quantity']; ?></small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="item-price">$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-md-4">
                        <div class="checkout-card">
                            <h4 class="section-title">Order Summary</h4>
                            <div class="summary-item">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Shipping</span>
                                <span>$<?php echo number_format($shipping_fee, 2); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Tax (<?php echo number_format($settings['tax_rate'] ?? 0, 1); ?>%)</span>
                                <span>$<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="summary-item summary-total">
                                <span>Total</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Place Order</button>
                                <a href="cart.php" class="btn btn-outline-secondary">Back to Cart</a>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add selection effect to shipping and payment methods
        document.querySelectorAll('.shipping-method, .payment-method').forEach(label => {
            label.addEventListener('click', function() {
                const input = this.querySelector('input[type="radio"]');
                const type = input.name === 'shipping_method' ? 'shipping-method' : 'payment-method';
                
                // Remove selected class from all methods of same type
                document.querySelectorAll('.' + type).forEach(div => {
                    div.classList.remove('selected');
                });
                
                // Add selected class to clicked method
                this.classList.add('selected');
                
                // Check the radio input
                input.checked = true;
            });
        });
    </script>
</body>
</html> 