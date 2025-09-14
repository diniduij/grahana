<?php
require "db.php";  // adjust path if needed

try {
    // Admin user
    $adminPassword = password_hash("1234", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, mobile_no, role) 
                           VALUES (:full_name, :username, :password, :mobile_no, :role)");
    $stmt->execute([
        ':full_name' => 'System Administrator',
        ':username' => 'admin',
        ':password' => $adminPassword,
        ':mobile_no' => '0771234567',
        ':role' => 'admin'
    ]);

    // Sample field user
    $fieldPassword = password_hash("abcd", PASSWORD_DEFAULT);
    $stmt->execute([
        ':full_name' => 'Field User',
        ':username' => 'fielduser',
        ':password' => $fieldPassword,
        ':mobile_no' => '0779876543',
        ':role' => 'field'
    ]);

    echo "Users inserted successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
