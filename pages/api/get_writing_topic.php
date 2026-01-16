<?php
require_once __DIR__ . '/../../includes/ai_service.php';
header('Content-Type: application/json');

try {
    $topic = fetchWritingTopic();
    echo json_encode(['topic' => $topic]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>