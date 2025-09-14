<?php
require "../../db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

$stage_id = $data['stage_id'] ?? null;
$crop_id = $data['crop_id'] ?? null;
$input_type = $data['input_type'] ?? null;
$stage = $data['stage'] ?? null;
$description = $data['description'] ?? null;
$quantity = $data['quantity'] ?? null;
$applied_date = $data['applied_date'] ?? null;

if (!$crop_id || !$input_type || !$stage || !$applied_date) {
    http_response_code(422);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

try {
    if ($stage_id) {
        // Update
        $stmt = $pdo->prepare("UPDATE landuse.coconut_stages 
            SET input_type = :input_type, stage = :stage, description = :description, 
                quantity = :quantity, applied_date = :applied_date 
            WHERE stage_id = :stage_id");
        $stmt->execute([
            "input_type" => $input_type,
            "stage" => $stage,
            "description" => $description,
            "quantity" => $quantity,
            "applied_date" => $applied_date,
            "stage_id" => $stage_id
        ]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO landuse.coconut_stages 
            (crop_id, input_type, stage, description, quantity, applied_date) 
            VALUES (:crop_id, :input_type, :stage, :description, :quantity, :applied_date)");
        $stmt->execute([
            "crop_id" => $crop_id,
            "input_type" => $input_type,
            "stage" => $stage,
            "description" => $description,
            "quantity" => $quantity,
            "applied_date" => $applied_date
        ]);
    }
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
