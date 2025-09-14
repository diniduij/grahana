<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'field') {
    header("Location: ../dashboard.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Fetch assigned GNDs
$stmt = $pdo->prepare("
    SELECT g.gid, g.mc_uc_ps_n, g.gnd_n, g.gnd_c, g.province_n, g.district_n, g.dsd_n
    FROM user_gnd ug
    JOIN gnd g ON ug.gnd_id = g.gid
    WHERE ug.user_id = :user_id
    ORDER BY g.mc_uc_ps_n, g.gnd_n
");
$stmt->execute([':user_id' => $user_id]);
$assigned_gnds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_gnd_id = $_POST['gnd_id'] ?? null;

    // Check if GND belongs to user
    $valid = false;
    foreach($assigned_gnds as $gnd){
        if($gnd['gid'] == $selected_gnd_id){
            $valid = true;
            $_SESSION['selected_gnd'] = $gnd; // Store full GND info in session
            break;
        }
    }

    if($valid){
        // Redirect back to user dashboard
        header("Location: ../dashboard.php");
        exit;
    } else {
        $error = "Invalid GND selection.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../assets/css/tailwind.min.css" rel="stylesheet">
<title>My GNDs</title>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col space-y-4">

<header class="p-4 bg-gray-800 text-white flex justify-between items-center">
    <span>Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']); ?> (<?= $_SESSION['user']['role']; ?>)</span>
    <div class="flex gap-2">
        <a href="../dashboard.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">← Back to Dashboard</a>
        <a href="../logout.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Logout</a>
    </div>
</header>

<h1 class="text-2xl font-bold mb-4">📍 My Assigned GNDs</h1>

<?php if(!empty($assigned_gnds)): ?>
<form method="POST" class="bg-white p-6 rounded-lg shadow-md max-w-md">
    <?php if(!empty($error)): ?>
        <div class="bg-red-100 text-red-600 p-2 mb-4 rounded"><?= $error ?></div>
    <?php endif; ?>

    <label class="block mb-2 font-semibold">Select a GND to Collect Data:</label>
    <select name="gnd_id" class="border p-2 mb-4 w-full" required>
        <option value="">-- Select GND --</option>
        <?php foreach($assigned_gnds as $gnd): ?>
            <option value="<?= $gnd['gid'] ?>">
                <?= htmlspecialchars($gnd['mc_uc_ps_n'] . " | " . $gnd['gnd_n'] . " | " . $gnd['gnd_c']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">
        Select GND
    </button>


</form>

<?php else: ?>
<div class="bg-white p-6 rounded-lg shadow-md text-gray-500">No GNDs assigned.</div>
<?php endif; ?>

</body>
</html>
