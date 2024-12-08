<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS shoepee_db");
    echo "Database dropped successfully<br>";
    
    // Create fresh database
    $pdo->exec("CREATE DATABASE shoepee_db");
    echo "New database created successfully<br>";
    
    echo "<br>Database reset complete. Please run database_init.php to initialize the tables.<br>";
    echo "<a href='database_init.php' class='btn btn-primary'>Initialize Database</a>";

} catch(PDOException $e) {
    die("Database reset failed: " . $e->getMessage());
}
?> 

<!DOCTYPE html>
<html>
<head>
    <title>Database Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 2rem;
            background-color: #f0f0f0;
        }
        .message {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
            margin-top: 1rem;
        }
        .btn-primary:hover {
            background-color: #e04527;
            border-color: #e04527;
        }
    </style>
</head>
<body>
    <div class="message">
        <h2>Database Reset</h2>
        <div id="messages"></div>
    </div>
    <script>
        // Capture PHP output and display in messages div
        document.getElementById('messages').innerHTML = document.body.innerText;
    </script>
</body>
</html> 