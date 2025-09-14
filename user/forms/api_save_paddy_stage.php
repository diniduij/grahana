<?php
require "../../db.php";

header("Content-Type: application/json");

try {
    $input_id = $_POST['input_id'] ?? null;
    $cultivation_id = $_POST['cultivation_id'] ?? null;
    $input_type = $_POST['input_type'] ?? null;
    $stage = $_POST['stage'] ?? null;
    $description = $_POST['description'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $applied_date = $_POST['applied_date'] ?? null;

    if (!$cultivation_id || !$input_type) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    if ($input_id) {
        // Update
        $stmt = $pdo->prepare("UPDATE landuse.paddy_stages 
            SET input_type=:input_type, stage=:stage, description=:description, quantity=:quantity, applied_date=:applied_date, meta_updated_on=NOW()
            WHERE input_id=:input_id");
        $stmt->execute([
            "input_type" => $input_type,
            "stage" => $stage,
            "description" => $description,
            "quantity" => $quantity,
            "applied_date" => $applied_date,
            "input_id" => $input_id
        ]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO landuse.paddy_stages (cultivation_id, input_type, stage, description, quantity, applied_date) 
            VALUES (:cultivation_id, :input_type, :stage, :description, :quantity, :applied_date)");
        $stmt->execute([
            "cultivation_id" => $cultivation_id,
            "input_type" => $input_type,
            "stage" => $stage,
            "description" => $description,
            "quantity" => $quantity,
            "applied_date" => $applied_date
        ]);
    }

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
