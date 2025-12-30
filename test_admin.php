<?php
require_once __DIR__ . "/config/db.php";

$name = "Admin";
$email = "admin@test.com";
$password = "123456";
$role = "ADMIN";

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, role, active)
         VALUES (?, ?, ?, ?, 1)"
    );
    $stmt->execute([$name, $email, $hash, $role]);

    echo "Admin created. email=admin@test.com password=123456";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "Admin already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
