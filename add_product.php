<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/products/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image_paths = [];

    // Handle image uploads
    if (!empty($_FILES['images']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['images']['type'][$key];
                $file_size = $_FILES['images']['size'][$key];
                
                if (!in_array($file_type, $allowed_types)) {
                    $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG and GIF are allowed.";
                    break;
                }
                
                if ($file_size > $max_size) {
                    $_SESSION['error_message'] = "File size too large. Maximum size is 5MB.";
                    break;
                }
                
                $file_name = uniqid() . '_' . $_FILES['images']['name'][$key];
                $destination = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $image_paths[] = $destination;
                }
            }
        }
    }

    if (empty($product_name) || empty($description) || $price <= 0 || $stock < 0) {
        $_SESSION['error_message'] = "Please fill all fields correctly";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert product
            $stmt = $pdo->prepare("INSERT INTO Product (Product_Name, Description, Price, Stock) VALUES (?, ?, ?, ?)");
            $stmt->execute([$product_name, $description, $price, $stock]);
            $product_id = $pdo->lastInsertId();
            
            // Insert image paths
            if (!empty($image_paths)) {
                $stmt = $pdo->prepare("INSERT INTO ProductImage (Product_ID, Image_Path) VALUES (?, ?)");
                foreach ($image_paths as $path) {
                    $stmt->execute([$product_id, $path]);
                }
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = "Product added successfully";
            header("Location: manage_products.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error adding product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 10px;
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
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Manage Products</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Add New Product</h1>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-4" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="product_name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="product_name" name="product_name" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
            </div>
            
            <div class="mb-3">
                <label for="price" class="form-label">Price ($)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="mb-3">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
            </div>

            <div class="mb-3">
                <label for="images" class="form-label">Product Images</label>
                <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple required>
                <div id="imagePreview" class="mt-2 d-flex flex-wrap"></div>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Add Product</button>
                <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            [...e.target.files].forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        });
    </script>
</body>
</html> 