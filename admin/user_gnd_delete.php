<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

if(isset($_GET['id'])){
    $stmt = $pdo->prepare("DELETE FROM user_gnd WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
}

header("Location: user_gnd_manage.php");
exit;
