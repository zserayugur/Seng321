<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$selectedRole = strtoupper(trim($_POST['role'] ?? 'LEARNER')); // LEARNER/INSTRUCTOR/ADMIN

if ($email === '' || $password === '') {
  header("Location: /SENG321/login_part/index.php?tab=login&error=" . urlencode("Email and password required."));
  exit;
}

/* kullanıcıyı çek */
$stmt = $pdo->prepare("SELECT id, name, email, role, password_hash, active FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* şifre kontrol */
if (!$user || !password_verify($password, $user['password_hash'])) {
  header("Location: /SENG321/login_part/index.php?tab=login&error=" . urlencode("Login failed."));
  exit;
}

/* aktif mi */
if ((int)$user['active'] !== 1) {
  header("Location: /SENG321/login_part/index.php?tab=login&error=" . urlencode("Account is inactive."));
  exit;
}

/* ✅ role eşleşmesi */
if (strtoupper($user['role']) !== $selectedRole) {
  header("Location: /SENG321/login_part/index.php?tab=login&error=" . urlencode("Role mismatch."));
  exit;
}

/* session */
session_regenerate_id(true);
$_SESSION["user"] = [
  "id" => (int)$user["id"],
  "name" => $user["name"] ?? "",
  "email" => $user["email"],
  "role" => strtoupper($user["role"]) // ADMIN/INSTRUCTOR/LEARNER
];

/* yönlendirme */
if ($_SESSION["user"]["role"] === "ADMIN") {
  header("Location: /SENG321/admin/dashboard.php");
  exit;
}
if ($_SESSION["user"]["role"] === "INSTRUCTOR") {
  header("Location: /SENG321/instructor/dashboard.php");
  exit;
}

header("Location: /SENG321/pages/speaking.php");
exit;
