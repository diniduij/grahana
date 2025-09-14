<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $user_id = $_POST['user_id'] ?? null;
    $gnd_ids = $_POST['gnd_ids'] ?? [];

    if($user_id && !empty($gnd_ids)){
        $stmt = $pdo->prepare("INSERT INTO user_gnd (user_id, gnd_id) VALUES (:user_id, :gnd_id)");

        foreach($gnd_ids as $gnd_id){
            $stmt->execute([':user_id' => $user_id, ':gnd_id' => $gnd_id]);
        }

        header("Location: assign_gnd.php?success=1");
        exit;
    } else {
        header("Location: assign_gnd.php?error=1");
        exit;
    }
}
