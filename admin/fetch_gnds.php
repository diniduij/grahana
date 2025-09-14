<?php
require "../db.php";

if(isset($_POST['mc_uc_ps'])){
    $mc_uc_ps = $_POST['mc_uc_ps'];

    $stmt = $pdo->prepare("SELECT gid, gnd_n, gnd_c FROM gnd WHERE mc_uc_ps_n = :mc_uc_ps ORDER BY gnd_n");
    $stmt->execute([':mc_uc_ps' => $mc_uc_ps]);
    $gnds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<option value="">-- Select GND --</option>';
    foreach($gnds as $g){
        echo '<option value="'.$g['gid'].'">'.htmlspecialchars($g['gnd_n']." | ".$g['gnd_c']).'</option>';
    }
}
