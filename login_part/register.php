<?php
require_once __DIR__ . '/../config/db.php';
session_start();

/* Bu dosya sadece POST işlemi yapacak */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /Seng321/login_part/index.php?tab=register");
  exit;
}

$name = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$role = strtoupper(trim($_POST['role'] ?? 'LEARNER'));

/* Admin register yasak */
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

if (strlen($password) < 6) {
  header("Location: /Seng3321/login_part/index.php?tab=register&error=" . urlencode("Password too short (min 6)."));
  exit;
}

$check = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$check->execute([$email]);
if ($check->fetch()) {
  header("Location: /Seng3321/login_part/index.php?tab=register&error=" . urlencode("Email already exists."));
  exit;
}

/* role whitelist (registerda zaten admin yok ama yine de güvenlik) */
if (!in_array($role, ['INSTRUCTOR','LEARNER'], true)) $role = 'LEARNER';

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,1)");
$stmt->execute([$name,$email,$hash,$role]);

/* ✅ başarı: login tab'ına dön + email doldur */
header("Location: /Seng3321/login_part/index.php?tab=login&registered=1&email=" . urlencode($email));
exit;
