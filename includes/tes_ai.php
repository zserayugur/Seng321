<?php
require_once __DIR__ . "/includes/ai_client.php";

$prompt = <<<PROMPT
You are generating exam questions. Output ONLY valid JSON.

Schema:
{
  "questions": [
    {"question_text": "...", "choices": ["A","B","C","D"], "correct_index": 0, "explanation": "..."}
  ]
}

Generate 5 questions for CEFR A2 Grammar practice.
PROMPT;

try {
  $ai = ai_generate_json($prompt);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode($ai, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  echo "ERROR: " . htmlspecialchars($e->getMessage());
}
