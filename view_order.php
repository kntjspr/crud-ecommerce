<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_orders.php");
    exit();
}

$order_id = $_GET['id'];

// Get order details with customer info
$stmt = $pdo->prepare("
    SELECT o.*, 
           c.First_Name, c.Last_Name, c.Email, c.Phone_Number,
           p.Payment_Status, p.Payment_Date, p.Amount as Payment_Amount, pm.Method_Name as Payment_Method,
           s.Shipping_Status,
           sa.Street, sa.Barangay, sa.Town_City, sa.Province, sa.Region, sa.Postal_Code,
           sm.Method_Name as Shipping_Method, sm.Cost as Shipping_Cost
    FROM `Order` o
    LEFT JOIN Customer c ON o.Customer_ID = c.Customer_ID
    LEFT JOIN Payment p ON o.Order_ID = p.Order_ID
    LEFT JOIN Payment_Method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    LEFT JOIN Transaction t ON o.Order_ID = t.Order_ID
    LEFT JOIN Shipping s ON t.Shipping_ID = s.Shipping_ID
    LEFT JOIN Shipping_Address sa ON s.Shipping_Address_ID = sa.Shipping_Address_ID
    LEFT JOIN Shipping_Method sm ON s.Shipping_Method_ID = sm.Shipping_Method_ID
    WHERE o.Order_ID = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: manage_orders.php");
    exit();
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.Product_Name, p.Price,
           (SELECT Image_Path FROM ProductImage pi WHERE pi.Product_ID = p.Product_ID LIMIT 1) as Image_Path
    FROM OrderItem oi
    LEFT JOIN Product p ON oi.Product_ID = p.Product_ID
    WHERE oi.Order_ID = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Calculate subtotal from order items
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['Price'] * $item['Quantity'];
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        if (isset($_POST['update_payment'])) {
            $stmt = $pdo->prepare("
                UPDATE Payment 
                SET Payment_Status = ? 
                WHERE Order_ID = ?
            ");
            $stmt->execute([$_POST['payment_status'], $order_id]);
        }

        if (isset($_POST['update_shipping'])) {
            $stmt = $pdo->prepare("
                UPDATE Shipping s
                JOIN Transaction t ON s.Shipping_ID = t.Shipping_ID
                SET s.Shipping_Status = ?
                WHERE t.Order_ID = ?
            ");
            $stmt->execute([$_POST['shipping_status'], $order_id]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = "Order status updated successfully!";
        header("Location: view_order.php?id=" . $order_id);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Failed to update status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?> - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .order-page {
            margin: 2rem auto;
        }
        .page-header {
            background-color: #f05537;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .content-card {
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
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 4px;
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
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
        }
        .btn-primary:hover {
            background-color: #d64426;
            border-color: #d64426;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="order-page">
        <div class="container">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="m-0">Order #<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></h2>
                    <p class="mb-0">Placed on <?php echo date('F j, Y', strtotime($order['Order_Date'])); ?></p>
                </div>
                <a href="manage_orders.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Order Status -->
                <div class="col-md-4">
                    <div class="content-card">
                        <h4 class="section-title">Payment Status</h4>
                        <form method="POST">
                            <div class="mb-3">
                                <select name="payment_status" class="form-select mb-2">
                                    <option value="Pending" <?php echo $order['Payment_Status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Completed" <?php echo $order['Payment_Status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $order['Payment_Status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_payment" class="btn btn-primary">Update Payment Status</button>
                            </div>
                        </form>

                        <h4 class="section-title">Shipping Status</h4>
                        <form method="POST">
                            <div class="mb-3">
                                <select name="shipping_status" class="form-select mb-2">
                                    <option value="Pending" <?php echo $order['Shipping_Status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo $order['Shipping_Status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Shipped" <?php echo $order['Shipping_Status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="Delivered" <?php echo $order['Shipping_Status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="Cancelled" <?php echo $order['Shipping_Status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_shipping" class="btn btn-primary">Update Shipping Status</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="col-md-4">
                    <div class="content-card">
                        <h4 class="section-title">Customer Information</h4>
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['First_Name'] . ' ' . $order['Last_Name']); ?></div>
                        
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['Email']); ?></div>
                        
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['Phone_Number']); ?></div>

                        <h4 class="section-title">Shipping Address</h4>
                        <div class="info-value">
                            <?php
                            echo htmlspecialchars($order['Street']) . "<br>";
                            echo htmlspecialchars($order['Barangay']) . "<br>";
                            echo htmlspecialchars($order['Town_City']) . ", " . htmlspecialchars($order['Province']) . "<br>";
                            echo htmlspecialchars($order['Region']) . " " . htmlspecialchars($order['Postal_Code']);
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="col-md-4">
                    <div class="content-card">
                        <h4 class="section-title">Payment Information</h4>
                        <div class="info-label">Payment Method</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['Payment_Method']); ?></div>
                        
                        <div class="info-label">Payment Date</div>
                        <div class="info-value">
                            <?php echo $order['Payment_Date'] ? date('F j, Y', strtotime($order['Payment_Date'])) : 'Not paid yet'; ?>
                        </div>

                        <h4 class="section-title">Shipping Method</h4>
                        <div class="info-label">Method</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['Shipping_Method']); ?></div>
                        
                        <div class="info-label">Shipping Cost</div>
                        <div class="info-value">$<?php echo number_format($order['Shipping_Cost'], 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="content-card">
                <h4 class="section-title">Order Items</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($item['Image_Path'] ?? 'uploads/products/default.jpg'); ?>" 
                                                 class="product-image me-3" 
                                                 alt="<?php echo htmlspecialchars($item['Product_Name']); ?>">
                                            <div>
                                                <?php echo htmlspecialchars($item['Product_Name']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['Price'], 2); ?></td>
                                    <td><?php echo $item['Quantity']; ?></td>
                                    <td class="text-end">$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                                <td class="text-end">$<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Shipping</strong></td>
                                <td class="text-end">$<?php echo number_format($order['Shipping_Cost'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($subtotal + $order['Shipping_Cost'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
