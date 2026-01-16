<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/ai_service.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception("Invalid JSON Input");
    }

    $script = $input['script'] ?? '';
    $questions = $input['questions'] ?? [];
    $answers = $input['answers'] ?? [];

    if (empty($script) || empty($questions)) {
        throw new Exception("Missing script or questions");
    }

    $feedback = evaluateListeningAnswers($script, $questions, $answers);

    // AI often returns a valid JSON string inside the text. Let's try to unwrap it.
    if (is_string($feedback)) {
        // Clean markdown code blocks if any
        $clean = preg_replace('/^```json\s*/i', '', $feedback);
        $clean = preg_replace('/^```\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);

        $decoded = json_decode($clean, true);
        if ($decoded) {
            $feedback = $decoded;
        }
    }

    if (!$feedback) {
        $feedback = [
            "report_title" => "Evaluation Unavailable",
            "grading_notes" => ["mistake_explanation" => "AI did not return a valid response. Please try again or check your specific answer details."]
        ];
    }

    echo json_encode(['feedback' => $feedback]);

} catch (Exception $e) {
    http_response_code(500);
    // Return error as JSON so frontend alerts it
    echo json_encode(['error' => "Server Error: " . $e->getMessage()]);
    error_log("Evaluate Listening Error: " . $e->getMessage());
}
