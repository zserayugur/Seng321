<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../includes/csrf.php";

if (current_user_role() !== "LEARNER") {
  http_response_code(403);
  exit("Forbidden");
}

$student_id = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit("Method Not Allowed");
}

csrf_validate($_POST['csrf_token'] ?? null);

$assignment_id = (int)($_POST['assignment_id'] ?? 0);
if ($assignment_id <= 0) {
  http_response_code(400);
  exit("Bad request");
}

$stmt = $conn->prepare("
  UPDATE assignments
  SET status='completed', completed_at=NOW()
  WHERE id=? AND student_id=? AND status='pending'
");
$stmt->bind_param("ii", $assignment_id, $student_id);
$stmt->execute();
$stmt->close();

header("Location: /Seng321/dashboard/student_assignments.php");
exit;
