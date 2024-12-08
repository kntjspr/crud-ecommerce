<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Get all orders with customer details
$stmt = $pdo->query("
    SELECT o.*, 
           c.First_Name as Customer_First_Name, 
           c.Last_Name as Customer_Last_Name,
           p.Payment_Status,
           s.Shipping_Status
    FROM `Order` o
    LEFT JOIN Customer c ON o.Customer_ID = c.Customer_ID
    LEFT JOIN Payment p ON o.Order_ID = p.Order_ID
    LEFT JOIN Transaction t ON o.Order_ID = t.Order_ID
    LEFT JOIN Shipping s ON t.Shipping_ID = s.Shipping_ID
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
    <style>
        body {
            background-color: #f0f0f0;
        }
        .manage-page .manage-container {
            margin: 2rem auto;
            padding: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .manage-page .manage-header {
            background-color: #f05537;
            color: white;
            padding: 1rem;
            border-radius: 8px 8px 0 0;
        }
        .manage-page .manage-content {
            padding: 2rem;
        }
        .manage-page .table {
            margin-bottom: 0;
        }
        .manage-page .table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 500;
        }
        .manage-page .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .manage-page .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .manage-page .status-completed {
            background-color: #198754;
            color: white;
        }
        .manage-page .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .manage-page .btn-view {
            background-color: #f05537;
            border: none;
            color: white;
        }
        .manage-page .btn-view:hover {
            background-color: #e04527;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="manage-page">
        <div class="container">
            <div class="manage-container">
                <div class="manage-header">
                    <h2 class="m-0">Manage Orders</h2>
                </div>

                <div class="manage-content">
                    <?php if(isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Order Date</th>
                                    <th>Total Amount</th>
                                    <th>Payment Status</th>
                                    <th>Shipping Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['Order_ID'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['Customer_First_Name'] . ' ' . $order['Customer_Last_Name']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['Order_Date'])); ?></td>
                                        <td>$<?php echo number_format($order['Total_Amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php 
                                                if($order['Payment_Status'] == 'Completed') {
                                                    echo 'status-completed';
                                                } elseif($order['Payment_Status'] == 'Cancelled') {
                                                    echo 'status-cancelled';
                                                } else {
                                                    echo 'status-pending';
                                                }
                                            ?>">
                                                <?php echo $order['Payment_Status'] ?? 'Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php 
                                                if($order['Shipping_Status'] == 'Delivered') {
                                                    echo 'status-completed';
                                                } elseif($order['Shipping_Status'] == 'Cancelled') {
                                                    echo 'status-cancelled';
                                                } else {
                                                    echo 'status-pending';
                                                }
                                            ?>">
                                                <?php echo $order['Shipping_Status'] ?? 'Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_order.php?id=<?php echo $order['Order_ID']; ?>" 
                                               class="btn btn-view btn-sm">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 