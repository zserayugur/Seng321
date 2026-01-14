<?php
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method not allowed', 405);

$userId = current_user_id();
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$attemptId = (int)($body['attemptId'] ?? 0);
$reason = strtoupper(trim($body['reason'] ?? 'MANUAL'));
if (!in_array($reason, ['MANUAL','TIME_EXPIRED','PAUSE_POLICY'], true)) $reason = 'MANUAL';
if ($attemptId <= 0) json_err('attemptId required', 400);

try {
  // expiry varsa önce yakala
  ensure_auto_submit_if_needed($userId, $attemptId);

  $res = submit_attempt($userId, $attemptId, $reason);
  $a = $res['attempt'];

  $canStartNext = ($a['testType'] === 'READING' && (int)$a['readingStage'] === 1);

  json_ok([
    'result' => $res,
    'canStartNext' => $canStartNext,
    'readingGroup' => $a['readingGroup'],
  ]);
} catch (Throwable $e) {
  json_err($e->getMessage(), 400);
}
