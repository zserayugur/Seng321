<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/attempt_repo.php';

header('Content-Type: application/json');

$userId = current_user_id();
$attemptId = (int)($_POST['attempt_id'] ?? 0);

$attempt = get_attempt($attemptId, $userId);
if (!$attempt) { http_response_code(404); echo json_encode(["error"=>"attempt not found"]); exit; }

submit_attempt($attemptId, $userId);
echo json_encode(["ok"=>true]);
