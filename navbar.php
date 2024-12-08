<?php
// Get cart count if user is logged in as customer
$cart_count = 0;
if (isset($_SESSION['customer_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Cart WHERE Customer_ID = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $cart_count = $stmt->fetchColumn();
}
?>

<!-- Add Bootstrap Icons CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

<nav class="navbar navbar-expand-lg" style="background-color: #f05537;">
    <div class="container">
        <a class="navbar-brand text-white d-flex align-items-center" href="index.php">
            <img src="uploads/shoepee_logo.png" width="40" height="40" class="me-2" alt="Shoepee Logo">
            Shoepee
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-3">
                <li class="nav-item">
                    <a class="nav-link text-white" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="products.php">Products</a>
                </li>
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="admin_dashboard.php">Admin</a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Search Form -->
            <form class="d-flex flex-grow-1 mx-5" action="products.php" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" name="search" placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                           style="border-radius: 4px 0 0 4px; padding: 12px 20px;">
                    <button class="btn btn-light d-flex align-items-center" type="submit" 
                            style="border-radius: 0 4px 4px 0; padding: 0 20px;">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- User Actions -->
            <?php if(isset($_SESSION['customer_id']) || isset($_SESSION['employee_id'])): ?>
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['customer_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="cart.php">
                                <i class="bi bi-cart3"></i>
                                <?php if(isset($cart_count) && $cart_count > 0): ?>
                                    <span class="badge bg-danger"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="profile.php" title="Profile">
                            <i class="bi bi-person-circle"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="logout.php" title="Logout">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="register.php">Register</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
.navbar {
    padding: 1rem 0;
}

.navbar .nav-link {
    padding: 0.75rem 1.25rem;
    font-size: 1.15rem;
}

.navbar .nav-link i {
    font-size: 1.3rem;
}

.navbar .badge {
    position: absolute;
    top: 0;
    right: 0;
    transform: translate(50%, -50%);
}

.navbar .nav-item {
    position: relative;
}

.navbar-brand {
    font-size: 1.5rem;
    font-weight: 500;
}

.navbar .form-control {
    font-size: 1.1rem;
}

.navbar .btn-light i {
    font-size: 1.2rem;
}
</style> 