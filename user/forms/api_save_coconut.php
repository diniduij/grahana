<?php
session_start();
require "../../db.php";

header('Content-Type: application/json');

// ------------------------
// 1. User Authentication
// ------------------------
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'field') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

try {
    $data = $_POST;
    $crop_id = $data['crop_id'] ?? null;

    // ------------------------
    // 2. Handle File Upload
    // ------------------------
    $deed_image = null;
    if (isset($_FILES['deed_image']) && $_FILES['deed_image']['error'] === 0) {
        $targetDir = "../../uploads/deeds/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        $filename = uniqid() . "_" . basename($_FILES['deed_image']['name']);
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($_FILES['deed_image']['tmp_name'], $targetFile)) {
            $deed_image = $filename;
        }
    }

    // ------------------------
    // 3. Geometry SQL from user location
    // ------------------------
    $longitude = isset($data['longitude']) ? floatval($data['longitude']) : null;
    $latitude  = isset($data['latitude']) ? floatval($data['latitude']) : null;

    if ($longitude !== null && $latitude !== null) {
        // Transform WGS84 (4326) → Web Mercator (3857)
        $geom_sql = "ST_Transform(ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326), 3857)";
    } else {
        $geom_sql = "NULL";
    }

    // ------------------------
    // 4. Insert or Update
    // ------------------------
    if ($crop_id) {
        // UPDATE
        $sql = "UPDATE landuse.coconut SET
                    owner_id = :owner_id,
                    is_active = :is_active,
                    coconut_variety = :coconut_variety,
                    planting_density = :planting_density,
                    no_seedlings = :no_seedlings,
                    no_youngpalms = :no_youngpalms,
                    no_maturepalms = :no_maturepalms,
                    no_oldpalms = :no_oldpalms,
                    extent_ha = :extent_ha,
                    water_source = :water_source,
                    soil_type = :soil_type,
                    ownership_type = :ownership_type,
                    land_tenure = :land_tenure,
                    deed_number = :deed_number,
                    deed_image = COALESCE(:deed_image, deed_image),
                    elevation_m = :elevation_m,
                    suitability_class = :suitability_class,
                    land_capability_rating = :land_capability_rating,
                    irrigation_method = :irrigation_method,
                    flood_risk_level = :flood_risk_level,
                    geom = $geom_sql,
                    meta_updated_on = NOW()
                WHERE crop_id = :crop_id";
        $params = [
            ':crop_id' => $crop_id
        ];
    } else {
        // INSERT
        $sql = "INSERT INTO landuse.coconut
                (landuse_id, owner_id, is_active, coconut_variety, planting_density,
                 no_seedlings, no_youngpalms, no_maturepalms, no_oldpalms,
                 extent_ha, water_source, soil_type, ownership_type, land_tenure,
                 deed_number, deed_image, elevation_m, suitability_class,
                 land_capability_rating, irrigation_method, flood_risk_level, geom)
                VALUES
                (:landuse_id, :owner_id, :is_active, :coconut_variety, :planting_density,
                 :no_seedlings, :no_youngpalms, :no_maturepalms, :no_oldpalms,
                 :extent_ha, :water_source, :soil_type, :ownership_type, :land_tenure,
                 :deed_number, :deed_image, :elevation_m, :suitability_class,
                 :land_capability_rating, :irrigation_method, :flood_risk_level, $geom_sql)";
        $params = [
            ':landuse_id' => $data['landuse_id']
        ];
    }

    // ------------------------
    // 5. Common Bindings
    // ------------------------
    $params = array_merge($params, [
        ':owner_id' => $data['owner_id'],
        ':is_active' => $data['is_active'] ?? 1,
        ':coconut_variety' => isset($data['coconut_variety']) ? '{'.$data['coconut_variety'].'}' : '{None}',
        ':planting_density' => $data['planting_density'] ?? null,
        ':no_seedlings' => $data['no_seedlings'] ?? null,
        ':no_youngpalms' => $data['no_youngpalms'] ?? null,
        ':no_maturepalms' => $data['no_maturepalms'] ?? null,
        ':no_oldpalms' => $data['no_oldpalms'] ?? null,
        ':extent_ha' => $data['extent_ha'] ?? null,
        ':water_source' => $data['water_source'] ?? null,
        ':soil_type' => $data['soil_type'] ?? null,
        ':ownership_type' => $data['ownership_type'] ?? null,
        ':land_tenure' => $data['land_tenure'] ?? null,
        ':deed_number' => $data['deed_number'] ?? null,
        ':deed_image' => $deed_image,
        ':elevation_m' => $data['elevation_m'] ?? null,
        ':suitability_class' => $data['suitability_class'] ?? null,
        ':land_capability_rating' => $data['land_capability_rating'] ?? null,
        ':irrigation_method' => $data['irrigation_method'] ?? null,
        ':flood_risk_level' => $data['flood_risk_level'] ?? null
    ]);

    if ($longitude !== null && $latitude !== null) {
        $params[':longitude'] = $longitude;
        $params[':latitude']  = $latitude;
    }

    // ------------------------
    // 6. Execute
    // ------------------------
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success'=>true,'crop_id'=>$crop_id ?: 'new']);

} catch(Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
