<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

if (current_user_role() !== 'LEARNER') {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Forbidden']);
  exit;
}

$userId = current_user_id();

$attempt_id = (int)($_POST['attempt_id'] ?? 0);
$assignment_id = (int)($_POST['assignment_id'] ?? 0);

if ($attempt_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'attempt_id required']);
  exit;
}

try {
  // 1) attempt submit
  $stmt = $pdo->prepare("
    UPDATE assessment_attempts
    SET status='submitted', submitted_at=NOW()
    WHERE id=? AND user_id=? AND status='in_progress'
  ");
  $stmt->execute([$attempt_id, $userId]);

  // 2) Eğer assignment_id geldiyse assignment'ı completed yap
  if ($assignment_id > 0) {
    $upd = $pdo->prepare("
      UPDATE assignments
      SET status='completed', completed_at=NOW()
      WHERE id=? AND student_id=? AND status='pending'
    ");
    $upd->execute([$assignment_id, $userId]);
  }

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
