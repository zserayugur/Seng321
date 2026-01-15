<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: /SENG321/login_part/index.php");
  exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$error = "";

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $error = "Invalid email format.";
} else {
  $stmt = $pdo->prepare("SELECT id, name, email, role, password_hash FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) $error = "No account found with this email.";
  elseif (!password_verify($password, $user['password_hash'])) $error = "Incorrect password.";
}

if ($error) {
  header("Location: /SENG321/login_part/index.php?error=" . urlencode($error));
  exit;
}

session_regenerate_id(true);
$_SESSION["user"] = [
  "id" => (int)$user["id"],
  "name" => $user["name"] ?? "",
  "email" => $user["email"],
  "role" => $user["role"]
];

if ($user['role'] === 'admin') header("Location: /SENG321/dashboard/admin.php");
elseif ($user['role'] === 'instructor') header("Location: /SENG321/dashboard/instructor.php");
else header("Location: /SENG321/dashboard/learner.php");
exit;
