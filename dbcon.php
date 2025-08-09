<?php
$host = 'localhost';
$dbname = 'system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Uncomment below to debug connection
    // echo "Connected successfully";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>