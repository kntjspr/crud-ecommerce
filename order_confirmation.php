<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in as customer
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $_GET['order_id'];

// Get order details with shipping and payment info
$stmt = $pdo->prepare("
    SELECT o.*, 
           p.Payment_Status, p.Amount as Payment_Amount, p.Payment_Method_ID,
           c.First_Name, c.Last_Name, c.Email, c.Phone_Number,
           s.Shipping_Status, s.Shipping_Method_ID,
           sa.Street, sa.Barangay, sa.Town_City, sa.Province, sa.Region, sa.Postal_Code,
           sm.Method_Name as Shipping_Method_Name, sm.Cost as Shipping_Cost,
           pm.Method_Name as Payment_Method_Name
    FROM `Order` o
    LEFT JOIN Transaction t ON o.Order_ID = t.Order_ID
    LEFT JOIN Payment p ON t.Payment_ID = p.Payment_ID
    LEFT JOIN Customer c ON o.Customer_ID = c.Customer_ID
    LEFT JOIN Shipping s ON t.Shipping_ID = s.Shipping_ID
    LEFT JOIN Shipping_Address sa ON s.Shipping_Address_ID = sa.Shipping_Address_ID
    LEFT JOIN Shipping_Method sm ON s.Shipping_Method_ID = sm.Shipping_Method_ID
    LEFT JOIN Payment_Method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    WHERE o.Order_ID = ? AND o.Customer_ID = ?
");
$stmt->execute([$order_id, $_SESSION['customer_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit();
}

// Get order items
$stmt = $pdo->prepare("
    SELECT t.Quantity, t.Product_ID, p.Product_Name, p.Price,
           (SELECT Image_Path FROM ProductImage WHERE Product_ID = p.Product_ID LIMIT 1) as Image_Path
    FROM Transaction t
    JOIN Product p ON t.Product_ID = p.Product_ID
    WHERE t.Order_ID = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Get settings for tax and shipping
$stmt = $pdo->query("SELECT tax_rate, shipping_fee, free_shipping_threshold FROM Settings WHERE id = 1");
$settings = $stmt->fetch();

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['Price'] * $item['Quantity'];
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
    <title>Order Confirmation - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .confirmation-page {
            margin: 2rem auto;
        }
        .confirmation-header {
            background-color: #f05537;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .confirmation-card {
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
        .order-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
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
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-completed {
            background-color: #198754;
            color: white;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
        }
        .btn-primary:hover {
            background-color: #d64426;
            border-color: #d64426;
        }
        .info-label {
            font-weight: 500;
            color: #666;
            margin-bottom: 0.25rem;
        }
        .info-value {
            color: #333;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="confirmation-page">
        <div class="container">
            <div class="confirmation-header">
                <h2 class="m-0">Order Confirmation</h2>
                <p class="mb-0">Thank you for your order!</p>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Order Information -->
                <div class="col-md-8">
                    <div class="confirmation-card">
                        <h4 class="section-title">Order Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-label">Order Number</div>
                                <div class="info-value">#<?php echo str_pad($order['Order_ID'], 8, '0', STR_PAD_LEFT); ?></div>
                                
                                <div class="info-label">Order Date</div>
                                <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['Order_Date'])); ?></div>
                                
                                <div class="info-label">Payment Method</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['Payment_Method_Name']); ?></div>
                                
                                <div class="info-label">Payment Status</div>
                                <div class="info-value">
                                    <span class="status-badge <?php 
                                        if($order['Payment_Status'] == 'Completed') {
                                            echo 'status-completed';
                                        } elseif($order['Payment_Status'] == 'Cancelled') {
                                            echo 'status-cancelled';
                                        } else {
                                            echo 'status-pending';
                                        }
                                    ?>">
                                        <?php echo $order['Payment_Status']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Shipping Method</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['Shipping_Method_Name']); ?></div>
                                
                                <div class="info-label">Shipping Status</div>
                                <div class="info-value">
                                    <span class="status-badge <?php 
                                        if($order['Shipping_Status'] == 'Delivered') {
                                            echo 'status-completed';
                                        } elseif($order['Shipping_Status'] == 'Cancelled') {
                                            echo 'status-cancelled';
                                        } else {
                                            echo 'status-pending';
                                        }
                                    ?>">
                                        <?php echo $order['Shipping_Status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="confirmation-card">
                        <h4 class="section-title">Shipping Address</h4>
                        <div class="row">
                            <div class="col-12">
                                <div class="info-value">
                                    <?php echo htmlspecialchars($order['Street']); ?><br>
                                    <?php echo htmlspecialchars($order['Barangay']); ?><br>
                                    <?php echo htmlspecialchars($order['Town_City'] . ', ' . $order['Province']); ?><br>
                                    <?php echo htmlspecialchars($order['Region'] . ' ' . $order['Postal_Code']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="confirmation-card">
                        <h4 class="section-title">Order Items</h4>
                        <?php foreach($order_items as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-2">
                                        <img src="<?php echo htmlspecialchars($item['Image_Path'] ?? 'assets/images/default-product.jpg'); ?>" 
                                             class="product-image" 
                                             alt="<?php echo htmlspecialchars($item['Product_Name']); ?>">
                                    </div>
                                    <div class="col-6">
                                        <div class="product-name"><?php echo htmlspecialchars($item['Product_Name']); ?></div>
                                        <small class="text-muted">Quantity: <?php echo $item['Quantity']; ?></small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="item-price">$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></div>
                                        <small class="text-muted">$<?php echo number_format($item['Price'], 2); ?> each</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-md-4">
                    <div class="confirmation-card">
                        <h4 class="section-title">Order Summary</h4>
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Shipping (<?php echo htmlspecialchars($order['Shipping_Method_Name']); ?>)</span>
                            <span>$<?php echo number_format($order['Shipping_Cost'], 2); ?></span>
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
                            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 