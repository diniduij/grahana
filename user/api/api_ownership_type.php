<?php
require "../../db.php"; // adjust path to your DB config

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT unnest(enum_range(NULL::buildings.ownership_type_enum)) AS ownership");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "success" => true,
        "data" => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
