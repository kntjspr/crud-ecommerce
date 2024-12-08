<?php
// Start the session
session_start();

// Include the database configuration
require_once 'config/database.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'customer') {
        header("Location: index.php");
    } else if ($_SESSION['is_admin']) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

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
        $_SESSION['user_type'] = 'employee';
        
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
        
        // Check if the user exists and the password is correct
        if($user && password_verify($password, $user['Password'])) {
            $_SESSION['customer_id'] = $user['Customer_ID'];
            $_SESSION['user_name'] = $user['First_Name'] . ' ' . $user['Last_Name'];
            $_SESSION['user_type'] = 'customer';
            
            // Check if there's a redirect URL stored
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect);
            } else {
                header("Location: index.php");
            }
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
    <style>
        body {
            background-color: #f0f0f0;
        }
        /* Scope all login styles to avoid affecting navbar */
        .login-page .login-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .login-page .login-header {
            background-color: #f05537;
            color: white;
            padding: 1rem;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .login-page .login-form {
            padding: 2rem;
        }
        .login-page .form-label {
            color: #666;
            font-weight: normal;
            margin-bottom: 0.5rem;
        }
        .login-page .form-control {
            border: 1px solid #ddd;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .login-page .btn-login {
            background-color: #f05537;
            border: none;
            padding: 0.75rem;
            width: 100%;
            font-weight: 500;
            border-radius: 4px;
        }
        .login-page .btn-login:hover {
            background-color: #e04527;
        }
        .login-page .register-link {
            text-align: center;
            margin-top: 1rem;
            color: #666;
        }
        .login-page .register-link a {
            color: #f05537;
            text-decoration: none;
        }
        .login-page .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="login-page">
        <div class="login-container">
            <div class="login-header">
                <h2 class="m-0">Login to Shoepee</h2>
            </div>

            <div class="login-form">
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

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary btn-login">Login</button>
                </form>

                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 