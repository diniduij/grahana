<?php
header('Content-Type: application/json');
$response = ["success" => false, "message" => "Unknown error"];

try {
    // your insert/update query here...
    $response = ["success" => true, "message" => "Saved successfully"];
} catch (Exception $e) {
    $response = ["success" => false, "message" => $e->getMessage()];
}

echo json_encode($response);
