<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid id");

// Admin kendini silemesin (güzel detay)
if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
  die("You cannot delete your own admin account.");
}

// İstersen: başka admin silinmesin kuralı da ekleyebilirsin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("User not found");

if (strtoupper($row['role']) === 'ADMIN') {
  die("You cannot delete another admin.");
}

$del = $pdo->prepare("DELETE FROM users WHERE id=?");
$del->execute([$id]);

header("Location: /language-platform/admin/users.php");
exit;