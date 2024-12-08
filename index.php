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
    <title>Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #f05537;
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

        /* Product section styling */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 0;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: contain;
            margin-bottom: 1rem;
        }
        .product-name {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .product-price {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .product-rating {
            color: var(--primary-color);
        }
        .rating-count {
            color: #666;
            font-size: 0.9rem;
        }
        .page-title {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Welcome to Shoepee</h1>
                    <p>Find your perfect pair of shoes!</p>
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
        <h2 class="page-title">Top Products</h2>
        <div class="product-grid">
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
                <a href="product_details.php?id=<?php echo $product['Product_ID']; ?>" class="product-card">
                    <img 
                        src="<?php echo htmlspecialchars($product['Image_Path'] ?? 'uploads/products/default.jpg'); ?>" 
                        class="product-image" 
                        alt="<?php echo htmlspecialchars($product['Product_Name']); ?>"
                    >
                    <h2 class="product-name"><?php echo htmlspecialchars($product['Product_Name']); ?></h2>
                    <div class="product-price">$<?php echo number_format($product['Price'], 2); ?></div>
                    <div class="product-rating">
                        <?php
                        $rating = round($product['avg_rating'] ?? 0);
                        for($i = 0; $i < 5; $i++) {
                            echo $i < $rating ? '★' : '☆';
                        }
                        ?>
                        <span class="rating-count">(<?php echo $product['review_count'] ?? 0; ?> reviews)</span>
                    </div>
                </a>
            <?php } ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
