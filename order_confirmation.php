<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Get order details with all related information
$stmt = $pdo->prepare("
    SELECT o.*, 
           c.First_Name, c.Last_Name,
           sm.Method_Name as Shipping_Method_Name,
           sm.Estimated_Delivery_Time,
           s.Shipping_Status,
           sa.Street, sa.Barangay, sa.Town_City, sa.Province, sa.Region, sa.Postal_Code,
           pm.Method_Name as Payment_Method_Name,
           pm.Provider as Payment_Provider,
           p.Payment_Status,
           p.Payment_Date
    FROM `Order` o
    JOIN Customer c ON o.Customer_ID = c.Customer_ID
    JOIN Transaction t ON o.Order_ID = t.Order_ID
    JOIN Shipping s ON t.Shipping_ID = s.Shipping_ID
    JOIN Shipping_Method sm ON s.Shipping_Method_ID = sm.Shipping_Method_ID
    JOIN Shipping_Address sa ON s.Shipping_Address_ID = sa.Shipping_Address_ID
    JOIN Payment p ON t.Payment_ID = p.Payment_ID
    JOIN Payment_Method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    WHERE o.Order_ID = ? AND o.Customer_ID = ?
    LIMIT 1
");

$stmt->execute([$_GET['order_id'], $_SESSION['customer_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

/* If order not found or doesn't belong to customer
    We'll redirect to index page without any error message for security reasons.
*/
if (!$order) {
    header("Location: index.php");
    exit();
}

// Get order items
$stmt = $pdo->prepare("
    SELECT p.Product_Name, p.Price, t.Quantity
    FROM Transaction t
    JOIN Product p ON t.Product_ID = p.Product_ID
    WHERE t.Order_ID = ?
");
$stmt->execute([$order['Order_ID']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <h5>Thank you for your order, <?php echo htmlspecialchars($order['First_Name'] ?? ''); ?>! Your order has been successfully placed.</h5>
                        
                        <p><strong>Order ID:</strong> <?php echo $order['Order_ID'] ?? ''; ?><br>
                        <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['Order_Date'] ?? '')); ?></p>

                        <!-- Shipping Information -->
                        <h6>Shipping Information</h6>
                        <p>
                            <strong>Method:</strong> <?php echo htmlspecialchars($order['Shipping_Method_Name'] ?? 'N/A'); ?><br>
                            <strong>Estimated Delivery:</strong> <?php echo htmlspecialchars($order['Estimated_Delivery_Time'] ?? 'N/A'); ?><br>
                            <strong>Status:</strong> <?php echo htmlspecialchars($order['Shipping_Status'] ?? 'N/A'); ?>
                        </p>

                        <p>
                            <strong>Shipping Address:</strong><br>
                            <?php echo htmlspecialchars($order['Street'] ?? ''); ?><br>
                            <?php echo htmlspecialchars($order['Barangay'] ?? ''); ?><br>
                            <?php echo htmlspecialchars($order['Town_City'] ?? ''); ?>,
                            <?php echo htmlspecialchars($order['Province'] ?? ''); ?><br>
                            <?php echo htmlspecialchars($order['Region'] ?? ''); ?>
                            <?php echo htmlspecialchars($order['Postal_Code'] ?? ''); ?>
                        </p>

                        <!-- Payment Information -->
                        <h6>Payment Information</h6>
                        <p>
                            <strong>Method:</strong> <?php echo htmlspecialchars($order['Payment_Method_Name'] ?? 'N/A'); ?> - 
                            <?php echo htmlspecialchars($order['Payment_Provider'] ?? 'N/A'); ?><br>
                            <strong>Status:</strong> <?php echo htmlspecialchars($order['Payment_Status'] ?? 'N/A'); ?><br>
                            <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['Payment_Date'] ?? '')); ?>
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