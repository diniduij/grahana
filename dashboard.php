<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
$selected_gnd = $_SESSION['selected_gnd'] ?? null;
$gnd_selected = isset($selected_gnd);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="assets/css/tailwind.min.css" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="assets/img/gis_nwp.ico">
  <title>ග්‍රහණ - Dashboard</title>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

  <!-- Top Bar -->
  <header class="p-4 bg-gray-800 text-white flex justify-between items-center">
    <span>Welcome, <?= htmlspecialchars($user['full_name']); ?> (<?= $user['role']; ?>)</span>
    <a href="logout.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Logout</a>
  </header>

  <!-- Dashboard Content -->
  <main class="p-6 flex-1">
    <h2 class="text-2xl font-bold mb-6">Dashboard</h2>

    <?php if ($user['role'] === 'admin'): ?>
      <!-- Admin Functions -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <a href="admin/user_manage.php" class="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">👥 Manage Users</h3>
          <p class="text-gray-600">Create, edit and delete users</p>
        </a>

        <a href="admin/reports.php" class="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">📊 Reports</h3>
          <p class="text-gray-600">View system usage and field reports.</p>
        </a>

        <a href="admin/assign_gnd.php" class="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
           <h3 class="text-xl font-semibold mb-2">🗂 Assign GNDs</h3>
           <p class="text-gray-600">Assign GNDs to users for field work.</p>
        </a>

        <a href="admin/user_gnd_manage.php" class="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-semibold mb-2">📋 Manage Assigned GNDs</h3>
            <p class="text-gray-600">View and remove assigned GNDs from users.</p>
        </a>

        <a href="admin/other_admin_function.php" class="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">⚙️ Other Admin</h3>
          <p class="text-gray-600">Additional admin functionalities.</p>
        </a>
      </div>

    <?php elseif ($user['role'] === 'field'): ?>
      <!-- Show selected GND if any -->
      <?php if ($selected_gnd): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded-lg mb-6 max-w-md">
            <h4 class="font-semibold">✅ Selected GND</h4>
            <p class="text-gray-700">
                <?= htmlspecialchars($selected_gnd['mc_uc_ps_n'] . " | " . $selected_gnd['gnd_n'] . " | " . $selected_gnd['gnd_c']) ?>
            </p>
            <p class="text-gray-600 text-sm">
                <?= htmlspecialchars($selected_gnd['province_n'] . ", " . $selected_gnd['district_n'] . ", " . $selected_gnd['dsd_n']) ?>
            </p>
            <a href="user/my_gnd.php" class="text-blue-600 hover:underline mt-2 inline-block">Select a different GND</a>
        </div>
      <?php endif; ?>

      <!-- Field User Functions -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          <!-- Field Data Collection Button -->
          <?php if ($gnd_selected): ?>
              <a href="user/field_map.php" class="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                  <h3 class="text-xl font-semibold mb-2">🗺️ Field Data Collection</h3>
                  <p class="text-gray-600">Collect data with map and forms.</p>
              </a>
          <?php else: ?>
              <div class="block bg-gray-200 p-6 rounded-lg shadow opacity-50 cursor-not-allowed relative group">
                  <h3 class="text-xl font-semibold mb-2">🗺️ Field Data Collection</h3>
                  <p class="text-gray-600">Collect data with map and forms.</p>
                  <span class="absolute top-0 left-0 w-full h-full flex items-center justify-center text-sm text-white bg-black bg-opacity-50 rounded-lg opacity-0 group-hover:opacity-100 transition">
                      Please select a GND first
                  </span>
              </div>
          <?php endif; ?>

          <!-- My GNDs -->
          <a href="user/my_gnd.php" class="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
              <h3 class="text-xl font-semibold mb-2">📍 My GNDs</h3>
              <p class="text-gray-600">View and manage your assigned GNDs.</p>
          </a>

          <!-- My Submissions -->
          <a href="field_reports.php" class="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
              <h3 class="text-xl font-semibold mb-2">📋 My Submissions</h3>
              <p class="text-gray-600">View your submitted field data.</p>
          </a>
      </div>
    <?php endif; ?>
  </main>

</body>
</html>
