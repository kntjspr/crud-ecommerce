<?php
session_start();
require_once 'config/database.php';

// Check if the registration form is submitted
if(isset($_POST['register'])) {
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
           
            // Get the customer details after registration
            $customer_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM Customer WHERE Customer_ID = ?");
            $stmt->execute([$customer_id]);
            $customer = $stmt->fetch();
            
            // Set all required session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['customer_id'] = $customer_id;
            $_SESSION['username'] = $username;
            $_SESSION['first_name'] = $customer['First_Name'];
            $_SESSION['last_name'] = $customer['Last_Name'];
            
            header("Location: index.php");
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
    <title>Register - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #f44336;
            --secondary-color: #ff8a80;
            --bg-color: #f0f0f0;
        }
        
        body {
            background-color: var(--bg-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .registration-container {
            background: white;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .registration-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            border-radius: 8px 8px 0 0;
        }
        
        .registration-form {
            padding: 30px;
            background: #e0e0e0;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-control {
            padding: 12px;
            border: none;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .register-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            width: 100%;
            margin-top: 20px;
            font-size: 16px;
        }
        
        .register-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #6366f1;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            Register for Shoepee
        </div>
        <div class="registration-form">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" name="phone" required>
                </div>
                
                <button type="submit" name="register" class="register-btn">Register</button>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 