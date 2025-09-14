<?php
session_start();
header('Content-Type: application/json');

if (!isset($_POST['landuse_id'])) {
    echo json_encode(['success' => false, 'message' => 'No landuse_id provided']);
    exit;
}

$_SESSION['selected_landuse_id'] = $_POST['landuse_id'];

echo json_encode(['success' => true, 'landuse_id' => $_SESSION['selected_landuse_id']]);
