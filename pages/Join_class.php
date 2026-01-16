<?php
require_once __DIR__ . "/../includes/auth_guard.php"; // login zorunlu
require_once __DIR__ . "/../config/db.php";          // $pdo

$student_id = (int)($_SESSION["user"]["id"] ?? 0);
$class_code = strtoupper(trim($_POST["class_code"] ?? ""));

if ($student_id <= 0) {
  die("Not logged in.");
}
if ($class_code === "") {
  die("Class code required.");
}

/* 1) Class var mı + aktif mi? */
$stmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$class_code]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
  die("Invalid class code.");
}

$class_id = (int)$class["id"];

/* 2) Zaten member mı? */
$stmt = $pdo->prepare("SELECT id FROM class_members WHERE class_id=? AND student_id=? LIMIT 1");
$stmt->execute([$class_id, $student_id]);
if ($stmt->fetch()) {
  die("You are already in this class.");
}

/* 3) Zaten pending request var mı? */
$stmt = $pdo->prepare("SELECT id FROM class_join_requests WHERE class_id=? AND student_id=? AND status='PENDING' LIMIT 1");
$stmt->execute([$class_id, $student_id]);
if ($stmt->fetch()) {
  die("Join request already sent (PENDING).");
}

/* 4) Request oluştur */
$stmt = $pdo->prepare("
  INSERT INTO class_join_requests (class_id, student_id, status, requested_at)
  VALUES (?, ?, 'PENDING', NOW())
");
$stmt->execute([$class_id, $student_id]);

header("Location: /Seng321/pages/class_join.php?sent=1");
exit;
