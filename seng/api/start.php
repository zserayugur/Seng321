<?php
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method not allowed', 405);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$type = strtoupper(trim($body['type'] ?? ''));

$userId = current_user_id();

try {
  if ($type === 'READING') {
    $attemptId = start_attempt($userId, 'READING', 1, null);
  } elseif (in_array($type, ['GRAMMAR','VOCAB'], true)) {
    $attemptId = start_attempt($userId, $type, null, null);
  } else {
    json_err('Invalid type', 400);
  }

  $a = get_attempt($userId, $attemptId);
  $payload = fetch_questions_for_attempt($attemptId);

  json_ok([
    'attempt' => [
      'id' => (int)$attemptId,
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
