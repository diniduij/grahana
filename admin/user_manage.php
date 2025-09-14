<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <link href="../assets/css/tailwind.min.css" rel="stylesheet">
  <title>User Management</title>
</head>
<body class="bg-gray-100 min-h-screen p-6">

  <!-- Page Header -->
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">👥 User Management</h1>
    <a href="../dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">← Back to Dashboard</a>
  </div>

  <!-- Add User Button -->
  <a href="user_add.php" class="bg-blue-600 text-white px-4 py-2 rounded mb-4 inline-block hover:bg-blue-700 transition">+ Add User</a>

  <!-- Users Table -->
  <div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full table-auto border-collapse">
      <thead class="bg-gray-200 text-left">
        <tr>
          <th class="p-2">ID</th>
          <th class="p-2">Full Name</th>
          <th class="p-2">Username</th>
          <th class="p-2">Role</th>
          <th class="p-2">Mobile</th>
          <th class="p-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr class="border-b">
            <td class="p-2"><?= $u['id'] ?></td>
            <td class="p-2"><?= htmlspecialchars($u['full_name']) ?></td>
            <td class="p-2"><?= htmlspecialchars($u['username']) ?></td>
            <td class="p-2"><?= $u['role'] ?></td>
            <td class="p-2"><?= $u['mobile_no'] ?></td>
            <td class="p-2 space-x-2">
              <a href="user_edit.php?id=<?= $u['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
              <a href="user_delete.php?id=<?= $u['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Delete this user?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</body>
</html>
