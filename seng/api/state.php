<?php
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_err('Method not allowed', 405);

$userId = current_user_id();
$attemptId = (int)($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) json_err('attempt_id required', 400);

try {
  ensure_auto_submit_if_needed($userId, $attemptId);

  $a = get_attempt($userId, $attemptId);
  $payload = fetch_questions_for_attempt($attemptId);

  json_ok([
    'attempt' => [
      'id' => (int)$a['id'],
      'testType' => $a['test_type'],
      'readingStage' => $a['reading_stage'],
      'readingGroup' => $a['reading_group'],
      'status' => $a['status'],
      'expiresAt' => $a['expires_at'],
      'remainingSec' => $a['remainingSec'],
    ],
    'passage' => $payload['passage'],
    'questions' => $payload['questions'],
  ]);
} catch (Throwable $e) {
  json_err($e->getMessage(), 400);
}
