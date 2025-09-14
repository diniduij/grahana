<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);

header("Location: user_manage.php");
exit;
