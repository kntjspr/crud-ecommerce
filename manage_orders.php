<?php
session_start();
require_once 'config/database.php';

// Debug session data
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'employee' || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    error_log("Authorization failed:");
    error_log("user_type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'not set'));
    error_log("is_admin: " . (isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'true' : 'false') : 'not set'));
    header("Location: login.php?error=unauthorized");
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    
    if ($_POST['action'] === 'update_shipping') {
        $shipping_status = $_POST['shipping_status'];
        // Update shipping status in Shipping table through Transaction
        $stmt = $pdo->prepare("
            UPDATE Shipping s
            JOIN Transaction t ON s.Shipping_ID = t.Shipping_ID
            SET s.Shipping_Status = ?
            WHERE t.Order_ID = ?
        ");
        $stmt->execute([$shipping_status, $order_id]);
        $_SESSION['success_message'] = "Shipping status updated successfully";
    }
    
    if ($_POST['action'] === 'update_payment') {
        $payment_status = $_POST['payment_status'];
        // Update payment status in Payment table
        $stmt = $pdo->prepare("
            UPDATE Payment 
            SET Payment_Status = ?
            WHERE Order_ID = ?
        ");
        $stmt->execute([$payment_status, $order_id]);
        $_SESSION['success_message'] = "Payment status updated successfully";
    }
    
    if ($_POST['action'] === 'delete_order') {
        try {
            $pdo->beginTransaction();
            
            // First update product quantities back
            $stmt = $pdo->prepare("
                SELECT Product_ID, Quantity 
                FROM Transaction 
                WHERE Order_ID = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                $stmt = $pdo->prepare("
                    UPDATE Product 
                    SET Stock = Stock + ? 
                    WHERE Product_ID = ?
                ");
                $stmt->execute([$item['Quantity'], $item['Product_ID']]);
            }
            
            // Delete related records in correct order
            $stmt = $pdo->prepare("
                DELETE t, p, s 
                FROM Transaction t
                LEFT JOIN Payment p ON t.Payment_ID = p.Payment_ID
                LEFT JOIN Shipping s ON t.Shipping_ID = s.Shipping_ID
                WHERE t.Order_ID = ?
            ");
            $stmt->execute([$order_id]);
            
            // Finally delete the order
            $stmt = $pdo->prepare("DELETE FROM `Order` WHERE Order_ID = ?");
            $stmt->execute([$order_id]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Order deleted successfully";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error deleting order: " . $e->getMessage();
        }
    }
    
    header("Location: manage_orders.php");
    exit();
}

// Get all orders with customer and shipping/payment information
$stmt = $pdo->query("
    SELECT o.*, 
           c.First_Name, c.Last_Name, c.Email,
           s.Shipping_Status,
           sm.Method_Name as Shipping_Method,
           p.Payment_Status,
           pm.Method_Name as Payment_Method
    FROM `Order` o
    JOIN Customer c ON o.Customer_ID = c.Customer_ID
    LEFT JOIN Transaction t ON o.Order_ID = t.Order_ID
    LEFT JOIN Shipping s ON t.Shipping_ID = s.Shipping_ID
    LEFT JOIN Shipping_Method sm ON s.Shipping_Method_ID = sm.Shipping_Method_ID
    LEFT JOIN Payment p ON t.Payment_ID = p.Payment_ID
    LEFT JOIN Payment_Method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    GROUP BY o.Order_ID
    ORDER BY o.Order_Date DESC
");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Shoepee</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Manage Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employee_register.php">Register Employee</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
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

        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Manage Orders</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="min-width: 80px">Order ID</th>
                                <th style="min-width: 200px">Customer</th>
                                <th style="min-width: 120px">Date</th>
                                <th style="min-width: 100px">Total</th>
                                <th style="min-width: 150px">Shipping Status</th>
                                <th style="min-width: 150px">Payment Status</th>
                                <th style="min-width: 180px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['Order_ID']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['First_Name'] . ' ' . $order['Last_Name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['Email']); ?></small>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['Order_Date'])); ?></td>
                                    <td>$<?php echo number_format($order['Total_Amount'], 2); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="update_shipping">
                                            <input type="hidden" name="order_id" value="<?php echo $order['Order_ID']; ?>">
                                            <select name="shipping_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="Pending" <?php echo $order['Shipping_Status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo $order['Shipping_Status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Shipped" <?php echo $order['Shipping_Status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="Delivered" <?php echo $order['Shipping_Status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="update_payment">
                                            <input type="hidden" name="order_id" value="<?php echo $order['Order_ID']; ?>">
                                            <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="Pending" <?php echo $order['Payment_Status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Paid" <?php echo $order['Payment_Status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="Failed" <?php echo $order['Payment_Status'] == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#orderDetails<?php echo $order['Order_ID']; ?>">
                                            View Details
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                            <input type="hidden" name="action" value="delete_order">
                                            <input type="hidden" name="order_id" value="<?php echo $order['Order_ID']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php foreach($orders as $order): ?>
        <!-- Order Details Modal -->
        <div class="modal fade" id="orderDetails<?php echo $order['Order_ID']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order #<?php echo $order['Order_ID']; ?> Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <p>
                                    <strong>Name:</strong> <?php echo htmlspecialchars($order['First_Name'] . ' ' . $order['Last_Name']); ?><br>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($order['Email']); ?>
                                </p>

                                <h6>Shipping Information</h6>
                                <p>
                                    <strong>Method:</strong> <?php echo htmlspecialchars($order['Shipping_Method'] ?? 'N/A'); ?><br>
                                    <strong>Status:</strong> <?php echo htmlspecialchars($order['Shipping_Status'] ?? 'N/A'); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Payment Information</h6>
                                <p>
                                    <strong>Method:</strong> <?php echo htmlspecialchars($order['Payment_Method'] ?? 'N/A'); ?><br>
                                    <strong>Status:</strong> <?php echo htmlspecialchars($order['Payment_Status'] ?? 'N/A'); ?><br>
                                    <strong>Total Amount:</strong> $<?php echo number_format($order['Total_Amount'] ?? 0, 2); ?>
                                </p>

                                <h6>Order Status</h6>
                                <p>
                                    <strong>Order Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['Order_Date'])); ?><br>
                                    <strong>Shipping Status:</strong> <?php echo htmlspecialchars($order['Shipping_Status']); ?>
                                </p>
                            </div>
                        </div>

                        <?php
                        // Get order items
                        $stmt = $pdo->prepare("
                            SELECT p.*, t.Quantity
                            FROM Transaction t
                            JOIN Product p ON t.Product_ID = p.Product_ID
                            WHERE t.Order_ID = ?
                        ");
                        $stmt->execute([$order['Order_ID']]);
                        $items = $stmt->fetchAll();
                        ?>

                        <h6 class="mt-4">Order Items</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['Product_Name']); ?></td>
                                        <td>$<?php echo number_format($item['Price'], 2); ?></td>
                                        <td><?php echo $item['Quantity']; ?></td>
                                        <td>$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 