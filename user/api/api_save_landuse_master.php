<?php
require "../../db.php";

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$landuse_id     = $_POST['landuse_id'] ?? null;
$main_type      = $_POST['main_type'] ?? null;
$sub_type       = $_POST['sub_type'] ?? null;
$type           = $_POST['type'] ?? null;
$ownership_type = $_POST['ownership_type'] ?? null;
$area_ha        = $_POST['area_ha'] ?? null;
$remarks        = $_POST['remarks'] ?? null;
$field_updated  = 'TRUE';
if (!$landuse_id) {
    echo json_encode(["success" => false, "message" => "Missing landuse_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE landuse.landuse_master
        SET main_type = :main_type,
            sub_type = :sub_type,
            type = :type,
            ownership_type = :ownership_type,
            area_ha = :area_ha,
            remarks = :remarks,
            meta_last_update = NOW(),
            field_updated = :field_updated
        WHERE landuse_id = :landuse_id
    ");

    $stmt->execute([
        ":main_type"      => $main_type,
        ":sub_type"       => $sub_type,
        ":type"           => $type,
        ":ownership_type" => $ownership_type,
        ":area_ha"        => $area_ha,
        ":remarks"        => $remarks,
        ":field_updated"  => $field_updated,
        ":landuse_id"     => $landuse_id
    ]);

    echo json_encode(["success" => true, "landuse_id" => $landuse_id]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
