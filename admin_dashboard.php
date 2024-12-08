<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Get recent orders
$stmt = $pdo->query("
    SELECT o.*, c.First_Name, c.Last_Name, p.Payment_Status, s.Shipping_Status
    FROM `Order` o
    LEFT JOIN Customer c ON o.Customer_ID = c.Customer_ID
    LEFT JOIN Payment p ON o.Order_ID = p.Order_ID
    LEFT JOIN Transaction t ON o.Order_ID = t.Order_ID
    LEFT JOIN Shipping s ON t.Shipping_ID = s.Shipping_ID
    ORDER BY o.Order_Date DESC
    LIMIT 5
");
$recent_orders = $stmt->fetchAll();

// Get low stock products (less than 10 items)
$stmt = $pdo->query("
    SELECT p.*, c.Category_Name,
           (SELECT Image_Path FROM ProductImage pi WHERE pi.Product_ID = p.Product_ID LIMIT 1) as Image_Path
    FROM Product p
    LEFT JOIN Category c ON p.Category_ID = c.Category_ID
    WHERE p.Stock < 10
    ORDER BY p.Stock ASC
    LIMIT 5
");
$low_stock_products = $stmt->fetchAll();

// Get total sales for today
$stmt = $pdo->prepare("
    SELECT COUNT(*) as order_count, COALESCE(SUM(Total_Amount), 0) as total_sales
    FROM `Order`
    WHERE DATE(Order_Date) = CURDATE()
");
$stmt->execute();
$today_stats = $stmt->fetch();

// Get total products
$stmt = $pdo->query("SELECT COUNT(*) FROM Product");
$total_products = $stmt->fetchColumn();

// Get total customers
$stmt = $pdo->query("SELECT COUNT(*) FROM Customer");
$total_customers = $stmt->fetchColumn();

// Get total employees
$stmt = $pdo->query("SELECT COUNT(*) FROM Employee");
$total_employees = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'favicon.php'; ?>
    <title>Admin Dashboard - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .dashboard-page .dashboard-container {
            margin: 2rem auto;
            padding: 0;
        }
        .dashboard-page .dashboard-header {
            background-color: #f05537;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .dashboard-page .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .dashboard-page .stats-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-page .stats-icon {
            width: 48px;
            height: 48px;
            background-color: #f0553715;
            color: #f05537;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .dashboard-page .stats-value {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .dashboard-page .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        .dashboard-page .section-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dashboard-page .section-title {
            color: #f05537;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .dashboard-page .table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 500;
        }
        .dashboard-page .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .dashboard-page .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .dashboard-page .status-completed {
            background-color: #198754;
            color: white;
        }
        .dashboard-page .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .dashboard-page .product-image {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 4px;
        }
        .dashboard-page .stock-warning {
            color: #dc3545;
            font-weight: 500;
        }
        .dashboard-page .action-card {
            height: 100%;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
        }
        .dashboard-page .action-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-page .action-icon {
            font-size: 2rem;
            color: #f05537;
            margin-bottom: 1rem;
        }
        .dashboard-page .action-title {
            font-size: 1.25rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .dashboard-page .action-description {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            flex-grow: 1;
        }
        .dashboard-page .action-buttons {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="dashboard-page">
        <div class="container dashboard-container">
            <div class="dashboard-header">
                <h2 class="m-0">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <p class="mb-0">Here's what's happening today</p>
            </div>

            <!-- Stats Overview -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-cart-check fs-4"></i>
                        </div>
                        <div class="stats-value"><?php echo $today_stats['order_count']; ?></div>
                        <div class="stats-label">Orders Today</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                        <div class="stats-value">$<?php echo number_format($today_stats['total_sales'], 2); ?></div>
                        <div class="stats-label">Sales Today</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                        <div class="stats-value"><?php echo $total_customers; ?></div>
                        <div class="stats-label">Total Customers</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                        <div class="stats-value"><?php echo $total_products; ?></div>
                        <div class="stats-label">Total Products</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="section-card">
                        <h4 class="section-title">Quick Actions</h4>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="action-card section-card h-100">
                                    <div class="action-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <h5 class="action-title">Products</h5>
                                    <p class="action-description">Add, edit, and manage your product inventory</p>
                                    <div class="d-flex gap-2 action-buttons">
                                        <a href="add_product.php" class="btn btn-sm btn-outline-primary">Add New</a>
                                        <a href="manage_products.php" class="btn btn-sm btn-primary">Manage All</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="action-card section-card h-100">
                                    <div class="action-icon">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <h5 class="action-title">Employees</h5>
                                    <p class="action-description">Manage staff and their permissions</p>
                                    <div class="d-flex gap-2 action-buttons">
                                        <a href="employee_register.php" class="btn btn-sm btn-outline-primary">Add New</a>
                                        <a href="edit_employee.php" class="btn btn-sm btn-primary">Manage All</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="action-card section-card h-100">
                                    <div class="action-icon">
                                        <i class="bi bi-cart"></i>
                                    </div>
                                    <h5 class="action-title">Orders</h5>
                                    <p class="action-description">View and process customer orders</p>
                                    <div class="d-flex gap-2 action-buttons">
                                        <a href="manage_orders.php" class="btn btn-sm btn-primary">Manage Orders</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="action-card section-card h-100">
                                    <div class="action-icon">
                                        <i class="bi bi-gear"></i>
                                    </div>
                                    <h5 class="action-title">Settings</h5>
                                    <p class="action-description">Configure system settings</p>
                                    <div class="d-flex gap-2 action-buttons">
                                        <a href="settings.php" class="btn btn-sm btn-primary">Manage Settings</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Orders -->
                <div class="col-md-8">
                    <div class="section-card">
                        <h4 class="section-title">Recent Orders</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo str_pad($order['Order_ID'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($order['First_Name'] . ' ' . $order['Last_Name']); ?></td>
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
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="manage_orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Products -->
                <div class="col-md-4">
                    <div class="section-card">
                        <h4 class="section-title">Low Stock Alert</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($low_stock_products as $product): ?>
                                        <tr>
                                            <td class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($product['Image_Path'] ?? 'uploads/products/default.jpg'); ?>" 
                                                     class="product-image me-2" 
                                                     alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
                                                <div>
                                                    <div><?php echo htmlspecialchars($product['Product_Name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['Category_Name']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="stock-warning"><?php echo $product['Stock']; ?> left</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="manage_products.php" class="btn btn-sm btn-outline-primary">Manage Products</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 