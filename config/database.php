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

// Check if this is first installation
if (!checkDatabaseExists($host, $username, $password, $dbname)) {
    header('Location: setup.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 