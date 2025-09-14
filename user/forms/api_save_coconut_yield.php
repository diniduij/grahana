<?php
require "../../db.php";

header("Content-Type: application/json");

try {
    $yield_id          = $_POST['yield_id'] ?? null;
    $crop_id           = $_POST['crop_id'] ?? null;
    $harvesting_method = $_POST['harvesting_method'] ?? null;
    $last_harvest_date = $_POST['last_harvest_date'] ?? null;
    $last_harvest_qty  = $_POST['last_harvest_qty'] ?? null;
    $next_harvest_date = $_POST['next_harvest_date'] ?? null;
    $expected_yield    = $_POST['expected_yield'] ?? null;
    $remarks           = $_POST['remarks'] ?? null;

    // Required field check
    if (!$crop_id || !$harvesting_method) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    if ($yield_id) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE landuse.coconut_yield SET
                crop_id = :crop_id,
                harvesting_method = :harvesting_method,
                last_harvest_date = :last_harvest_date,
                last_harvest_qty = :last_harvest_qty,
                next_harvest_date = :next_harvest_date,
                expected_yield = :expected_yield,
                remarks = :remarks,
                meta_updated_on = NOW()
            WHERE yield_id = :yield_id
        ");
        $stmt->execute([
            "yield_id" => $yield_id,
            "crop_id" => $crop_id,
            "harvesting_method" => $harvesting_method,
            "last_harvest_date" => $last_harvest_date,
            "last_harvest_qty" => $last_harvest_qty,
            "next_harvest_date" => $next_harvest_date,
            "expected_yield" => $expected_yield,
            "remarks" => $remarks
        ]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO landuse.coconut_yield (
                crop_id, harvesting_method, last_harvest_date,
                last_harvest_qty, next_harvest_date, expected_yield, remarks
            ) VALUES (
                :crop_id, :harvesting_method, :last_harvest_date,
                :last_harvest_qty, :next_harvest_date, :expected_yield, :remarks
            )
        ");
        $stmt->execute([
            "crop_id" => $crop_id,
            "harvesting_method" => $harvesting_method,
            "last_harvest_date" => $last_harvest_date,
            "last_harvest_qty" => $last_harvest_qty,
            "next_harvest_date" => $next_harvest_date,
            "expected_yield" => $expected_yield,
            "remarks" => $remarks
        ]);
    }

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
