<?php
require "../../db.php";

header("Content-Type: application/json");

try {
    if (!isset($_GET['id'])) {
        echo json_encode(["success" => false, "message" => "Missing ID"]);
        exit;
    }

    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("DELETE FROM landuse.paddy_stages WHERE input_id=:id");
    $stmt->execute(["id" => $id]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
