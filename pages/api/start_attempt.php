<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/attempt_repo.php';

header('Content-Type: application/json');

$userId = current_user_id();
$type = $_POST['type'] ?? '';
$part = isset($_POST['part']) ? (int)$_POST['part'] : null;

if (!in_array($type, ['listening','speaking','writing'], true)) {
  http_response_code(400); echo json_encode(["error"=>"invalid type"]); exit;
}

if ($type === 'speaking') {
  $duration = 150;            // kayıt süresi
  $meta = ["prep_seconds"=>10, "questions_count"=>5];
} elseif ($type === 'writing') {
  $duration = 50*60;          // 50 dk
  $meta = ["min_words"=>250, "max_words"=>450];
} else { // listening
  if (!in_array($part, [1,2], true)) { $part = 1; }
  $duration = 10*60;          // 10 dk
  $meta = ["preview_seconds"=>10, "questions_count"=>10, "level"=> ($part===1 ? "intermediate_easy" : "advanced")];
}

$attemptId = create_attempt($userId, $type, $duration, $part, $meta);
echo json_encode(["attempt_id"=>$attemptId, "duration_seconds"=>$duration, "meta"=>$meta]);
