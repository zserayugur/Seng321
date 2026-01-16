<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/attempt_repo.php';

header('Content-Type: application/json');

$userId = current_user_id();
$attemptId = (int)($_POST['attempt_id'] ?? 0);

$attempt = get_attempt($attemptId, $userId);
if (!$attempt) {
  http_response_code(404);
  echo json_encode(["error"=>"attempt not found"]);
  exit;
}

/* MOCK evaluation (AI yok) */
$mock = [
  "score_percent" => rand(60, 90),
  "message" => "Mock evaluation. AI will be integrated later.",
  "details" => [
    "fluency" => rand(6, 9),
    "vocabulary" => rand(6, 9),
    "grammar" => rand(6, 9),
    "coherence" => rand(6, 9)
  ]
];

save_ai_result($attemptId, "mock", $mock);
echo json_encode(["ok"=>true, "evaluation"=>$mock]);
