<?php
session_start();
require_once 'config/database.php';

// Add error handling for database connection
if (!isset($pdo)) {
    die('
        <div style="text-align: center; margin-top: 50px;">
            <h1>Database Connection Error</h1>
            <p>Sorry, we are experiencing technical difficulties. Please check if MYSQL is turned on and is publicly accessible.</p>
        </div>
    ');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoepee - Your One-Stop Shoe Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #f44336;
            --secondary-color: #ff8a80;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
        }
        
        .hero-section {
            background-color: #f5f5f5;
            padding: 40px;
            margin: 40px auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 1450px;
        }
        
        .hero-section h1 {
            color: var(--primary-color);
            font-size: 3.5rem;
            margin-bottom: 20px;
        }
        
        .hero-section p {
            color: #666;
            font-size: 1.5rem;
        }
        
        .hero-image {
            max-width: 400px;
            float: right;
            position: relative;
            z-index: 2;
        }
        
        .circle-bg {
            width: 300px;
            height: 300px;
            background-color: #FFE4E1;
            border-radius: 50%;
            position: absolute;
            right: 60px;
            top: 50%;
            transform: translateY(30%);
            z-index: 1;
        }
        
        .product-card {
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-price {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: bold;
        }
        
        .rating {
            color: #ffd700;
        }
        
        .section-title {
            color: var(--primary-color);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="uploads/shoepee_logo.png" width="30" height="30" class="d-inline-block align-top" alt="">
                Shoepee
            </a>
            <div class="d-flex align-items-center">
                <a href="products.php" class="text-white text-decoration-none mx-3">Products</a>
                <a href="admin_dashboard.php" class="text-white text-decoration-none mx-3">Admin Dashboard</a>
                <a href="manage_products.php" class="text-white text-decoration-none mx-3">Manage Products</a>
                <a href="register_employee.php" class="text-white text-decoration-none mx-3">Register Employee</a>
                <div class="ms-3">
                    <a href="profile.php" class="text-white text-decoration-none">Profile</a> |
                    <a href="logout.php" class="text-white text-decoration-none">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Welcome to Shoepee</h1>
                    <p>Find your perfect pair of shoe!</p>
                </div>
                <div class="col-md-6">
                    <div class="position-relative">
                        <div class="circle-bg"></div>
                        <img src="uploads/hero_shoe.png" alt="Hero Shoe" class="hero-image">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 class="section-title">Top Products</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("
                SELECT p.*, 
                       (SELECT Image_Path FROM ProductImage pi 
                        WHERE pi.Product_ID = p.Product_ID 
                        LIMIT 1) as Image_Path,
                       (SELECT AVG(Rating) FROM Review r 
                        WHERE r.Product_ID = p.Product_ID) as avg_rating,
                       (SELECT COUNT(*) FROM Review r 
                        WHERE r.Product_ID = p.Product_ID) as review_count
                FROM Product p 
                ORDER BY avg_rating DESC 
                LIMIT 4
            ");
            while($product = $stmt->fetch()){
            ?>
                <div class="col-md-3 mb-4">
                    <a href="product_details.php?id=<?php echo $product['Product_ID']; ?>" 
                       class="text-decoration-none">
                        <div class="card product-card">
                            <img src="<?php echo htmlspecialchars($product['Image_Path'] ?? 'uploads/products/default.jpg'); ?>" 
                                 class="card-img-top product-image" 
                                 alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['Product_Name']); ?></h5>
                                <p class="product-price">$<?php echo number_format($product['Price'], 2); ?></p>
                                <div class="rating">
                                    <?php
                                    $rating = round($product['avg_rating'] ?? 0);
                                    for($i = 0; $i < 5; $i++) {
                                        echo $i < $rating ? '★' : '☆';
                                    }
                                    echo " (" . ($product['review_count'] ?? 0) . " reviews)";
                                    ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
