<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Customer WHERE Username = ?");
    $stmt->execute([$username]);
    if($stmt->fetchColumn() > 0) {
        $error = "Username already exists";
    } else {
        // Insert customer into database
        $stmt = $pdo->prepare("INSERT INTO Customer (Username, Password, First_Name, Last_Name, Email, Phone_Number) VALUES (?, ?, ?, ?, ?, ?)");
        if($stmt->execute([$username, $password, $first_name, $last_name, $email, $phone])) {
            $customer_id = $pdo->lastInsertId();
            
            // Set session variables like in login.php
            $_SESSION['customer_id'] = $customer_id;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['is_admin'] = false;
            
            // Redirect to intended page or home
            $redirect_url = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirect_url);
            exit();
        } else {
            $error = "Registration failed";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'favicon.php'; ?>
    <title>Register - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f0f0;
        }
        /* Scope all register styles to avoid affecting navbar */
        .register-page .register-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .register-page .register-header {
            background-color: #f05537;
            color: white;
            padding: 1rem;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .register-page .register-form {
            padding: 2rem;
        }
        .register-page .form-label {
            color: #666;
            font-weight: normal;
            margin-bottom: 0.5rem;
        }
        .register-page .form-control {
            border: 1px solid #ddd;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .register-page .btn-register {
            background-color: #f05537;
            border: none;
            padding: 0.75rem;
            width: 100%;
            font-weight: 500;
            border-radius: 4px;
        }
        .register-page .btn-register:hover {
            background-color: #e04527;
        }
        .register-page .login-link {
            text-align: center;
            margin-top: 1rem;
            color: #666;
        }
        .register-page .login-link a {
            color: #f05537;
            text-decoration: none;
        }
        .register-page .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="register-page">
        <div class="register-container">
            <div class="register-header">
                <h2 class="m-0">Register for Shoepee</h2>
            </div>

            <div class="register-form">
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-register">Register</button>
                </form>

                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 