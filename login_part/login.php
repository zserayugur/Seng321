<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$selectedRole = strtoupper(trim($_POST['role'] ?? 'LEARNER')); 

// Load base path utility
require_once __DIR__ . '/../includes/base_path.php';
$basePath = get_base_path();

if ($email === '' || $password === '') {
  header("Location: " . $basePath . "/login_part/index.php?tab=login&error=" . urlencode("Email and password required."));
  exit;
}

$stmt = $pdo->prepare("SELECT id, name, email, role, password_hash, active FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
  header("Location: " . $basePath . "/login_part/index.php?tab=login&error=" . urlencode("Login failed."));
  exit;
}

if ((int)$user['active'] !== 1) {
  header("Location: " . $basePath . "/login_part/index.php?tab=login&error=" . urlencode("Account is inactive."));
  exit;
}

if (strtoupper($user['role']) !== $selectedRole) {
  header("Location: " . $basePath . "/login_part/index.php?tab=login&error=" . urlencode("Role mismatch."));
  exit;
}

session_regenerate_id(true);
$_SESSION["user"] = [
  "id" => (int)$user["id"],
  "name" => $user["name"] ?? "",
  "email" => $user["email"],
  "role" => strtoupper($user["role"]) 
];

if ($_SESSION["user"]["role"] === "ADMIN") {
  header("Location: " . $basePath . "/admin/dashboard.php");
  exit;
}
if ($_SESSION["user"]["role"] === "INSTRUCTOR") {
  header("Location: " . $basePath . "/dashboard/instructor.php");
  exit;
}

header("Location: " . $basePath . "/dashboard/learner.php");
exit;
