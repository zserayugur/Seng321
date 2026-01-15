<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /Seng321/login_part/index.php?tab=register");
  exit;
}

$name = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$role = strtoupper(trim($_POST['role'] ?? 'LEARNER'));

if ($role === 'ADMIN') {
  header("Location: /Seng321/login_part/index.php?tab=register&error=" . urlencode("Admin registration is not allowed."));
  exit;
}

if ($name === '' || $email === '' || $password === '') {
  header("Location: /Seng321/login_part/index.php?tab=register&error=" . urlencode("All fields are required."));
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header("Location: /Seng321/login_part/index.php?tab=register&error=" . urlencode("Invalid email."));
  exit;
}

$regex = '/^(?=.*[A-Z])(?=.*\d).{8,}$/';

if (!preg_match($regex, $password)) {
  header("Location: /Seng321/login_part/index.php?tab=register&error=" . urlencode(
    "Password must be at least 8 characters long and include 1 uppercase letter and 1 number."
  ));
  exit;
}

$check = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$check->execute([$email]);
if ($check->fetch()) {
  header("Location: /Seng321/login_part/index.php?tab=register&error=" . urlencode("Email already exists."));
  exit;
}

if (!in_array($role, ['INSTRUCTOR','LEARNER'], true)) {
  $role = 'LEARNER';
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
  "INSERT INTO users 
   (name, email, password_hash, password_plain, role, active)
   VALUES (?, ?, ?, ?, ?, 1)"
);

$stmt->execute([
  $name,
  $email,
  $hash,
  $password,   
  $role
]);

header("Location: /Seng321/login_part/index.php?tab=login&registered=1&email=" . urlencode($email));
exit;
