<?php
require "../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['stage_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing stage_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM landuse.coconut_stages WHERE stage_id = :id");
    $stmt->execute(["id" => $data['stage_id']]);
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
