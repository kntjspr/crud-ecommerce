<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['customer_id']) || !isset($_SESSION['order_id'])) {
    header("Location: login.php");
    exit();
}

// Get order details
$stmt = $pdo->prepare("
    SELECT t.*, p.Product_Name, p.Price 
    FROM Transaction t 
    JOIN Product p ON t.Product_ID = p.Product_ID 
    WHERE t.Order_ID = ?
");
$stmt->execute([$_SESSION['order_id']]);
$order_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($order_items as $item) {
    $total += $item['Price'] * $item['Quantity'];
}

// Get shipping methods
$shipping_methods = $pdo->query("SELECT * FROM Shipping_Method")->fetchAll();

// Get payment methods
$payment_methods = $pdo->query("SELECT * FROM Payment_Method")->fetchAll();

// Process checkout
if (isset($_POST['place_order'])) {
    try {
        $pdo->beginTransaction();

        // Create shipping address
        $stmt = $pdo->prepare("
            INSERT INTO Shipping_Address (Street, Barangay, Town_City, Province, Region, Postal_Code) 
            VALUES (?, ?, ?, ?, ?, ?)
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

        // Create shipping record
        $stmt = $pdo->prepare("
            INSERT INTO Shipping (Shipping_Status, Shipping_Address_ID, Shipping_Method_ID) 
            VALUES ('Pending', ?, ?)
        ");
        $stmt->execute([$shipping_address_id, $_POST['shipping_method']]);
        $shipping_id = $pdo->lastInsertId();

        // Create payment record
        $stmt = $pdo->prepare("
            INSERT INTO Payment (Order_ID, Payment_Date, Payment_Status, Payment_Method_ID) 
            VALUES (?, NOW(), 'Pending', ?)
        ");
        $stmt->execute([$_SESSION['order_id'], $_POST['payment_method']]);
        $payment_id = $pdo->lastInsertId();

        // Create receipt
        $stmt = $pdo->prepare("
            INSERT INTO Receipt (Tax_Amount, Total_Amount, Type) 
            VALUES (?, ?, 'Sale')
        ");
        $tax = $total * 0.12; // 12% tax
        $stmt->execute([$tax, $total + $tax]);
        $receipt_id = $pdo->lastInsertId();

        // Update transaction records
        $stmt = $pdo->prepare("
            UPDATE Transaction 
            SET Shipping_ID = ?, Payment_ID = ?, Receipt_ID = ? 
            WHERE Order_ID = ?
        ");
        $stmt->execute([$shipping_id, $payment_id, $receipt_id, $_SESSION['order_id']]);

        // Update order total
        $stmt = $pdo->prepare("UPDATE `Order` SET Total_Amount = ? WHERE Order_ID = ?");
        $stmt->execute([$total + $tax, $_SESSION['order_id']]);

        $pdo->commit();
        unset($_SESSION['order_id']); // Clear cart
        header("Location: order_confirmation.php?order_id=" . $_SESSION['order_id']);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Checkout failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Shoepee</title>
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
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Checkout</h1>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <form method="POST" class="needs-validation" novalidate>
                    <!-- Shipping Address -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Shipping Address</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="street" class="form-label">Street Address</label>
                                    <input type="text" class="form-control" id="street" name="street" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="barangay" class="form-label">Barangay</label>
                                    <input type="text" class="form-control" id="barangay" name="barangay" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="province" class="form-label">Province</label>
                                    <input type="text" class="form-control" id="province" name="province" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="region" class="form-label">Region</label>
                                    <input type="text" class="form-control" id="region" name="region" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Shipping Method</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach($shipping_methods as $method): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" 
                                           id="shipping_<?php echo $method['Shipping_Method_ID']; ?>" 
                                           value="<?php echo $method['Shipping_Method_ID']; ?>" required>
                                    <label class="form-check-label" for="shipping_<?php echo $method['Shipping_Method_ID']; ?>">
                                        <?php echo htmlspecialchars($method['Method_Name']); ?> - 
                                        $<?php echo number_format($method['Cost'], 2); ?> 
                                        (<?php echo htmlspecialchars($method['Estimated_Delivery_Time']); ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach($payment_methods as $method): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="payment_<?php echo $method['Payment_Method_ID']; ?>" 
                                           value="<?php echo $method['Payment_Method_ID']; ?>" required>
                                    <label class="form-check-label" for="payment_<?php echo $method['Payment_Method_ID']; ?>">
                                        <?php echo htmlspecialchars($method['Method_Name']); ?> - 
                                        <?php echo htmlspecialchars($method['Provider']); ?>
                                        <?php if($method['Transaction_Fee'] > 0): ?>
                                            (Fee: $<?php echo number_format($method['Transaction_Fee'], 2); ?>)
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary btn-lg">Place Order</button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($order_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo htmlspecialchars($item['Product_Name']); ?> x <?php echo $item['Quantity']; ?></span>
                                <span>$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Subtotal</strong>
                            <strong>$<?php echo number_format($total, 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tax (12%)</span>
                            <span>$<?php echo number_format($total * 0.12, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>$<?php echo number_format($total * 1.12, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 