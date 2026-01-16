<?php
require_once __DIR__ . '/../../includes/ai_service.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$topic = $input['topic'] ?? '';
$transcript = $input['transcript'] ?? '';

if (empty($transcript)) {
    echo json_encode(['error' => 'No speech detected. Please try recording again.']);
    exit;
}

$result = evaluateSpeakingAttempt($topic, $transcript);

echo json_encode($result);
?>