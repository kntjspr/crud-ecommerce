<?php
session_start();
require_once 'config/database.php';

if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // First try employee login
    $stmt = $pdo->prepare("SELECT * FROM Employee WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && password_verify($password, $user['Password'])) {
        $_SESSION['employee_id'] = $user['Employee_ID'];
        $_SESSION['is_admin'] = $user['Is_Admin'];
        $_SESSION['user_name'] = $user['First_Name'] . ' ' . $user['Last_Name'];
        
        if($user['Is_Admin']) {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        // Try customer login
        $stmt = $pdo->prepare("SELECT * FROM Customer WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user && password_verify($password, $user['Password'])) {
            $_SESSION['customer_id'] = $user['Customer_ID'];
            $_SESSION['user_name'] = $user['First_Name'] . ' ' . $user['Last_Name'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Login to Shoepee</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if(isset($_GET['error']) && $_GET['error'] == 'invalid_user'): ?>
                            <div class="alert alert-danger">Session expired. Please login again.</div>
                        <?php endif; ?>

                        <?php if(isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                        </form>
                        <p class="text-center mt-3">
                            Don't have an account? <a href="register.php">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 