<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$id = (int)($_GET["id"] ?? 0);
$stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
$stmt->execute([$id]);

header("Location: /language-platform/admin/users.php");
exit;
