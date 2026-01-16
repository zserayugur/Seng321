<?php
require_once __DIR__ . '/../../includes/ai_service.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$topic = $input['topic'] ?? '';
$text = $input['text'] ?? '';

if (empty($text) || strlen(trim($text)) < 5) {
    echo json_encode(['error' => 'Essay is too short. Please write more.']);
    exit;
}

$result = evaluateWritingAttempt($topic, $text);

echo json_encode($result);
?>