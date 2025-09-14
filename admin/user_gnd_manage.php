<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

// Fetch filter options
$users_stmt = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name");
$users_list = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

$mc_uc_ps_stmt = $pdo->query("SELECT DISTINCT mc_uc_ps_n FROM gnd ORDER BY mc_uc_ps_n");
$mc_uc_ps_list = $mc_uc_ps_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filters from GET
$filter_user = $_GET['user_id'] ?? '';
$filter_mc_uc_ps = $_GET['mc_uc_ps'] ?? '';
$filter_gnd = $_GET['gnd'] ?? '';

// Build query dynamically
$sql = "
    SELECT ug.id as assignment_id, u.full_name as user_name, g.gnd_n, g.gnd_c, g.mc_uc_ps_n
    FROM user_gnd ug
    JOIN users u ON ug.user_id = u.id
    JOIN gnd g ON ug.gnd_id = g.gid
    WHERE 1=1
";

$params = [];

if($filter_user) {
    $sql .= " AND u.id = :user_id";
    $params[':user_id'] = $filter_user;
}
if($filter_mc_uc_ps) {
    $sql .= " AND g.mc_uc_ps_n = :mc_uc_ps";
    $params[':mc_uc_ps'] = $filter_mc_uc_ps;
}
if($filter_gnd) {
    $sql .= " AND g.gnd_n ILIKE :gnd";
    $params[':gnd'] = "%$filter_gnd%";
}

$sql .= " ORDER BY u.full_name, g.mc_uc_ps_n, g.gnd_n";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="../assets/css/tailwind.min.css" rel="stylesheet">
<title>Manage Assigned GNDs</title>
</head>
<body class="bg-gray-100 min-h-screen p-6">

<h1 class="text-2xl font-bold mb-4">🗂 Manage Assigned GNDs</h1>
<a href="../dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded mb-4 inline-block hover:bg-gray-700">← Back to Dashboard</a>

<!-- Filter Form -->
<form method="GET" class="bg-white p-4 rounded-lg shadow-md mb-4 flex flex-col md:flex-row gap-4 items-end">
    <div>
        <label class="block mb-1">User</label>
        <select name="user_id" class="border p-2 rounded w-full">
            <option value="">All Users</option>
            <?php foreach($users_list as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block mb-1">MC/UC/PS</label>
        <select name="mc_uc_ps" class="border p-2 rounded w-full">
            <option value="">All MC/UC/PS</option>
            <?php foreach($mc_uc_ps_list as $m): ?>
                <option value="<?= htmlspecialchars($m['mc_uc_ps_n']) ?>" <?= $filter_mc_uc_ps == $m['mc_uc_ps_n'] ? 'selected' : '' ?>><?= htmlspecialchars($m['mc_uc_ps_n']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block mb-1">GND Name</label>
        <input type="text" name="gnd" value="<?= htmlspecialchars($filter_gnd) ?>" placeholder="GND" class="border p-2 rounded w-full">
    </div>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
    <a href="user_gnd_manage.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Reset</a>
</form>

<!-- Assigned GNDs Table -->
<div class="bg-white rounded-lg shadow overflow-x-auto">
  <table class="w-full table-auto border-collapse">
    <thead class="bg-gray-200 text-left">
      <tr>
        <th class="p-2">User</th>
        <th class="p-2">MC/UC/PS</th>
        <th class="p-2">GND</th>
        <th class="p-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($assignments as $a): ?>
      <tr class="border-b">
        <td class="p-2"><?= htmlspecialchars($a['user_name']) ?></td>
        <td class="p-2"><?= htmlspecialchars($a['mc_uc_ps_n']) ?></td>
        <td class="p-2"><?= htmlspecialchars($a['gnd_n'] . " | " . $a['gnd_c']) ?></td>
        <td class="p-2">
          <a href="user_gnd_delete.php?id=<?= $a['assignment_id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Remove this GND assignment?')">Remove</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($assignments)): ?>
      <tr>
        <td colspan="4" class="p-2 text-center text-gray-500">No assignments found.</td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
