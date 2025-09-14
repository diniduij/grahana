<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $mobile_no = $_POST['mobile_no'];
    $role = $_POST['role'];

    $password_sql = '';
    $params = [':full_name'=>$full_name, ':username'=>$username, ':mobile_no'=>$mobile_no, ':role'=>$role, ':id'=>$id];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_sql = ", password = :password";
        $params[':password'] = $password;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name=:full_name, username=:username, mobile_no=:mobile_no, role=:role $password_sql WHERE id=:id");
        $stmt->execute($params);
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
  <title>Edit User</title>
</head>
<body class="bg-gray-100 min-h-screen p-6">

  <h1 class="text-2xl font-bold mb-4">✏️ Edit User</h1>

  <?php if ($error): ?>
    <div class="bg-red-100 text-red-600 p-2 mb-4 rounded"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
    <label class="block mb-2">Full Name</label>
    <input type="text" name="full_name" class="border p-2 mb-3 w-full" value="<?= htmlspecialchars($user['full_name']) ?>" required>

    <label class="block mb-2">Username</label>
    <input type="text" name="username" class="border p-2 mb-3 w-full" value="<?= htmlspecialchars($user['username']) ?>" required>

    <label class="block mb-2">Password (leave blank to keep current)</label>
    <input type="password" name="password" class="border p-2 mb-3 w-full">

    <label class="block mb-2">Mobile No</label>
    <input type="text" name="mobile_no" class="border p-2 mb-3 w-full" value="<?= htmlspecialchars($user['mobile_no']) ?>">

    <label class="block mb-2">Role</label>
    <select name="role" class="border p-2 mb-4 w-full" required>
      <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
      <option value="field" <?= $user['role']=='field'?'selected':'' ?>>Field</option>
    </select>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save Changes</button>
    <a href="user_manage.php" class="ml-2 text-gray-600">Cancel</a>
  </form>

</body>
</html>
