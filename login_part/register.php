<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'LEARNER';


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email");
}

if (strlen($password) < 6) {
    die("Password too short");
}

$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    die("Email already exists");
}

$role = strtoupper($role); 

if (!in_array($role, ['ADMIN','INSTRUCTOR','LEARNER'])) {
    $role = 'LEARNER';
}
$plainPassword = $_POST['password'];
$hash = password_hash($plainPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
  INSERT INTO users (name, email, role, password_hash, password_plain)
  VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$name, $email, $role, $hash, $plainPassword]);

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users
    (name, email, password_hash, role, active)
    VALUES (?, ?, ?, ?, 1)
");

$stmt->execute([
    $name,
    $email,
    $password_hash,
    $role
]);

echo "REGISTER SUCCESS";
exit;
