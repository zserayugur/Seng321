<?php
// includes/ai_service.php
require_once __DIR__ . '/env.php';
require_once 'mock_data.php';

// API KEY LOADING LOGIC
$apiKey = '';
$keyFile = __DIR__ . '/../.gemini_key';

if (file_exists($keyFile)) {
    $apiKey = trim(file_get_contents($keyFile));
}

if (empty($apiKey)) {
    $apiKey = getenv('GEMINI_API_KEY');
}

if (empty($apiKey) && isset($_ENV['GEMINI_API_KEY'])) {
    $apiKey = $_ENV['GEMINI_API_KEY'];
}

if (empty($apiKey)) {
    error_log("AI Service Error: Gemini API Key not found in .gemini_key or environment.");
}

define('GEMINI_API_KEY', $apiKey);

/* ============================================================
   AI RECOMMENDATIONS
============================================================ */

function fetchAIRecommendationsFromChatGPT()
{
    if (empty(GEMINI_API_KEY)) {
        return getFallbackData();
    }

    $raw = geminiCall("You are an expert English tutor. Return raw JSON only. Do not wrap in markdown code blocks. Create a short, motivating study plan for a student at B2 level. Insight must be about language learning consistency. Focus area examples: 'Business Vocabulary', 'Past Perfect Tense', 'IELTS Speaking'. Format: {\"insight_text\":\"...\",\"focus_area\":\"...\",\"daily_plan\":[{\"title\":\"...\",\"duration\":\"...\",\"priority\":\"High/Medium/Low\",\"type\":\"Quiz/Video/Reading\"}],\"resources\":[{\"title\":\"...\",\"description\":\"...\",\"type\":\"Quiz/Video\"}]}");

    // Extract JSON using regex (handles markdown blocks like ```json ... ```)
    if (preg_match('/\{[\s\S]*\}/', $raw, $matches)) {
        $jsonStr = $matches[0];

        // Sometimes AI adds ```json ... ``` wrapper inside the match if we aren't careful, 
        // or control characters. Let's clean it.
        $jsonStr = preg_replace('/^```json\s*/i', '', $jsonStr);
        $jsonStr = preg_replace('/^```\s*/i', '', $jsonStr);
        $jsonStr = preg_replace('/\s*```$/', '', $jsonStr);

        // Remove potentially dangerous control characters
        $jsonStr = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonStr);

        $data = json_decode($jsonStr, true);
        if ($data)
            return $data;

        // If still failed, try decoding the raw match without cleaning (sometimes aggressive cleaning breaks it)
        $data2 = json_decode($matches[0], true);
        if ($data2)
            return $data2;
    }

    // Fallback if parsing fails
    $errorMsg = "AI Parse Error. Raw: " . htmlspecialchars(substr($raw, 0, 100));
    error_log($errorMsg);

    return getFallbackData();
}

/* ============================================================
   AI TEST QUESTIONS
============================================================ */

