<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'shoepee_db';

function checkDatabaseExists($host, $username, $password, $dbname) {
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
        return $result->rowCount() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

$dbExists = checkDatabaseExists($host, $username, $password, $dbname);
$step = isset($_GET['step']) ? $_GET['step'] : ($dbExists ? 'complete' : 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoepee Setup Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .wizard-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .step-indicator {
            margin-bottom: 30px;
        }
        .step {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
        }
        .step.active {
            background: #f05537;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
        }
        .btn-primary:hover {
            background-color: #e04527;
            border-color: #e04527;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="wizard-container">
            <h1 class="text-center mb-4">Shoepee Setup Wizard</h1>
            
            <div class="step-indicator text-center">
                <span class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</span>
                <span class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</span>
                <span class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</span>
            </div>

            <?php if($step == 1): ?>
                <div class="step-content">
                    <h3>Welcome to Shoepee Setup</h3>
                    <p>This wizard will help you set up your Shoepee e-commerce platform. Before we begin, we'll check your system requirements.</p>
                    
                    <div class="requirements mt-4">
                        <h4>System Requirements:</h4>
                        <ul class="list-group">
                            <li class="list-group-item">
                                PHP Version: 
                                <?php echo version_compare(PHP_VERSION, '7.4.0') >= 0 ? 
                                    '<span class="text-success">✓ ' . PHP_VERSION . '</span>' : 
                                    '<span class="text-danger">✗ Requires PHP 7.4 or higher</span>'; ?>
                            </li>
                            <li class="list-group-item">
                                PDO Extension: 
                                <?php echo extension_loaded('pdo') ? 
                                    '<span class="text-success">✓ Enabled</span>' : 
                                    '<span class="text-danger">✗ Not enabled</span>'; ?>
                            </li>
                            <li class="list-group-item">
                                MySQL Connection: 
                                <?php 
                                try {
                                    new PDO("mysql:host=$host", $username, $password);
                                    echo '<span class="text-success">✓ Connected</span>';
                                } catch(PDOException $e) {
                                    echo '<span class="text-danger">✗ Connection failed</span>';
                                }
                                ?>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="text-end mt-4">
                        <a href="setup.php?step=2" class="btn btn-primary">Continue</a>
                    </div>
                </div>

            <?php elseif($step == 2): ?>
                <div class="step-content">
                    <h3>Database Setup</h3>
                    <p>We'll now create and initialize the database with required tables.</p>
                    
                    <div class="alert alert-info">
                        This will create a new database named 'shoepee_db' if it doesn't exist.
                    </div>

                    <div class="text-end mt-4">
                        <a href="database_reset.php" class="btn btn-primary">Initialize Database</a>
                    </div>
                </div>

            <?php elseif($step == 3): ?>
                <div class="step-content">
                    <h3>Setup Complete!</h3>
                    <div class="alert alert-success">
                        <h4>Installation Successful!</h4>
                        <p>Your Shoepee e-commerce platform has been successfully installed.</p>
                    </div>
                    
                    <h4>Default Admin Credentials:</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item">Email: admin@shoepee.com</li>
                        <li class="list-group-item">Password: admin123</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        Please change these credentials after your first login!
                    </div>

                    <div class="text-end mt-4">
                        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                    </div>
                </div>

            <?php elseif($step == 'complete'): ?>
                <div class="step-content">
                    <div class="alert alert-info">
                        <h4>Already Installed</h4>
                        <p>Shoepee is already installed and configured on your system.</p>
                    </div>
                    
                    <div class="text-end mt-4">
                        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 