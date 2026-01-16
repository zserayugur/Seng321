<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) die("Invalid file.");

$stmt = $pdo->prepare("
  SELECT af.*, a.class_id
  FROM assignment_files af
  JOIN assignments a ON a.id = af.assignment_id
  WHERE af.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$f) die("File not found.");

$userId = (int)($_SESSION["user"]["id"] ?? 0);
$role = strtoupper($_SESSION["user"]["role"] ?? "");

// erişim: instructor kendi class'ıysa veya learner class member ise (basit kontrol)
if ($role === "LEARNER") {
  $chk = $pdo->prepare("SELECT 1 FROM class_members WHERE class_id=? AND student_id=? LIMIT 1");
  $chk->execute([(int)$f["class_id"], $userId]);
  if (!$chk->fetchColumn()) die("Forbidden.");
}

$path = __DIR__ . "/../uploads/assignments/" . $f["stored_name"];
if (!is_file($path)) die("Missing file on server.");

header("Content-Type: " . ($f["mime_type"] ?: "application/octet-stream"));
header('Content-Disposition: attachment; filename="' . basename($f["original_name"]) . '"');
header("Content-Length: " . filesize($path));
readfile($path);
exit;