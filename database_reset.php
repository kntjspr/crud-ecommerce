<?php
// Database configuration assuming that the database is hosted on localhost
$host = 'localhost';
$username = 'root';
$password = '';

// Try to connect to the database
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS shoepee_db");
    echo "Existing database dropped successfully<br>";

    // // Create fresh database
    // $pdo->exec("CREATE DATABASE shoepee_db");
    // echo "New database created successfully<br>";

    // // Switch to the new database
    // $pdo->exec("USE shoepee_db");

    echo "Database reset complete. Please run database_init.php to initialize the tables.";

} catch(PDOException $e) {
    // If there's an error, display it
    die("Reset failed: " . $e->getMessage());
}
?> 