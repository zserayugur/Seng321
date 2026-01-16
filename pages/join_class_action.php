<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: /Seng321/pages/join_class.php");
  exit;
}

$student_id = (int)($_SESSION["user"]["id"] ?? 0);
$class_code = strtoupper(trim($_POST["class_code"] ?? ""));

if ($student_id <= 0) {
  die("Not authenticated.");
}
if ($class_code === "") {
  die("Class code required.");
}

/* 1) Class var mi + aktif mi? */
$stmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$class_code]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
  die("Invalid class code.");
}

/* 2) Zaten member mi? */
$chk = $pdo->prepare("SELECT 1 FROM class_members WHERE class_id=? AND student_id=? LIMIT 1");
$chk->execute([(int)$class["id"], $student_id]);
if ($chk->fetchColumn()) {
  die("You are already a member of this class.");
}

/* 3) Pending request var mi? */
$chk2 = $pdo->prepare("SELECT 1 FROM class_join_requests WHERE class_id=? AND student_id=? AND status='PENDING' LIMIT 1");
$chk2->execute([(int)$class["id"], $student_id]);
if ($chk2->fetchColumn()) {
  die("You already have a pending request.");
}

/* 4) Request oluştur */
$ins = $pdo->prepare("INSERT INTO class_join_requests (class_id, student_id, status, requested_at) VALUES (?, ?, 'PENDING', NOW())");
$ins->execute([(int)$class["id"], $student_id]);

header("Location: /Seng321/pages/join_class.php?sent=1");
exit;