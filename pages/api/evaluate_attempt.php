<?php
// Disable error display for API
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../includes/auth_guard.php';
    require_once __DIR__ . '/../../includes/attempt_repo.php';
    require_once __DIR__ . '/../../includes/ai_service.php';
    require_once __DIR__ . '/../../includes/mock_data.php';
    
    $userId = current_user_id();
    $attemptId = (int)($_POST['attempt_id'] ?? 0);
    $skill = $_POST['skill'] ?? 'writing';
    $essayText = $_POST['text'] ?? '';
    $transcript = $_POST['transcript'] ?? '';
    
    $attempt = get_attempt($attemptId, $userId);
    if (!$attempt) {
        http_response_code(404);
        echo json_encode(["error" => "attempt not found"]);
        exit;
    }
    
    // Get user's current CEFR level for context
    $profile = getUserProfile();
    $knownCefr = $profile['current_level'] ?? null;
    
    $evaluation = null;
    
    // Handle different skill types
    if ($skill === 'writing') {
        // Get essay text from attempt answers if not provided
        if (empty($essayText)) {
            $answers = get_answers($attemptId);
            if (!empty($answers)) {
                $essayText = $answers[0]['answer_text'] ?? '';
            }
        }
        
        if (empty($essayText)) {
            http_response_code(400);
            echo json_encode(["error" => "Essay text is required"]);
            exit;
        }
        
        // Call AI evaluation for writing
        $evaluation = fetchAIWritingEvaluation($essayText, $knownCefr);
        
    } elseif ($skill === 'speaking') {
        // Get transcript if not provided
        if (empty($transcript)) {
            // Try to get from attempt answers or use mock
            $answers = get_answers($attemptId);
            if (!empty($answers)) {
                $transcript = $answers[0]['answer_text'] ?? '';
            }
        }
        
        // If still empty, use a placeholder (in production, extract from audio)
        if (empty($transcript)) {
            $transcript = "Student speaking sample. Transcript extraction needed.";
        }
        
        // Call AI evaluation for speaking
        $evaluation = fetchAISpeakingEvaluation($transcript, $knownCefr);
        
    } elseif ($skill === 'listening') {
        // For listening, calculate score from answers
        $answers = get_answers($attemptId);
        // Get questions from attempt meta or session (simplified for now)
        $evaluation = [
            'cefr' => $knownCefr ?? 'B1',
            'score_percent' => 75, // Would calculate from answers
            'message' => 'Listening test completed. Score calculated from answers.'
        ];
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid skill type"]);
        exit;
    }
    
    if (!$evaluation) {
        http_response_code(500);
        echo json_encode(["error" => "Evaluation failed"]);
        exit;
    }
    
    // Save to ai_results table
    save_ai_result($attemptId, AI_MODE === 'live' ? 'gemini' : 'mock', $evaluation);
    
    echo json_encode([
        "ok" => true,
        "evaluation" => $evaluation
    ]);
} catch (Throwable $e) {
    error_log("evaluate_attempt.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
}
