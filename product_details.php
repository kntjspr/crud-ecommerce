<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET['id'];

// Get product details with category and ratings
$stmt = $pdo->prepare("
    SELECT p.*, c.Category_Name,
           (SELECT GROUP_CONCAT(Image_Path) FROM ProductImage pi WHERE pi.Product_ID = p.Product_ID) as Images,
           (SELECT AVG(Rating) FROM Review r WHERE r.Product_ID = p.Product_ID) as avg_rating,
           (SELECT COUNT(*) FROM Review r WHERE r.Product_ID = p.Product_ID) as review_count
    FROM Product p
    LEFT JOIN Category c ON p.Category_ID = c.Category_ID
    WHERE p.Product_ID = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Get related products from same category
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT Image_Path FROM ProductImage pi WHERE pi.Product_ID = p.Product_ID LIMIT 1) as Image_Path
    FROM Product p
    WHERE p.Category_ID = ? AND p.Product_ID != ?
    LIMIT 4
");
$stmt->execute([$product['Category_ID'], $product_id]);
$related_products = $stmt->fetchAll();

// Get product reviews
$stmt = $pdo->prepare("
    SELECT r.*, c.First_Name, c.Last_Name
    FROM Review r
    LEFT JOIN Customer c ON r.Customer_ID = c.Customer_ID
    WHERE r.Product_ID = ?
    ORDER BY r.Review_Date DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// Split images into array
$images = $product['Images'] ? explode(',', $product['Images']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'favicon.php'; ?>
    <title><?php echo htmlspecialchars($product['Product_Name']); ?> - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .product-page {
            margin: 2rem auto;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1rem;
        }
        .product-category {
            color: #f05537;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .product-price {
            font-size: 2rem;
            font-weight: 600;
            color: #f05537;
            margin-bottom: 1.5rem;
        }
        .product-description {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .stock-status {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .in-stock {
            color: #198754;
        }
        .low-stock {
            color: #ffc107;
        }
        .out-of-stock {
            color: #dc3545;
        }
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .thumbnail:hover {
            transform: scale(1.1);
        }
        .thumbnail.active {
            border: 2px solid #f05537;
        }
        .quantity-input {
            width: 100px;
        }
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
            padding: 0.75rem 2rem;
        }
        .btn-primary:hover {
            background-color: #d64426;
            border-color: #d64426;
        }
        .related-title {
            color: #f05537;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .related-product {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .related-product:hover {
            transform: translateY(-5px);
        }
        .related-image {
            width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 1rem;
        }
        .related-name {
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .related-price {
            color: #f05537;
            font-weight: 600;
        }
        .stars {
            color: #ffc107;
            font-size: 1.1rem;
        }
        .rating-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        .rating-count {
            font-size: 0.9rem;
        }
        .review-item {
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .review-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .reviewer-name {
            color: #333;
            margin-bottom: 0.25rem;
        }
        .review-date {
            font-size: 0.875rem;
        }
        .review-text {
            color: #666;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="product-page">
        <div class="container">
            <div class="product-card">
                <div class="row">
                    <!-- Product Images -->
                    <div class="col-md-6">
                        <img src="<?php echo htmlspecialchars($images[0] ?? 'uploads/products/default.jpg'); ?>" 
                             class="main-image" id="mainImage" alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
                        
                        <?php if (count($images) > 1): ?>
                            <div class="d-flex gap-2 mt-3">
                                <?php foreach($images as $index => $image): ?>
                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                         class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="changeImage('<?php echo htmlspecialchars($image); ?>', this)"
                                         alt="Product thumbnail">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Details -->
                    <div class="col-md-6">
                        <div class="product-category">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($product['Category_Name']); ?>
                        </div>
                        <h1 class="product-title"><?php echo htmlspecialchars($product['Product_Name']); ?></h1>
                        <div class="product-price">$<?php echo number_format($product['Price'], 2); ?></div>
                        <div class="product-rating mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stars">
                                    <?php
                                    $rating = round($product['avg_rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="bi bi-star-fill text-warning"></i>';
                                        } else {
                                            echo '<i class="bi bi-star text-warning"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="rating-value"><?php echo number_format($product['avg_rating'] ?? 0, 1); ?></span>
                                <span class="rating-count text-muted">(<?php echo $product['review_count'] ?? 0; ?> reviews)</span>
                            </div>
                        </div>
                        
                        <div class="stock-status mb-4">
                            <?php if($product['Stock'] > 10): ?>
                                <span class="in-stock"><i class="bi bi-check-circle"></i> In Stock</span>
                            <?php elseif($product['Stock'] > 0): ?>
                                <span class="low-stock"><i class="bi bi-exclamation-circle"></i> Low Stock - Only <?php echo $product['Stock']; ?> left</span>
                            <?php else: ?>
                                <span class="out-of-stock"><i class="bi bi-x-circle"></i> Out of Stock</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-description">
                            <?php echo nl2br(htmlspecialchars($product['Description'])); ?>
                        </div>

                        <?php if($product['Stock'] > 0): ?>
                            <form action="cart.php" method="POST" class="d-flex gap-3 align-items-center">
                                <input type="hidden" name="product_id" value="<?php echo $product['Product_ID']; ?>">
                                <input type="number" name="quantity" value="1" min="1" 
                                       max="<?php echo $product['Stock']; ?>" 
                                       class="form-control quantity-input">
                                <button type="submit" name="add_to_cart" class="btn btn-primary">
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <?php if(count($related_products) > 0): ?>
                <div class="product-card">
                    <h3 class="related-title">Related Products</h3>
                    <div class="row">
                        <?php foreach($related_products as $related): ?>
                            <div class="col-md-3 mb-4">
                                <a href="product_details.php?id=<?php echo $related['Product_ID']; ?>" 
                                   class="text-decoration-none">
                                    <div class="related-product">
                                        <img src="<?php echo htmlspecialchars($related['Image_Path'] ?? 'uploads/products/default.jpg'); ?>" 
                                             class="related-image" 
                                             alt="<?php echo htmlspecialchars($related['Product_Name']); ?>">
                                        <div class="related-name"><?php echo htmlspecialchars($related['Product_Name']); ?></div>
                                        <div class="related-price">$<?php echo number_format($related['Price'], 2); ?></div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <?php if(count($reviews) > 0): ?>
                <div class="product-card">
                    <h3 class="related-title">Customer Reviews</h3>
                    <div class="reviews-list">
                        <?php foreach($reviews as $review): ?>
                            <div class="review-item mb-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="reviewer-name fw-bold">
                                            <?php echo htmlspecialchars($review['First_Name'] . ' ' . $review['Last_Name']); ?>
                                        </div>
                                        <div class="review-date text-muted small">
                                            <?php echo date('F j, Y', strtotime($review['Review_Date'])); ?>
                                        </div>
                                    </div>
                                    <div class="stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $review['Rating']) {
                                                echo '<i class="bi bi-star-fill text-warning"></i>';
                                            } else {
                                                echo '<i class="bi bi-star text-warning"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="review-text">
                                    <?php echo nl2br(htmlspecialchars($review['Review_Text'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function changeImage(src, thumbnail) {
            document.getElementById('mainImage').src = src;
            // Remove active class from all thumbnails
            document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
            // Add active class to clicked thumbnail
            thumbnail.classList.add('active');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 