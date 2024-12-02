<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = (int)$_GET['order_id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, c.First_Name, c.Last_Name, c.Email 
    FROM `Order` o 
    JOIN Customer c ON o.Customer_ID = c.Customer_ID 
    WHERE o.Order_ID = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Get order items
$stmt = $pdo->prepare("
    SELECT t.*, p.Product_Name, p.Price 
    FROM Transaction t 
    JOIN Product p ON t.Product_ID = p.Product_ID 
    WHERE t.Order_ID = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Get shipping details
$stmt = $pdo->prepare("
    SELECT s.*, sa.*, sm.Method_Name, sm.Estimated_Delivery_Time 
    FROM Transaction t 
    JOIN Shipping s ON t.Shipping_ID = s.Shipping_ID 
    JOIN Shipping_Address sa ON s.Shipping_Address_ID = sa.Shipping_Address_ID 
    JOIN Shipping_Method sm ON s.Shipping_Method_ID = sm.Shipping_Method_ID 
    WHERE t.Order_ID = ? 
    LIMIT 1
");
$stmt->execute([$order_id]);
$shipping = $stmt->fetch();

// Get payment details
$stmt = $pdo->prepare("
    SELECT p.*, pm.Method_Name, pm.Provider 
    FROM Transaction t 
    JOIN Payment p ON t.Payment_ID = p.Payment_ID 
    JOIN Payment_Method pm ON p.Payment_Method_ID = pm.Payment_Method_ID 
    WHERE t.Order_ID = ? 
    LIMIT 1
");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Shoepee</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h1 class="text-center text-success mb-4">
                            <i class="bi bi-check-circle"></i> Order Confirmed!
                        </h1>
                        <p class="text-center">
                            Thank you for your order, <?php echo htmlspecialchars($order['First_Name']); ?>! 
                            Your order has been successfully placed.
                        </p>
                        <p class="text-center">
                            Order ID: <strong><?php echo $order_id; ?></strong><br>
                            Date: <?php echo date('M d, Y H:i', strtotime($order['Order_Date'])); ?>
                        </p>

                        <hr>

                        <!-- Order Items -->
                        <h5>Order Items</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($order_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['Product_Name']); ?></td>
                                            <td>$<?php echo number_format($item['Price'], 2); ?></td>
                                            <td><?php echo $item['Quantity']; ?></td>
                                            <td>$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>$<?php echo number_format($order['Total_Amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Shipping Information -->
                        <h5>Shipping Information</h5>
                        <p>
                            <strong>Method:</strong> <?php echo htmlspecialchars($shipping['Method_Name']); ?><br>
                            <strong>Estimated Delivery:</strong> <?php echo htmlspecialchars($shipping['Estimated_Delivery_Time']); ?><br>
                            <strong>Status:</strong> <?php echo htmlspecialchars($shipping['Shipping_Status']); ?>
                        </p>
                        <p>
                            <strong>Shipping Address:</strong><br>
                            <?php echo htmlspecialchars($shipping['Street']); ?><br>
                            <?php echo htmlspecialchars($shipping['Barangay']); ?><br>
                            <?php echo htmlspecialchars($shipping['Town_City']); ?>, 
                            <?php echo htmlspecialchars($shipping['Province']); ?><br>
                            <?php echo htmlspecialchars($shipping['Region']); ?> 
                            <?php echo htmlspecialchars($shipping['Postal_Code']); ?>
                        </p>

                        <!-- Payment Information -->
                        <h5>Payment Information</h5>
                        <p>
                            <strong>Method:</strong> <?php echo htmlspecialchars($payment['Method_Name']); ?> - 
                            <?php echo htmlspecialchars($payment['Provider']); ?><br>
                            <strong>Status:</strong> <?php echo htmlspecialchars($payment['Payment_Status']); ?><br>
                            <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($payment['Payment_Date'])); ?>
                        </p>

                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 