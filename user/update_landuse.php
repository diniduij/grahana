<?php
session_start();
require "../db.php";

// Only allow field users
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'field') {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

// Read JSON payload
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['landuse_id'])) {
    http_response_code(400);
    echo "Invalid data";
    exit;
}

$landuse_id = $data['landuse_id'];
$main_type = $data['main_type'] ?? null;
$sub_type = $data['sub_type'] ?? null;
$type = $data['type'] ?? null;
$area_ha = $data['area_ha'] ?? null;
$ownership_type = $data['ownership_type'] ?? null;
$remarks = $data['remarks'] ?? null;

try {
    $sql = "UPDATE landuse.landuse_master
            SET main_type = :main_type,
                sub_type = :sub_type,
                type = :type,
                area_ha = :area_ha,
                ownership_type = :ownership_type,
                remarks = :remarks,
                meta_last_update = NOW()
            WHERE landuse_id = :landuse_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':main_type' => $main_type,
        ':sub_type' => $sub_type,
        ':type' => $type,
        ':area_ha' => $area_ha,
        ':ownership_type' => $ownership_type,
        ':remarks' => $remarks,
        ':landuse_id' => $landuse_id
    ]);

    echo "Landuse feature updated successfully";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
}
