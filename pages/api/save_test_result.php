<?php
require_once __DIR__ . '/../../includes/mock_data.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

header('Content-Type: application/json');

/*
    Expected POST:
    - test_type (e.g. 'vocabulary', 'grammar')
    - score (0-100)
    - level (A1-C2)
    - details (JSON string of q/a analysis)
*/

$type = $_POST['test_type'] ?? 'unknown';
$score = (float) ($_POST['score'] ?? 0);
$level = $_POST['level'] ?? 'B1';
$details = $_POST['details'] ?? '{}';

// 1. Update Profile (Estimate)
$mapIelts = ["A1" => 3.0, "A2" => 4.0, "B1" => 5.0, "B2" => 6.0, "C1" => 7.0, "C2" => 8.0];
$mapToefl = ["A1" => 20, "A2" => 35, "B1" => 55, "B2" => 75, "C1" => 95, "C2" => 110];

updateUserProfile([
    "current_level" => $level, // Update current level based on latest test? Or just weighted? Let's just update perfectly for now
    "ielts_estimate" => $mapIelts[$level] ?? 5.5,
    "toefl_estimate" => $mapToefl[$level] ?? 60
]);

// 2. Save Result History
addTestResult([
    "test" => ucfirst($type) . " Assessment",
    "test_type" => $type,
    "score" => $score,
    "max_score" => 100,
    "cefr_level" => $level,
    "result_json" => $details
]);

echo json_encode(['success' => true]);
?>