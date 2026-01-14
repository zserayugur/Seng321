<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/attempt_repo.php';

header('Content-Type: application/json');

$userId = current_user_id();
$attemptId = (int)($_POST['attempt_id'] ?? 0);
$qIndex = (int)($_POST['question_index'] ?? 0);
$qText = $_POST['question_text'] ?? '';
$aText = $_POST['answer_text'] ?? '';

$attempt = get_attempt($attemptId, $userId);
if (!$attempt) { http_response_code(404); echo json_encode(["error"=>"attempt not found"]); exit; }
if ($attempt['status'] !== 'in_progress') { http_response_code(400); echo json_encode(["error"=>"attempt not in progress"]); exit; }

save_answer($attemptId, $qIndex, $qText, $aText);
echo json_encode(["ok"=>true]);
