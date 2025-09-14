<?php
session_start();
require_once("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'full_name' => $user['full_name']
            ];

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="assets/css/tailwind.min.css" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="assets/img/gis_nwp.ico">
  <title>ග්‍රහණ - Field Data Collection App</title>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col items-center">
      
      <!-- Logo -->
      <img src="assets/img/grahana.jpg" alt="App Logo" class="w-64 h-64 mb-4">

      <!-- <h2 class="text-4xl font-bold mb-6 text-center text-gray-800">ග්‍රහණ</h2> -->
      <p class="text-sm text-gray-500 mb-6 text-center">Field Data Collection Web App</p>

      <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-600 p-3 mb-4 w-full rounded text-center">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="w-full flex flex-col gap-4">
        <input type="text" name="username" placeholder="Username" required
               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="password" name="password" placeholder="Password" required
               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

        <button type="submit"
                class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700 transition duration-200">
          Login
        </button>
      </form>

      <p class="mt-6 text-sm text-gray-400 text-center inline-flex items-center justify-center gap-2">
          &copy; <?= date('Y') ?> <img src="assets/img/gis_nwp.ico" alt="App Logo" class="w-6 h-6"> GIS Unit NWP. All rights reserved. 
          
      </p>

    </div>
  </div>

</body>
</html>
