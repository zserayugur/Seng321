<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header("Location: /SENG321/admin/users.php?error=" . urlencode("Invalid id"));
  exit;
}

$currentId = (int)($_SESSION['user']['id'] ?? 0);
if ($id === $currentId) {
  header("Location: /SENG321/admin/users.php?error=" . urlencode("You cannot delete your own admin account."));
  exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  header("Location: /SENG321/admin/users.php?error=" . urlencode("User not found"));
  exit;
}

if (strtoupper($row['role']) === 'ADMIN') {
  header("Location: /SENG321/admin/users.php?error=" . urlencode("You cannot delete another admin."));
  exit;
}

$del = $pdo->prepare("DELETE FROM users WHERE id=?");
$del->execute([$id]);

header("Location: /SENG321/admin/users.php?deleted=1");
exit;
