<?php
/* NOTICE: Strictly for debugging purposes only. Executing this will wipe out the entire database.
    If you have executed this by mistake, you will need to re-initialize the database by executing employee_register.php
*/
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS shoepee_db");
    echo "Existing database dropped successfully<br>";

    // Create fresh database
    $pdo->exec("CREATE DATABASE shoepee_db");
    echo "New database created successfully<br>";

    // Switch to the new database
    $pdo->exec("USE shoepee_db");

    echo "Database reset complete. Please run database_init.php to initialize the tables.";

} catch(PDOException $e) {
    die("Reset failed: " . $e->getMessage());
}
?> 
