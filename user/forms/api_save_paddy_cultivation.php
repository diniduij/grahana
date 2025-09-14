<?php
require "../../db.php";

header("Content-Type: application/json");

try {
    $cultivation_id   = $_POST['cultivation_id'] ?? null;
    $crop_id          = $_POST['crop_id'] ?? null;
    $field_type       = $_POST['field_type'] ?? null;
    $paddy_variety    = $_POST['paddy_variety'] ?? null;
    $crop_duration    = $_POST['crop_duration'] ?? null;
    $irrigation_method= $_POST['irrigation_method'] ?? null;
    $season           = $_POST['season'] ?? null;
    $est_yield_kgpha  = $_POST['est_yield_kgpha'] ?? null;
    $cultivation_method = $_POST['cultivation_method'] ?? null;
    $seed_amount      = $_POST['seed_amount'] ?? null;
    $cultivated_date  = $_POST['cultivated_date'] ?? null;
    $havested_date    = $_POST['havested_date'] ?? null;
    $harvesting_method= $_POST['harvesting_method'] ?? null;
    $yield_kg         = $_POST['yield_kg'] ?? null;
    $remarks          = $_POST['remarks'] ?? null;

    if (!$crop_id || !$field_type || !$paddy_variety) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    if ($cultivation_id) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE landuse.paddy_cultivation SET
                crop_id = :crop_id,
                field_type = :field_type,
                paddy_variety = :paddy_variety,
                crop_duration = :crop_duration,
                irrigation_method = :irrigation_method,
                season = :season,
                est_yield_kgpha = :est_yield_kgpha,
                cultivation_method = :cultivation_method,
                seed_amount = :seed_amount,
                cultivated_date = :cultivated_date,
                havested_date = :havested_date,
                harvesting_method = :harvesting_method,
                yield_kg = :yield_kg,
                remarks = :remarks,
                meta_updated_on = NOW()
            WHERE cultivation_id = :cultivation_id
        ");
        $stmt->execute([
            "cultivation_id" => $cultivation_id,
            "crop_id" => $crop_id,
            "field_type" => $field_type,
            "paddy_variety" => $paddy_variety,
            "crop_duration" => $crop_duration,
            "irrigation_method" => $irrigation_method,
            "season" => $season,
            "est_yield_kgpha" => $est_yield_kgpha,
            "cultivation_method" => $cultivation_method,
            "seed_amount" => $seed_amount,
            "cultivated_date" => $cultivated_date,
            "havested_date" => $havested_date,
            "harvesting_method" => $harvesting_method,
            "yield_kg" => $yield_kg,
            "remarks" => $remarks
        ]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO landuse.paddy_cultivation (
                crop_id, field_type, paddy_variety, crop_duration,
                irrigation_method, season, est_yield_kgpha,
                cultivation_method, seed_amount, cultivated_date,
                havested_date, harvesting_method, yield_kg, remarks
            ) VALUES (
                :crop_id, :field_type, :paddy_variety, :crop_duration,
                :irrigation_method, :season, :est_yield_kgpha,
                :cultivation_method, :seed_amount, :cultivated_date,
                :havested_date, :harvesting_method, :yield_kg, :remarks
            )
        ");
        $stmt->execute([
            "crop_id" => $crop_id,
            "field_type" => $field_type,
            "paddy_variety" => $paddy_variety,
            "crop_duration" => $crop_duration,
            "irrigation_method" => $irrigation_method,
            "season" => $season,
            "est_yield_kgpha" => $est_yield_kgpha,
            "cultivation_method" => $cultivation_method,
            "seed_amount" => $seed_amount,
            "cultivated_date" => $cultivated_date,
            "havested_date" => $havested_date,
            "harvesting_method" => $harvesting_method,
            "yield_kg" => $yield_kg,
            "remarks" => $remarks
        ]);
    }

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
