<?php
session_start();
require_once 'config/database.php';

// Add search functionality to the existing query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];

if ($search !== '') {
    $where_clause = "WHERE p.Product_Name LIKE ? OR p.Description LIKE ? OR c.Category_Name LIKE ?";
    $search_term = "%{$search}%";
    $params = [$search_term, $search_term, $search_term];
}

$stmt = $pdo->prepare("
    SELECT p.*, c.Category_Name,
           (SELECT Image_Path FROM ProductImage pi WHERE pi.Product_ID = p.Product_ID LIMIT 1) as Image_Path
    FROM Product p
    LEFT JOIN Category c ON p.Category_ID = c.Category_ID
    $where_clause
    ORDER BY p.Product_ID DESC
");
$stmt->execute($params);
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
        body {
            background-color: #f0f0f0;
        }
        .products-page {
            padding: 2rem 0;
        }
        .page-title {
            color: #f05537;
            font-size: 2rem;
            margin-bottom: 2rem;
        }
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
            color: #f05537;
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .product-rating {
            color: #f05537;
        }
        .rating-count {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="products-page">
        <div class="container">
            <h1 class="page-title">Our Products</h1>
            
            <div class="product-grid">
                <?php foreach($products as $product): ?>
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
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 