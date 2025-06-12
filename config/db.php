<?php
$host = 'localhost';
$dbname = 'dbfinal';
$username = 'root';
$password = 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "✅ Connected successfully to the database.";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
