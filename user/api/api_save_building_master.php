<?php
require "../../db.php";

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$building_id      = $_POST['building_id'] ?? null;
$building_code    = $_POST['building_code'] ?? null;
$building_type    = $_POST['building_type'] ?? null;
$no_of_floors     = $_POST['no_of_floors'] ?? null;
$building_material= $_POST['building_material'] ?? null;
$roof_type        = $_POST['roof_type'] ?? null;
$electricity_sources = $_POST['electricity_sources'] ?? null;
$water_supply     = $_POST['water_supply'] ?? null;
$liquidwaste_disposal = $_POST['liquidwaste_disposal'] ?? null;
$solidwaste_disposal  = $_POST['solidwaste_disposal'] ?? null;
$construction_year    = $_POST['construction_year'] ?? null;
$construction_type    = $_POST['construction_type'] ?? null;
$is_occupied      = $_POST['is_occupied'] ?? null;
$remarks          = $_POST['remarks'] ?? null;
$field_updated    = 'TRUE';
if (!$building_id) {
    echo json_encode(["success" => false, "message" => "Missing building_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE buildings.building_master
        SET building_code = :building_code,
            building_type = :building_type,
            no_of_floors = :no_of_floors,
            building_material = :building_material,
            roof_type = :roof_type,
            electricity_sources = :electricity_sources,
            water_supply = :water_supply,
            liquidwaste_disposal = :liquidwaste_disposal,
            solidwaste_disposal = :solidwaste_disposal,
            construction_year = :construction_year,
            construction_type = :construction_type,
            is_occupied = :is_occupied,
            remarks = :remarks,
            meta_last_update = NOW(),
            field_updated = :field_updated
        WHERE building_id = :building_id
    ");

    $stmt->execute([
        ":building_code"         => $building_code,
        ":building_type"         => $building_type,
        ":no_of_floors"          => $no_of_floors,
        ":building_material"     => $building_material,
        ":roof_type"             => $roof_type,
        ":electricity_sources"   => $electricity_sources,
        ":water_supply"          => $water_supply,
        ":liquidwaste_disposal"  => $liquidwaste_disposal,
        ":solidwaste_disposal"   => $solidwaste_disposal,
        ":construction_year"     => $construction_year,
        ":construction_type"     => $construction_type,
        ":is_occupied"           => $is_occupied,
        ":remarks"               => $remarks,
        ":field_updated"         => $field_updated,
        ":building_id"           => $building_id
    ]);

    echo json_encode(["success" => true, "building_id" => $building_id]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
