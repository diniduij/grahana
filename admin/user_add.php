<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $mobile_no = $_POST['mobile_no'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, mobile_no, role) VALUES (:full_name, :username, :password, :mobile_no, :role)");
        $stmt->execute([
            ':full_name' => $full_name,
            ':username' => $username,
            ':password' => $password,
            ':mobile_no' => $mobile_no,
            ':role' => $role
        ]);
        header("Location: user_manage.php");
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <link href="../assets/css/tailwind.min.css" rel="stylesheet">
  <title>Add User</title>
</head>
<body class="bg-gray-100 min-h-screen p-6">

  <h1 class="text-2xl font-bold mb-4">➕ Add New User</h1>

  <?php if ($error): ?>
    <div class="bg-red-100 text-red-600 p-2 mb-4 rounded"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
    <label class="block mb-2">Full Name</label>
    <input type="text" name="full_name" class="border p-2 mb-3 w-full" required>

    <label class="block mb-2">Username</label>
    <input type="text" name="username" class="border p-2 mb-3 w-full" required>

    <label class="block mb-2">Password</label>
    <input type="password" name="password" class="border p-2 mb-3 w-full" required>

    <label class="block mb-2">Mobile No</label>
    <input type="text" name="mobile_no" class="border p-2 mb-3 w-full">

    <label class="block mb-2">Role</label>
    <select name="role" class="border p-2 mb-4 w-full" required>
      <option value="admin">Admin</option>
      <option value="field">Field</option>
    </select>

    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Create User</button>
    <a href="user_manage.php" class="ml-2 text-gray-600">Cancel</a>
  </form>

</body>
</html>
