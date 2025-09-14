<?php
session_start();
require "../../db.php";
header("Content-Type: application/json");

// --------------------
// 1. Basic validation
// --------------------
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'field') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    // Collect form inputs
    $crop_id  = $_POST['crop_id'] ?? null;
    $landuse_id = $_POST['landuse_id'] ?? null;

    if (!$landuse_id) {
        echo json_encode(["success" => false, "message" => "Missing landuse_id"]);
        exit;
    }

    // Convert empty crop_id to NULL
    if ($crop_id === '') {
        $crop_id = null;
    }

    // Handle file upload (optional)
    $deed_image_path = null;
    if (!empty($_FILES['deed_image']['name'])) {
        $upload_dir = "../../uploads/deeds/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = uniqid("deed_") . "_" . basename($_FILES["deed_image"]["name"]);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES["deed_image"]["tmp_name"], $target_path)) {
            $deed_image_path = $file_name;
        }
    }

    // --------------------
    // 2. UPDATE existing
    // --------------------
    if ($crop_id) {
        $sql = "UPDATE landuse.crop_field SET
                    owner_id = :owner_id,
                    is_active = :is_active,
                    extent_ha = :extent_ha,
                    water_source = :water_source,
                    soil_type = :soil_type,
                    ownership_type = :ownership_type,
                    land_tenure = :land_tenure,
                    deed_number = :deed_number,
                    deed_image = COALESCE(:deed_image, deed_image),
                    elevation_m = :elevation_m,
                    flood_risk_level = :flood_risk_level,
                    drainage_status = :drainage_status,
                    suitability_class = :suitability_class,
                    land_capability_rating = :land_capability_rating,
                    meta_updated_on = NOW()
                WHERE crop_id = :crop_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "owner_id" => $_POST['owner_id'] ?: null,
            "is_active" => $_POST['is_active'] ?? 1,
            "extent_ha" => $_POST['extent_ha'] ?: null,
            "water_source" => $_POST['water_source'] ?: null,
            "soil_type" => $_POST['soil_type'] ?: null,
            "ownership_type" => $_POST['ownership_type'] ?: null,
            "land_tenure" => $_POST['land_tenure'] ?: null,
            "deed_number" => $_POST['deed_number'] ?: null,
            "deed_image" => $deed_image_path,
            "elevation_m" => $_POST['elevation_m'] ?: null,
            "flood_risk_level" => $_POST['flood_risk_level'] ?: null,
            "drainage_status" => $_POST['drainage_status'] ?: null,
            "suitability_class" => $_POST['suitability_class'] ?: null,
            "land_capability_rating" => $_POST['land_capability_rating'] ?: null,
            "crop_id" => $crop_id
        ]);

        echo json_encode(["success" => true, "message" => "Crop field updated"]);
        exit;
    }

    // --------------------
    // 3. INSERT new
    // --------------------
    $sql = "INSERT INTO landuse.crop_field (
                landuse_id, owner_id, is_active, extent_ha, water_source,
                soil_type, ownership_type, land_tenure, deed_number, deed_image,
                elevation_m, flood_risk_level, drainage_status, suitability_class,
                land_capability_rating
            ) VALUES (
                :landuse_id, :owner_id, :is_active, :extent_ha, :water_source,
                :soil_type, :ownership_type, :land_tenure, :deed_number, :deed_image,
                :elevation_m, :flood_risk_level, :drainage_status, :suitability_class,
                :land_capability_rating
            ) RETURNING crop_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "landuse_id" => $landuse_id,
        "owner_id" => $_POST['owner_id'] ?: null,
        "is_active" => $_POST['is_active'] ?? 1,
        "extent_ha" => $_POST['extent_ha'] ?: null,
        "water_source" => $_POST['water_source'] ?: null,
        "soil_type" => $_POST['soil_type'] ?: null,
        "ownership_type" => $_POST['ownership_type'] ?: null,
        "land_tenure" => $_POST['land_tenure'] ?: null,
        "deed_number" => $_POST['deed_number'] ?: null,
        "deed_image" => $deed_image_path,
        "elevation_m" => $_POST['elevation_m'] ?: null,
        "flood_risk_level" => $_POST['flood_risk_level'] ?: null,
        "drainage_status" => $_POST['drainage_status'] ?: null,
        "suitability_class" => $_POST['suitability_class'] ?: null,
        "land_capability_rating" => $_POST['land_capability_rating'] ?: null
    ]);

    $new_id = $stmt->fetchColumn();

    echo json_encode(["success" => true, "message" => "Crop field inserted", "crop_id" => $new_id]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}
