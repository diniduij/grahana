<?php
require "../../db.php";

$cultivation_id = $_POST['cultivation_id'] ?? $_GET['id'] ?? null;

if (!$cultivation_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing cultivation_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM landuse.paddy_cultivation WHERE cultivation_id = :id");
    $stmt->execute(["id" => $cultivation_id]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
