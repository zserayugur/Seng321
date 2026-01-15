<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'LEARNER';

/* email format */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email");
}

/* password length */
if (strlen($password) < 6) {
    die("Password too short");
}

/* email unique */
$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    die("Email already exists");
}

/* ROLE → ENUM UYUMLU */
$role = strtoupper($role); // learner → LEARNER

if (!in_array($role, ['ADMIN','INSTRUCTOR','LEARNER'])) {
    $role = 'LEARNER';
}

/* password hash */
$password_hash = password_hash($password, PASSWORD_DEFAULT);

/* INSERT (TABLOYA TAM UYUMLU) */
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
