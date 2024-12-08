<?php
session_start();
require_once 'config/database.php';

// Check if product ID is provided in query string
if(!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Get product details with category information
$stmt = $pdo->prepare("
    SELECT p.*, c.Category_Name 
    FROM Product p 
    LEFT JOIN Category c ON p.Category_ID = c.Category_ID 
    WHERE p.Product_ID = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    header("Location: products.php");
    exit();
}

// Get product images
$stmt = $pdo->prepare("SELECT * FROM ProductImage WHERE Product_ID = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();

// Fetch product reviews
$stmt = $pdo->prepare("
    SELECT r.*, c.Username 
    FROM Review r 
    JOIN Customer c ON r.Customer_ID = c.Customer_ID 
    WHERE r.Product_ID = ? 
    ORDER BY r.Review_Date DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// Handle review submission
if(isset($_POST['submit_review']) && isset($_SESSION['customer_id'])) {
    $rating = (int)$_POST['rating'];
    $review_text = $_POST['review_text'];
    $customer_id = $_SESSION['customer_id'];
    
    $stmt = $pdo->prepare("INSERT INTO Review (Product_ID, Customer_ID, Rating, Review_Text, Review_Date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$product_id, $customer_id, $rating, $review_text]);
    
    header("Location: product_details.php?id=" . $product_id);
    exit();
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Validate quantity
    if ($quantity > 0 && $quantity <= $product['Stock']) {
        // Add to cart or update quantity if already exists
        if (isset($_SESSION['cart'][$product_id])) {
            // Check if new total quantity exceeds stock
            $new_quantity = $_SESSION['cart'][$product_id] + $quantity;
            if ($new_quantity <= $product['Stock']) {
                $_SESSION['cart'][$product_id] = $new_quantity;
                $_SESSION['success_message'] = "Cart updated successfully";
            } else {
                $_SESSION['error_message'] = "Cannot add more than available stock";
            }
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
            $_SESSION['success_message'] = "Product added to cart";
        }
        
        // Redirect to prevent form resubmission
        header("Location: cart.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Invalid quantity";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['Product_Name']); ?> - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        .thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            cursor: pointer;
            margin: 5px;
            border: 2px solid transparent;
        }
        .thumbnail.active {
            border-color: #0d6efd;
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
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['customer_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">Cart</a>
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
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['Product_Name']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-6">
                <?php if(!empty($images)): ?>
                    <div id="productCarousel" class="carousel slide mb-3" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach($images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($image['Image_Path']); ?>" 
                                         class="d-block product-image" 
                                         alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if(count($images) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if(count($images) > 1): ?>
                        <div class="d-flex flex-wrap justify-content-center">
                            <?php foreach($images as $index => $image): ?>
                                <img src="<?php echo htmlspecialchars($image['Image_Path']); ?>" 
                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                     data-bs-target="#productCarousel"
                                     data-bs-slide-to="<?php echo $index; ?>"
                                     alt="Thumbnail">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <img src="uploads/products/default.jpg" class="product-image" alt="Default Product Image">
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['Product_Name']); ?></h1>
                <p class="text-muted">Category: <?php echo htmlspecialchars($product['Category_Name']); ?></p>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>
                        
                        <h5>Price</h5>
                        <p class="h3 text-primary">$<?php echo number_format($product['Price'], 2); ?></p>
                        
                        <h5>Stock Status</h5>
                        <?php if($product['Stock'] > 0): ?>
                            <p class="text-success">In Stock (<?php echo $product['Stock']; ?> available)</p>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="product_id" value="<?php echo $product['Product_ID']; ?>">
                                <div class="row g-3 align-items-center">
                                    <div class="col-auto">
                                        <input type="number" 
                                               name="quantity" 
                                               class="form-control" 
                                               value="1" 
                                               min="1" 
                                               max="<?php echo $product['Stock']; ?>" 
                                               required>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" 
                                                name="add_to_cart" 
                                                class="btn btn-primary"
                                                <?php echo $product['Stock'] <= 0 ? 'disabled' : ''; ?>>
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <p class="text-danger">Out of Stock</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row">
            <div class="col-md-8">
                <h3>Customer Reviews</h3>
                <?php if(isset($_SESSION['customer_id'])): ?>
                    <form action="" method="POST" class="mb-4">
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating</label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Very Good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Fair</option>
                                <option value="1">1 - Poor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="review_text" class="form-label">Your Review</label>
                            <textarea class="form-control" id="review_text" name="review_text" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please <a href="login.php">login</a> to write a review.
                    </div>
                <?php endif; ?>

                <?php foreach($reviews as $review): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><?php echo htmlspecialchars($review['Username']); ?></h5>
                                <div class="text-warning">
                                    <?php for($i = 0; $i < $review['Rating']; $i++) echo '★'; ?>
                                    <?php for($i = $review['Rating']; $i < 5; $i++) echo '☆'; ?>
                                </div>
                            </div>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($review['Review_Text'])); ?></p>
                            <small class="text-muted">Posted on <?php echo date('M d, Y', strtotime($review['Review_Date'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize thumbnails
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', function() {
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Update thumbnail active state when carousel slides
        document.getElementById('productCarousel')?.addEventListener('slid.bs.carousel', function (event) {
            document.querySelectorAll('.thumbnail').forEach((thumb, index) => {
                thumb.classList.toggle('active', index === event.to);
            });
        });
    </script>
</body>
</html> 