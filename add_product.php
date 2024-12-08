<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM Category")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insert product
        $stmt = $pdo->prepare("
            INSERT INTO Product (Product_Name, Description, Price, Stock, Category_ID) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock'],
            $_POST['category']
        ]);
        $product_id = $pdo->lastInsertId();

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === 0) {
                    $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $destination = $upload_dir . $new_filename;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO ProductImage (Product_ID, Image_Path) 
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$product_id, $destination]);
                    }
                }
            }
        }

        $pdo->commit();
        $_SESSION['success_message'] = "Product added successfully!";
        header("Location: manage_products.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to add product: " . $e->getMessage();
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
        body {
            background-color: #f0f0f0;
        }
        .add-page .add-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .add-page .add-header {
            background-color: #f05537;
            color: white;
            padding: 1rem;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .add-page .add-form {
            padding: 2rem;
        }
        .add-page .form-label {
            color: #666;
            font-weight: normal;
            margin-bottom: 0.5rem;
        }
        .add-page .form-control {
            border: 1px solid #ddd;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .add-page .btn-add {
            background-color: #f05537;
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 4px;
            color: white;
        }
        .add-page .btn-add:hover {
            background-color: #e04527;
        }
        .add-page .section-title {
            color: #f05537;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .add-page .image-preview {
            max-width: 150px;
            max-height: 150px;
            margin: 0.5rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="add-page">
        <div class="add-container">
            <div class="add-header">
                <h2 class="m-0">Add New Product</h2>
            </div>

            <div class="add-form">
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <h4 class="section-title">Product Information</h4>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" name="stock" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-control" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['Category_ID']; ?>">
                                            <?php echo htmlspecialchars($category['Category_Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <h4 class="section-title">Product Images</h4>
                    
                    <div class="mb-3">
                        <label class="form-label">Upload Images</label>
                        <input type="file" class="form-control" name="images[]" accept="image/*" multiple required>
                        <div class="form-text">You can select multiple images. Supported formats: JPG, PNG, GIF</div>
                    </div>

                    <div id="imagePreview" class="mb-3 d-flex flex-wrap"></div>

                    <div class="d-flex justify-content-between">
                        <a href="manage_products.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-add">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
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