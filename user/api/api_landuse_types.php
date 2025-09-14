<?php
// api_landuse_types.php
require "../../db.php"; // adjust path

// Fetch all landuse types
$stmt = $pdo->query("SELECT main_type, infor_main_type, sub_type, infor_sub_type, type, infor_type FROM landuse.landuse_types ORDER BY main_type, sub_type, type");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($types);

