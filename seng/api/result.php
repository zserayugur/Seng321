<?php
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_err('Method not allowed', 405);

$userId = current_user_id();
$attemptId = (int)($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) json_err('attempt_id required', 400);

try {
  $res = get_result($userId, $attemptId);
  json_ok(['result' => $res]);
} catch (Throwable $e) {
  json_err($e->getMessage(), 400);
}
