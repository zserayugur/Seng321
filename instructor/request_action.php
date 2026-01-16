<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

$role = strtoupper($_SESSION["user"]["role"] ?? "");
if ($role !== "INSTRUCTOR") {
  http_response_code(403);
  die("403 Forbidden (Instructor only)");
}

$id = (int)($_GET["id"] ?? 0);
$action = $_GET["action"] ?? "";

if ($id <= 0 || !in_array($action, ["approve", "reject"], true)) {
  die("Invalid request");
}

$pdo->beginTransaction();

$reqStmt = $pdo->prepare("SELECT class_id, student_id, status FROM class_join_requests WHERE id=? FOR UPDATE");
$reqStmt->execute([$id]);
$req = $reqStmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
  $pdo->rollBack();
  die("Request not found");
}
if ($req["status"] !== "pending") {
  $pdo->rollBack();
  header("Location: /Seng321/instructor/requests.php");
  exit;
}

$newStatus = ($action === "approve") ? "approved" : "rejected";

$upd = $pdo->prepare("UPDATE class_join_requests SET status=? WHERE id=?");
$upd->execute([$newStatus, $id]);

if ($newStatus === "approved") {
  // class_members'a ekle (aynı kayıt varsa ekleme)
  $check = $pdo->prepare("SELECT 1 FROM class_members WHERE class_id=? AND student_id=? LIMIT 1");
  $check->execute([(int)$req["class_id"], (int)$req["student_id"]]);
  if (!$check->fetchColumn()) {
    $ins = $pdo->prepare("INSERT INTO class_members (class_id, student_id, joined_at) VALUES (?, ?, NOW())");
    $ins->execute([(int)$req["class_id"], (int)$req["student_id"]]);
  }
}

$pdo->commit();

header("Location: /Seng321/instructor/requests.php");
exit;