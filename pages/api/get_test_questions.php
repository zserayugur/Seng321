<?php
require_once __DIR__ . '/../../includes/ai_service.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

header('Content-Type: application/json');

$skill = $_POST['skill'] ?? 'vocabulary';
$level = $_POST['level'] ?? 'B1';
$count = (int) ($_POST['count'] ?? 20);

// Call the AI Service
$result = fetchAITestQuestions($skill, $level, $count);

echo json_encode($result);
?>