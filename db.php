<?php
// db.php
$host = "localhost";
$dbname = "grahana";
$user = "postgres";
$pass = "1234";

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
