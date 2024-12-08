<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Handle populate products action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'populate') {
    try {
        $pdo->beginTransaction();

        // Ensure categories exist
        $categories = ['Running', 'Basketball', 'Casual', 'Formal'];

        foreach ($categories as $cat) {
            $stmt = $pdo->prepare("
                INSERT INTO Category (Category_Name) 
                SELECT ? WHERE NOT EXISTS (
                    SELECT 1 FROM Category WHERE Category_Name = ?
                )
            ");
            $stmt->execute([$cat, $cat]);
        }

        // Get category IDs
        $category_ids = [];
        $stmt = $pdo->query("SELECT Category_ID, Category_Name FROM Category");
        while ($row = $stmt->fetch()) {
            $category_ids[$row['Category_Name']] = $row['Category_ID'];
        }

        // Sample products
        $products = [
            [
                'name' => 'Air Speed Runner',
                'description' => 'Lightweight running shoes with advanced cushioning technology.',
                'price' => 129.99,
                'stock' => 50,
                'category' => 'Running'
            ],
            [
                'name' => 'Pro Court Elite',
                'description' => 'Professional basketball shoes with ankle support and grip.',
                'price' => 159.99,
                'stock' => 40,
                'category' => 'Basketball'
            ],
            [
                'name' => 'Daily Comfort Walk',
                'description' => 'Comfortable everyday shoes for casual wear.',
                'price' => 79.99,
                'stock' => 75,
                'category' => 'Casual'
            ],
            [
                'name' => 'Classic Oxford',
                'description' => 'Elegant leather dress shoes for formal occasions.',
                'price' => 149.99,
                'stock' => 30,
                'category' => 'Formal'
            ],
            [
                'name' => 'Trail Blazer X',
                'description' => 'Durable trail running shoes for rough terrain.',
                'price' => 139.99,
                'stock' => 45,
                'category' => 'Running'
            ]
        ];

        // Insert products
        $stmt = $pdo->prepare("
            INSERT INTO Product (Product_Name, Description, Price, Stock, Category_ID)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($products as $product) {
            $stmt->execute([
                $product['name'],
                $product['description'],
                $product['price'],
                $product['stock'],
                $category_ids[$product['category']]
            ]);
            
            // Get the product ID
            $product_id = $pdo->lastInsertId();
            
            // Add a default image for the product
            $stmt2 = $pdo->prepare("
                INSERT INTO ProductImage (Product_ID, Image_Path)
                VALUES (?, ?)
            ");
            $stmt2->execute([
                $product_id,
                'uploads/default_shoe.png'
            ]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = "Sample products have been added successfully!";
        header("Location: settings.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Failed to populate products: " . $e->getMessage();
        header("Location: settings.php");
        exit();
    }
}

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_store':
                    $stmt = $pdo->prepare("
                        UPDATE Settings 
                        SET store_name = ?, 
                            store_email = ?,
                            store_phone = ?,
                            store_address = ?,
                            tax_rate = ?
                        WHERE id = 1
                    ");
                    $stmt->execute([
                        $_POST['store_name'],
                        $_POST['store_email'],
                        $_POST['store_phone'],
                        $_POST['store_address'],
                        $_POST['tax_rate']
                    ]);
                    $_SESSION['success_message'] = "Store settings updated successfully!";
                    break;

                case 'update_shipping':
                    $stmt = $pdo->prepare("
                        UPDATE Settings 
                        SET shipping_fee = ?,
                            free_shipping_threshold = ?
                        WHERE id = 1
                    ");
                    $stmt->execute([
                        $_POST['shipping_fee'],
                        $_POST['free_shipping_threshold']
                    ]);
                    $_SESSION['success_message'] = "Shipping settings updated successfully!";
                    break;

                case 'maintenance_mode':
                    $stmt = $pdo->prepare("
                        UPDATE Settings 
                        SET maintenance_mode = NOT maintenance_mode
                        WHERE id = 1
                    ");
                    $stmt->execute();
                    $_SESSION['success_message'] = "Maintenance mode toggled successfully!";
                    break;
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Failed to update settings: " . $e->getMessage();
    }
    
    header("Location: settings.php");
    exit();
}

// Get current settings
$stmt = $pdo->query("SELECT * FROM Settings WHERE id = 1");
$settings = $stmt->fetch();

// Get system statistics
$stats = [
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM `Order`")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM Product")->fetchColumn(),
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM Customer")->fetchColumn(),
    'total_employees' => $pdo->query("SELECT COUNT(*) FROM Employee")->fetchColumn(),
    'total_sales' => $pdo->query("SELECT COALESCE(SUM(Total_Amount), 0) FROM `Order`")->fetchColumn(),
    'avg_order_value' => $pdo->query("SELECT COALESCE(AVG(Total_Amount), 0) FROM `Order`")->fetchColumn()
];

// Get database information
$db_info = [
    'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
    'client_version' => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),
    'connection_status' => $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS),
    'driver_name' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .settings-page {
            margin: 2rem auto;
        }
        .settings-header {
            background-color: #f05537;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .settings-card {
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
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
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
        .stats-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .stats-label {
            color: #666;
            font-size: 0.9rem;
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
        .form-control:focus {
            border-color: #f05537;
            box-shadow: 0 0 0 0.2rem rgba(240, 85, 55, 0.25);
        }
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
        }
        .btn-primary:hover {
            background-color: #d64426;
            border-color: #d64426;
        }
        .maintenance-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="settings-page">
        <div class="container">
            <div class="settings-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="m-0">System Settings</h2>
                    <p class="mb-0">Manage your store configuration and view system information</p>
                </div>
                <div>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="maintenance_mode">
                        <button type="submit" class="btn btn-light maintenance-badge">
                            <i class="bi bi-gear"></i>
                            <?php echo $settings['maintenance_mode'] ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode'; ?>
                        </button>
                    </form>
                </div>
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

            <!-- System Statistics -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h4 class="section-title">System Statistics</h4>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div class="stats-value"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="stats-label">Total Orders</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stats-value"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="stats-label">Total Products</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stats-value"><?php echo number_format($stats['total_customers']); ?></div>
                        <div class="stats-label">Total Customers</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div class="stats-value"><?php echo number_format($stats['total_employees']); ?></div>
                        <div class="stats-label">Total Employees</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stats-value">$<?php echo number_format($stats['total_sales'], 2); ?></div>
                        <div class="stats-label">Total Sales</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="stats-value">$<?php echo number_format($stats['avg_order_value'], 2); ?></div>
                        <div class="stats-label">Avg. Order Value</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Store Settings -->
                <div class="col-md-6">
                    <div class="settings-card">
                        <h4 class="section-title">Store Settings</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_store">
                            <div class="mb-3">
                                <label class="form-label">Store Name</label>
                                <input type="text" class="form-control" name="store_name" 
                                       value="<?php echo htmlspecialchars($settings['store_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Store Email</label>
                                <input type="email" class="form-control" name="store_email" 
                                       value="<?php echo htmlspecialchars($settings['store_email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Store Phone</label>
                                <input type="tel" class="form-control" name="store_phone" 
                                       value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Store Address</label>
                                <textarea class="form-control" name="store_address" rows="3" required><?php 
                                    echo htmlspecialchars($settings['store_address'] ?? ''); 
                                ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tax Rate (%)</label>
                                <input type="number" step="0.01" class="form-control" name="tax_rate" 
                                       value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0'); ?>" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Store Settings</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Shipping Settings -->
                <div class="col-md-6">
                    <div class="settings-card">
                        <h4 class="section-title">Shipping Settings</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_shipping">
                            <div class="mb-3">
                                <label class="form-label">Base Shipping Fee ($)</label>
                                <input type="number" step="0.01" class="form-control" name="shipping_fee" 
                                       value="<?php echo htmlspecialchars($settings['shipping_fee'] ?? '0'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Free Shipping Threshold ($)</label>
                                <input type="number" step="0.01" class="form-control" name="free_shipping_threshold" 
                                       value="<?php echo htmlspecialchars($settings['free_shipping_threshold'] ?? '0'); ?>" required>
                                <small class="text-muted">Orders above this amount qualify for free shipping</small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Shipping Settings</button>
                            </div>
                        </form>
                    </div>

                    <!-- Database Information -->
                    <div class="settings-card mt-4">
                        <h4 class="section-title">System Information</h4>
                        <div class="info-label">Database Server Version</div>
                        <div class="info-value"><?php echo htmlspecialchars($db_info['server_version']); ?></div>
                        
                        <div class="info-label">Database Client Version</div>
                        <div class="info-value"><?php echo htmlspecialchars($db_info['client_version']); ?></div>
                        
                        <div class="info-label">Connection Status</div>
                        <div class="info-value"><?php echo htmlspecialchars($db_info['connection_status']); ?></div>
                        
                        <div class="info-label">Driver</div>
                        <div class="info-value"><?php echo htmlspecialchars($db_info['driver_name']); ?></div>
                        
                        <div class="info-label">PHP Version</div>
                        <div class="info-value"><?php echo phpversion(); ?></div>
                        
                        <div class="info-label">Server Software</div>
                        <div class="info-value"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE']); ?></div>
                    </div>

                    <!-- Database Management -->
                    <div class="settings-card">
                        <h4 class="section-title">Database Management</h4>
                        
                        <!-- Reset Database -->
                        <div class="mb-4">
                            <h5>Reset Database</h5>
                            <p class="text-muted">Reset the database to its initial state. This will remove all data and recreate the tables.</p>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetModal">
                                Reset Database
                            </button>
                        </div>

                        <!-- Populate Products -->
                        <div class="mb-4">
                            <h5>Populate Sample Products</h5>
                            <p class="text-muted">Add sample products to the database for testing purposes.</p>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#populateModal">
                                Populate Products
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="content-card">
                        <div class="settings-control">
                            <div class="bg-white p-4 rounded shadow-sm">
                                <div class="mb-4">
                                    <h5>Credits</h5>
                                    <p class="text-muted">Final project for Fundamentals of Database Systems - Team Ciderella ♥</p>
                                </div>

                                <div class="mb-4">
                                    <h6>Members</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li>Kent Jasper C. Sisi</li>
                                        <li>Harvie C. Babuyo</li>
                                        <li>Precious Gamalo</li>
                                        <li>Richter Anthony Yap</li>
                                        <li>Thomas Gabriel Martinez</li>
                                    </ul>
                                </div>

                                <div>
                                    <h6>Source Code</h6>
                                    <a href="https://github.com/kntjspr/crud-ecommerce" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-github me-2"></i>View on GitHub
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Database Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Warning: Database Reset
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>This action will:</strong>
                        <ul class="mb-0">
                            <li>Delete ALL data from the database</li>
                            <li>Remove ALL products, orders, and user accounts</li>
                            <li>Reset ALL settings to default</li>
                            <li>Log out ALL users</li>
                        </ul>
                    </div>
                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                    <p>Type "RESET" in the field below to confirm:</p>
                    <input type="text" id="resetConfirm" class="form-control" placeholder="Type RESET here">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmReset" disabled>
                        <i class="bi bi-trash3"></i> Reset Database
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Populate Products Modal -->
    <div class="modal fade" id="populateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Populate Sample Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <h6>This will:</h6>
                        <ul class="mb-0">
                            <li>Add sample products to the database</li>
                            <li>Create product categories if they don't exist</li>
                            <li>Add sample product images</li>
                        </ul>
                    </div>
                    <form id="populateForm" method="POST">
                        <input type="hidden" name="action" value="populate">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmPopulate" required>
                            <label class="form-check-label" for="confirmPopulate">
                                I understand that this will add sample data to the database
                            </label>
                        </div>
                        <button type="submit" class="btn btn-warning" disabled id="populateButton">
                            Populate Products
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle reset confirmation
        document.getElementById('resetConfirm').addEventListener('input', function() {
            document.getElementById('confirmReset').disabled = this.value !== 'RESET';
        });

        // Handle reset button click
        document.getElementById('confirmReset').addEventListener('click', function() {
            window.location.href = 'database_reset.php';
        });

        // Populate Products Form Validation
        document.getElementById('confirmPopulate').addEventListener('change', function() {
            document.getElementById('populateButton').disabled = !this.checked;
        });
    </script>
</body>
</html> 