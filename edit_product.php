<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM Category")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update product
        $stmt = $pdo->prepare("
            UPDATE Product SET 
            Product_Name = :name,
            Description = :description,
            Price = :price,
            Stock = :stock,
            Category_ID = :category
            WHERE Product_ID = :id
        ");

        $stmt->execute([
            ':name' => $_POST['name'],
            ':description' => $_POST['description'],
            ':price' => $_POST['price'],
            ':stock' => $_POST['stock'],
            ':category' => $_POST['category'],
            ':id' => $product_id
        ]);

        $_SESSION['success_message'] = "Product updated successfully!";
        header("Location: manage_products.php");
        exit();
    } catch (Exception $e) {
        $error_message = "Failed to update product: " . $e->getMessage();
    }
}

// Get product data
$stmt = $pdo->prepare("SELECT * FROM Product WHERE Product_ID = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: manage_products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3>Edit Product</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($product['Product_Name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3" required><?php 
                                    echo htmlspecialchars($product['Description']); 
                                ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" class="form-control" name="price" step="0.01" 
                                       value="<?php echo $product['Price']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" name="stock" 
                                       value="<?php echo $product['Stock']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category" required>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['Category_ID']; ?>"
                                            <?php echo $category['Category_ID'] == $product['Category_ID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['Category_Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="manage_products.php" class="btn btn-secondary">Back</a>
                                <button type="submit" class="btn btn-primary">Update Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 