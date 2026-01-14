<?php
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method not allowed', 405);

$userId = current_user_id();
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$attemptId  = (int)($body['attemptId'] ?? 0);
$questionId = (int)($body['questionId'] ?? 0);
$choice     = strtoupper(trim($body['choice'] ?? ''));

if ($attemptId <= 0 || $questionId <= 0 || $choice === '') json_err('Missing fields', 400);

try {
  ensure_auto_submit_if_needed($userId, $attemptId);
  save_answer($userId, $attemptId, $questionId, $choice);
  json_ok(['saved' => true]);
} catch (Throwable $e) {
  json_err($e->getMessage(), 400);
}
