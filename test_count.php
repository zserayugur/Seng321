<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/ai_service.php';

echo "Start\n";
echo "Fetching Grammar Questions (count=20)...\n";
$start = microtime(true);
$data = fetchAITestQuestions('grammar', 'B1', 20);
$duration = microtime(true) - $start;

echo "Duration: " . number_format($duration, 2) . "s\n";
echo "Source: " . $data['source'] . "\n";
echo "Questions Count: " . count($data['questions']) . "\n";

if (count($data['questions']) < 20) {
    echo "WARNING: Less than 20 questions returned.\n";
} else {
    echo "SUCCESS: 20 or more questions returned.\n";
}
?>