<?php
require "../../db.php";
if(!isset($_GET['building_id'])){
  echo json_encode(['success'=>false,'message'=>'No ID']); exit;
}
$id=$_GET['building_id'];
$stmt=$pdo->prepare("SELECT * FROM buildings.building_master WHERE building_id=:id LIMIT 1");
$stmt->execute([':id'=>$id]);
$row=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){ echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
echo json_encode(['success'=>true,'data'=>$row]);
