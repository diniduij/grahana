<?php
require "../../db.php";

header("Content-Type: application/json");

try {
    $yield_id = $_POST['yield_id'] ?? $_GET['yield_id'] ?? null;


    if (!$yield_id) {
        echo json_encode(["success" => false, "message" => "Missing yield_id"]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM landuse.coconut_yield WHERE yield_id = :yield_id");
    $stmt->execute(["yield_id" => $yield_id]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
