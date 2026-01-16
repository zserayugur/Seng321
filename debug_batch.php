<?php
ini_set('display_errors', 1);
require_once 'includes/ai_service.php';
echo "Starting Batch Test (20 questions)...\n";
$start = microtime(true);
$res = fetchAITestQuestions('grammar', 'B1', 20);
echo "Finished in " . (microtime(true) - $start) . "s\n";
echo "Source: " . ($res['source'] ?? 'N/A') . "\n";
echo "Count: " . count($res['questions'] ?? []) . "\n";
if (!empty($res['questions'])) {
    print_r($res['questions'][0]);
}
?>