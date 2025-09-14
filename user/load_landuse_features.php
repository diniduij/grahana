<?php
session_start();
require "../db.php";

header('Content-Type: application/json; charset=utf-8');

/* Prevent PHP warnings/notices from breaking JSON */
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

if (!isset($_SESSION['selected_gnd']['gid'])) {
    echo json_encode(["type" => "FeatureCollection", "features" => []], JSON_UNESCAPED_SLASHES);
    exit;
}

$gndId = $_SESSION['selected_gnd']['gid'];

try {
    $sql = "
        SELECT 
            l.landuse_id,
            l.landuse_code,
            l.main_type,
            l.sub_type,
            l.type,
            l.ownership_type,
            l.remarks,
            ROUND(
                (ST_Area(ST_Intersection(ST_MakeValid(l.geom), ST_MakeValid(g.geom))) / 10000)::numeric,
                2
            ) AS area_ha,
            ST_AsGeoJSON(
                ST_Transform(
                    ST_Intersection(ST_MakeValid(l.geom), ST_MakeValid(g.geom)),
                    3857
                )
            ) AS geojson
        FROM landuse.landuse_master l
        JOIN (
            SELECT ST_MakeValid(geom) AS geom
            FROM public.gnd
            WHERE gid = :gid
            LIMIT 1
        ) g ON ST_Intersects(ST_MakeValid(l.geom), g.geom)
        WHERE NOT ST_IsEmpty(ST_Intersection(ST_MakeValid(l.geom), g.geom))
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':gid' => $gndId]);

    $features = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['geojson'])) {
            $geom = json_decode($row['geojson'], true);
            if (is_array($geom)) {
                $features[] = [
                    "type" => "Feature",
                    "geometry" => $geom,
                    "properties" => [
                        "landuse_id"     => $row['landuse_id'],
                        "landuse_code"   => $row['landuse_code'],
                        "main_type"      => $row['main_type'],
                        "sub_type"       => $row['sub_type'],
                        "type"           => $row['type'],
                        "ownership_type" => $row['ownership_type'],
                        "remarks"        => $row['remarks'],
                        "area_ha"        => (float)$row['area_ha']
                    ]
                ];
            }
        }
    }

    echo json_encode([
        "type" => "FeatureCollection",
        "features" => $features
    ], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "type" => "FeatureCollection",
        "features" => [],
        "error" => "db_error",
        "details" => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES);
}
