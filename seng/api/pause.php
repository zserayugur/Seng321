<?php
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method not allowed', 405);

$userId = current_user_id();
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$attemptId = (int)($body['attemptId'] ?? 0);
if ($attemptId <= 0) json_err('attemptId required', 400);

try {
  ensure_auto_submit_if_needed($userId, $attemptId);
  $a = pause_attempt($attemptId, $userId);
  $a['remainingSec'] = remaining_sec($a);

  json_ok(['attempt' => [
    'status' => $a['status'],
    'remainingSec' => $a['remainingSec'],
  ]]);
} catch (Throwable $e) {
  json_err($e->getMessage(), 400);
}
