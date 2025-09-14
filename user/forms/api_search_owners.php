<?php
require "../../db.php";

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT owner_id, owner_name, owner_code 
    FROM public.ownership 
    WHERE owner_name ILIKE :q 
    ORDER BY owner_name 
    LIMIT 10
");
$stmt->execute([':q' => "%$q%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
