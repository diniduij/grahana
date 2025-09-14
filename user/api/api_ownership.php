<?php
require "../../db.php";
header("Content-Type: application/json");
$method = $_SERVER['REQUEST_METHOD'];

if ($method === "GET") {
    $stmt = $pdo->query("SELECT owner_id, owner_code, owner_type, owner_name, reference, remarks FROM ownership ORDER BY owner_name");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if ($method === "POST") {
    // Insert or Update
    if (!empty($data['owner_id'])) {
        $stmt = $pdo->prepare("UPDATE ownership SET owner_code=?, owner_type=?, owner_name=?, reference=?, remarks=? WHERE owner_id=?");
        $stmt->execute([$data['owner_code'], $data['owner_type'], $data['owner_name'], $data['reference'], $data['remarks'], $data['owner_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO ownership (owner_code, owner_type, owner_name, reference, remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['owner_code'], $data['owner_type'], $data['owner_name'], $data['reference'], $data['remarks']]);
    }
    echo json_encode(["status" => "ok"]);
    exit;
}

if ($method === "DELETE") {
    $id = $data['owner_id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM ownership WHERE owner_id = ?");
        $stmt->execute([$id]);
    }
    echo json_encode(["status" => "deleted"]);
    exit;
}
