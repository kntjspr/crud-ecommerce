<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Add this code block after session_start() and before the SELECT query
if (isset($_POST['delete_product'])) {
    $productId = $_POST['product_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Delete from Transaction first
        $stmt = $pdo->prepare("DELETE FROM Transaction WHERE Product_ID = ?");
        $stmt->execute([$productId]);
        
        // Delete from OrderItem
        $stmt = $pdo->prepare("DELETE FROM OrderItem WHERE Product_ID = ?");
        $stmt->execute([$productId]);
        
        // Delete from Cart if exists
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE Product_ID = ?");
        $stmt->execute([$productId]);
        
        // Delete product images
        $stmt = $pdo->prepare("DELETE FROM ProductImage WHERE Product_ID = ?");
        $stmt->execute([$productId]);
        
        // Delete the product
        $stmt = $pdo->prepare("DELETE FROM Product WHERE Product_ID = ?");
        $stmt->execute([$productId]);
        
        $pdo->commit();
        $_SESSION['success_message'] = "Product deleted successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['success_message'] = "Error deleting product: " . $e->getMessage();
    }
    
    header("Location: manage_products.php");
    exit();
}

// Get all products with their first image
$stmt = $pdo->query("
    SELECT p.*, c.Category_Name,
           (SELECT Image_Path FROM ProductImage pi 
            WHERE pi.Product_ID = p.Product_ID 
            LIMIT 1) as Image_Path
    FROM Product p 
    LEFT JOIN Category c ON p.Category_ID = c.Category_ID
    ORDER BY p.Product_ID DESC
");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'favicon.php'; ?>
    <title>Manage Products - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f0f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .manage-page .manage-content {
            padding: 2rem;
        }
        .manage-page .btn-add {
            background-color: white;
            color: #f05537;
            border: none;
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 4px;
        }
        .manage-page .btn-add:hover {
            background-color: #f8f9fa;
            color: #e04527;
        }
        .manage-page .product-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        .manage-page .table {
            margin-bottom: 0;
        }
        .manage-page .table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 500;
        }
        .manage-page .btn-edit {
            background-color: #f05537;
            border: none;
            color: white;
        }
        .manage-page .btn-edit:hover {
            background-color: #e04527;
        }
        .manage-page .btn-delete {
            background-color: #dc3545;
            border: none;
        }
        .manage-page .btn-delete:hover {
            background-color: #bb2d3b;
        }
        .footer {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="manage-page">
        <div class="container">
            <div class="manage-container">
                <div class="manage-header">
                    <h2 class="m-0">Manage Products</h2>
                    <a href="add_product.php" class="btn btn-add">Add New Product</a>
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
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $product): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($product['Image_Path'] ?? 'uploads/products/default.jpg'); ?>" 
                                                 class="product-image" 
                                                 alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($product['Product_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['Category_Name']); ?></td>
                                        <td>$<?php echo number_format($product['Price'], 2); ?></td>
                                        <td><?php echo $product['Stock']; ?></td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product['Product_ID']; ?>" 
                                               class="btn btn-edit btn-sm">Edit</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['Product_ID']; ?>">
                                                <button type="submit" name="delete_product" 
                                                        class="btn btn-delete btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
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
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = `delete_product.php?id=${productId}`;
            }
        }
    </script>
</body>
</html> 