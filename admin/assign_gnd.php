<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

// Fetch all users
$stmt = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct MC/UC/PS values
$stmt2 = $pdo->query("SELECT DISTINCT mc_uc_ps_n FROM gnd ORDER BY mc_uc_ps_n");
$mc_uc_ps_list = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="../assets/css/tailwind.min.css" rel="stylesheet">
<title>Assign GND</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

<h1 class="text-2xl font-bold mb-4">Assign GND to Users</h1>

<a href="../dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded mb-4 inline-block hover:bg-gray-700">← Back to Dashboard</a>

<form method="POST" action="assign_gnd_save.php" class="bg-white p-6 rounded-lg shadow-md max-w-md">

    <label class="block mb-2">Select User</label>
    <select name="user_id" class="border p-2 mb-4 w-full" required>
        <option value="">-- Select User --</option>
        <?php foreach($users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label class="block mb-2">Select MC/UC/PS</label>
    <select id="mc_uc_ps" class="border p-2 mb-4 w-full" required>
        <option value="">-- Select MC/UC/PS --</option>
        <?php foreach($mc_uc_ps_list as $m): ?>
            <option value="<?= htmlspecialchars($m['mc_uc_ps_n']) ?>"><?= htmlspecialchars($m['mc_uc_ps_n']) ?></option>
        <?php endforeach; ?>
    </select>

    <label class="block mb-2">Select GND(s)</label>
    <select name="gnd_ids[]" id="gnd_dropdown" class="border p-2 mb-4 w-full" multiple size="6" required>
        <!-- options loaded via AJAX -->
    </select>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Assign GND(s)</button>
</form>


<script>
$(document).ready(function(){
    $('#mc_uc_ps').on('change', function(){
        var mc_uc_ps = $(this).val();
        if(mc_uc_ps){
            $.ajax({
                type: 'POST',
                url: 'fetch_gnds.php',
                data: {mc_uc_ps: mc_uc_ps},
                success: function(html){
                    $('#gnd_dropdown').html(html);
                }
            });
        }else{
            $('#gnd_dropdown').html('<option value="">-- Select GND --</option>');
        }
    });
});
</script>

</body>
</html>
