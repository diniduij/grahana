<?php
// capture_location.php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'field') {
    http_response_code(403);
    exit('Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);

$x = $input['x'] ?? null;
$y = $input['y'] ?? null;
$note = $input['note'] ?? '';

if ($x === null || $y === null) {
    http_response_code(400);
    exit('Invalid coordinates');
}

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("INSERT INTO user_points (user_id, geom, note) VALUES (:uid, ST_SetSRID(ST_MakePoint(:x,:y), 3857), :note)");
$stmt->execute([
    ':uid' => $user_id,
    ':x' => $x,
    ':y' => $y,
    ':note' => $note
]);

echo "Location saved successfully!";
