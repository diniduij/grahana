<?php
session_start();
require "../db.php";

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

if (!isset($_GET['gnd_id']) || empty($_GET['gnd_id'])) {
    echo json_encode(["type"=>"FeatureCollection","features"=>[]], JSON_UNESCAPED_SLASHES);
    exit;
}

function pgArrayToPhpArray($pgArray) {
    if (!$pgArray) return [];
    $trimmed = trim($pgArray, '{}');
    if ($trimmed === '') return [];
    $parts = explode(',', $trimmed);
    return array_map(fn($v)=>trim($v,'"'), $parts);
}

$gndId = $_GET['gnd_id'];

try {
    $sql = "
        SELECT 
            b.building_id,
            b.building_code,
            b.building_type,
            b.no_of_floors,
            b.building_material,
            b.roof_type,
            b.electricity_sources,
            b.water_supply,
            b.liquidwaste_disposal,
            b.solidwaste_disposal,
            b.construction_year,
            b.construction_type,
            b.is_occupied,
            b.meta_created_date,
            b.meta_original_source,
            b.meta_updated_date,
            b.meta_update_source,
            ST_AsGeoJSON(ST_Transform(b.geom, 3857)) AS geojson
        FROM buildings.building_master b
        JOIN public.gnd g
        ON ST_Intersects(ST_MakeValid(b.geom), ST_MakeValid(g.geom))
        WHERE g.gid = :gid
          AND NOT ST_IsEmpty(ST_Intersection(ST_MakeValid(b.geom), ST_MakeValid(g.geom)))
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':gid' => $gndId]);

    $features = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['geojson']) && $row['geojson'] !== 'null') {
            $geom = json_decode($row['geojson'], true);
            if (is_array($geom)) {
                $features[] = [
                    "type" => "Feature",
                    "geometry" => $geom,
                    "properties" => [
                        "building_id"          => $row['building_id'],
                        "building_code"        => $row['building_code'],
                        "building_type"        => $row['building_type'],
                        "no_of_floors"         => (int)$row['no_of_floors'],
                        "building_material"    => $row['building_material'],
                        "roof_type"            => $row['roof_type'],
                        "electricity_sources"  => pgArrayToPhpArray($row['electricity_sources'] ?? '{}'),
                        "water_supply"         => $row['water_supply'],
                        "liquidwaste_disposal" => $row['liquidwaste_disposal'],
                        "solidwaste_disposal"  => $row['solidwaste_disposal'],
                        "construction_year"    => (int)$row['construction_year'],
                        "construction_type"    => $row['construction_type'],
                        "is_occupied"          => (bool)$row['is_occupied'],
                        "meta_created_date"    => $row['meta_created_date'],
                        "meta_original_source" => $row['meta_original_source'],
                        "meta_updated_date"    => $row['meta_updated_date'],
                        "meta_update_source"   => $row['meta_update_source']
                    ]
                ];
            }
        }
    }

    echo json_encode(["type"=>"FeatureCollection","features"=>$features], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "type"=>"FeatureCollection",
        "features"=>[],
        "error"=>"db_error",
        "details"=>$e->getMessage()
    ], JSON_UNESCAPED_SLASHES);
}
