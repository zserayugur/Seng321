<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
  header("Location: /SENG321/login_part/index.php?error=" . urlencode("Email and password required."));
  exit;
}

$stmt = $pdo->prepare("SELECT id, name, email, role, password_hash FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
  header("Location: /SENG321/login_part/index.php?error=" . urlencode("Login failed."));
  exit;
}

session_regenerate_id(true);

$_SESSION["user"] = [
  "id" => (int)$user["id"],
  "name" => $user["name"] ?? "",
  "email" => $user["email"],
  "role" => strtolower(trim($user["role"]))
];

$role = $_SESSION["user"]["role"];

if ($role === 'admin') {
  header("Location: /SENG321/admin/dashboard.php"); 
  exit;
}

header("Location: /SENG321/login_part/Learner.php");
exit;
