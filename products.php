<?php
session_start();
require_once 'config/database.php';

// Get all products with their first image
$stmt = $pdo->query("
    SELECT p.*, 
           (SELECT Image_Path FROM ProductImage pi 
            WHERE pi.Product_ID = p.Product_ID 
            LIMIT 1) as Image_Path
    FROM Product p 
    ORDER BY p.Product_ID DESC
");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .card {
            height: 100%;
        }
    </style>
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
                        <a class="nav-link active" href="products.php">Products</a>
                    </li>
                    <?php if(isset($_SESSION['employee_id'])): ?>
                        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_products.php">Manage Products</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="employee_register.php">Register Employee</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['employee_id']) || isset($_SESSION['customer_id'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Our Products</h1>
        <div class="row">
            <?php foreach($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <?php if($product['Image_Path']): ?>
                            <img src="<?php echo htmlspecialchars($product['Image_Path']); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
                        <?php else: ?>
                            <img src="uploads/products/default.jpg" class="card-img-top product-image" alt="Default Product Image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['Product_Name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($product['Description']); ?></p>
                            <p class="card-text">
                                <strong>Price:</strong> $<?php echo number_format($product['Price'], 2); ?>
                            </p>
                            <p class="card-text">
                                <strong>Stock:</strong> <?php echo $product['Stock']; ?> units
                            </p>
                            <a href="product_details.php?id=<?php echo $product['Product_ID']; ?>" class="btn btn-primary">View Details</a>
                            <?php if(isset($_SESSION['customer_id'])): ?>
                                <form method="POST" action="cart.php" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo $product['Product_ID']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-success">Add to Cart</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 