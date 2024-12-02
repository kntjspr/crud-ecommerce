<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data based on type
if (isset($_SESSION['customer_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Customer WHERE Customer_ID = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $user = $stmt->fetch();
    $userType = 'customer';
} else {
    $stmt = $pdo->prepare("SELECT * FROM Employee WHERE Employee_ID = ?");
    $stmt->execute([$_SESSION['employee_id']]);
    $user = $stmt->fetch();
    $userType = 'employee';
}

if (!$user) {
    header("Location: login.php?error=invalid_user");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3>Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Name:</strong>
                                <?php echo htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong>
                                <?php echo htmlspecialchars($user['Email']); ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Phone:</strong>
                                <?php echo htmlspecialchars($user['Phone_Number']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Gender:</strong>
                                <?php echo htmlspecialchars($user['Gender']); ?>
                            </div>
                        </div>

                        <?php if($userType === 'employee'): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>SSS Number:</strong>
                                    <?php echo htmlspecialchars($user['SSS_Number']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>TIN:</strong>
                                    <?php echo htmlspecialchars($user['TIN']); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Pag-IBIG:</strong>
                                    <?php echo htmlspecialchars($user['Pag_IBIG']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>PhilHealth:</strong>
                                    <?php echo htmlspecialchars($user['PhilHealth']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 