function fetchAITestQuestions(string $skill, string $cefr, int $count = 20): array
{
    if (empty(GEMINI_API_KEY))
        return getFallbackTestQuestions($skill, $cefr, $count);

    $skill = strtolower($skill);

    // 🔥 READING PROMPT
    if ($skill === "reading") {
        $prompt = "
You are creating an English reading test.

First write a CEFR {$cefr} level reading passage of 120–180 words.

Then create {$count} multiple choice questions based ONLY on that passage.

Rules:
- Return JSON only
- No markdown
- No explanation
- 4 choices each
- answer_index 0–3

Format:
{
  \"passage\": \"Full reading text here\",
  \"questions\": [
     {\"stem\":\"\",\"choices\":[\"\",\"\",\"\",\"\"],\"answer_index\":0}
  ]
}
";
    } else {
        // Grammar / Vocab
        $prompt = "
Return JSON only.
No markdown.
No explanation.

Skill: {$skill}
Level: MIXED (Provide questions ranging from A2 to C1 for better placement. 25% Easy, 50% Medium, 25% Hard)
Count: {$count}

Format:
{
  \"questions\": [
     {\"stem\":\"\",\"choices\":[\"\",\"\",\"\",\"\"],\"answer_index\":0}
  ]
}
";
    }

    $raw = geminiCall($prompt);

    // Extract JSON using regex (handles markdown blocks like ```json ... ```)
    if (!preg_match('/\{[\s\S]*\}/', $raw, $m)) {
        error_log("AI Questions Error: No JSON found in response.");
        return getFallbackTestQuestions($skill, $cefr, $count);
    }

    $jsonStr = $m[0];

    // Clean markdown and control characters
    $jsonStr = preg_replace('/^```json\s*/i', '', $jsonStr);
    $jsonStr = preg_replace('/^```\s*/i', '', $jsonStr);
    $jsonStr = preg_replace('/\s*```$/', '', $jsonStr);
    $jsonStr = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonStr);

    // Try to decode
    $data = json_decode($jsonStr, true);

    // If first attempt fails, try raw match as fallback
    if (!$data) {
        $data = json_decode($m[0], true);
    }

    if (!$data) {
        error_log("AI Questions JSON Decode Error: " . json_last_error_msg());
        return getFallbackTestQuestions($skill, $cefr, $count);
    }

    if (!isset($data["questions"])) {
        return getFallbackTestQuestions($skill, $cefr, $count);
    }

    $normalized = normalizeQuestionsForUI($data["questions"]);

    if (count($normalized) === 0) {
        return getFallbackTestQuestions($skill, $cefr, $count);
    }

    $passage = null;
    if ($skill === "reading") {
        $passage = $data["passage"] ?? null;
    }

    return [
        'skill' => $skill,
        'cefr' => $cefr,
        'passage' => ($skill === 'reading') ? ($aiData['passage'] ?? '') : null,
        'questions' => $questions,
        'source' => 'gemini'
    ];
}

/* ============================================================
   GEMINI CALL
============================================================ */

function geminiCall(string $prompt): string
{
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        'contents' => [[
            'parts' => [['text' => $prompt]]
        ]]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return [
            'cefr' => $knownCefr ?: 'B1',
            'ielts_estimate' => 5.5,
            'toefl_estimate' => 72,
            'diagnostic' => 'Connection error: ' . $err,
            'strengths' => [],
            'improvements' => [],
            'next_steps' => [],
            'word_count' => $wordCount,
            'source' => 'fallback'
        ];
    }
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (isset($decoded['error'])) {
        $msg = $decoded['error']['message'] ?? 'Unknown Gemini error';
        return [
            'cefr' => $knownCefr ?: 'B1',
            'ielts_estimate' => 5.5,
            'toefl_estimate' => 72,
            'diagnostic' => 'Gemini API error: ' . $msg,
            'strengths' => [],
            'improvements' => [],
            'next_steps' => [],
            'word_count' => $wordCount,
            'source' => 'fallback'
        ];
    }

    $rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $rawText = str_replace(["```json", "```"], "", $rawText);
    $ai = json_decode(trim($rawText), true);

    if (!is_array($ai) || empty($ai['cefr'])) {
        return [
            'cefr' => $knownCefr ?: 'B1',
            'ielts_estimate' => 5.5,
            'toefl_estimate' => 72,
            'diagnostic' => 'Parse error. Raw: ' . substr(trim($rawText), 0, 140),
            'strengths' => [],
            'improvements' => [],
            'next_steps' => [],
            'word_count' => $wordCount,
            'source' => 'fallback'
        ];
    }
=======
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . trim(GEMINI_API_KEY);
>>>>>>> Stashed changes

    $data = [
        "questions" => $q,
        "source" => "fallback"
    ];

    if (strtolower($skill) === 'reading') {
        $data['passage'] = "This is a placeholder reading passage because the AI could not be reached. \n\nLearning a language requires consistent practice. Reading daily helps expand vocabulary and understanding of grammar structures. In this mock test, you can practice answering questions even without a generated text, or try refreshing the page to connect to the AI again.";
    }

    return $data;
}

function getFallbackData()
{
    return [
        "insight_text" => "Demo mode",
        "focus_area" => "AI offline",
        "daily_plan" => [],
        "resources" => []
    ];
}
