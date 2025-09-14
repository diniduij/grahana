<?php
// api_get_landuse.php
require "../../db.php"; // adjust path

if (!isset($_GET['landuse_id']) || empty($_GET['landuse_id'])) {
    echo json_encode(['success' => false, 'message' => 'No landuse_id provided']);
    exit;
}

$landuse_id = $_GET['landuse_id'];

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM landuse.landuse_master
        WHERE landuse_id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $landuse_id]);
    $feature = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$feature) {
        echo json_encode(['success' => false, 'message' => 'Feature not found']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $feature]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: '.$e->getMessage()]);
}
