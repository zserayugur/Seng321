<?php
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

    $raw = geminiCall("You are an expert English tutor. Return JSON only. Create a short, motivating study plan for a student at B2 level. Insight must be about language learning consistency. Focus area examples: 'Business Vocabulary', 'Past Perfect Tense', 'IELTS Speaking'. Format: {\"insight_text\":\"...\",\"focus_area\":\"...\",\"daily_plan\":[{\"title\":\"...\",\"duration\":\"...\",\"priority\":\"High/Medium/Low\",\"type\":\"Quiz/Video/Reading\"}],\"resources\":[{\"title\":\"...\",\"description\":\"...\",\"type\":\"Quiz/Video\"}]}");

    // Extract JSON using regex (handles markdown blocks like ```json ... ```)
    if (preg_match('/\{[\s\S]*\}/', $raw, $matches)) {
        $jsonStr = $matches[0];
        $data = json_decode($jsonStr, true);
        if ($data)
            return $data;
    }

    // Fallback if parsing fails
    error_log("AI Parse Error. Raw output: " . substr($raw, 0, 200));
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
Level: {$cefr}
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

    // Try to decode
    $data = json_decode($m[0], true);

    // If decoding failed, it might be due to control characters. Try to clean it.
    if (json_last_error() !== JSON_ERROR_NONE) {
        $cleanJson = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $m[0]); // Remove control chars
        $data = json_decode($cleanJson, true);
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
        "skill" => $skill,
        "cefr" => $cefr,
        "passage" => $passage,
        "questions" => $normalized,
        "source" => "gemini"
    ];
}

/* ============================================================
   GEMINI CALL
============================================================ */

function geminiCall(string $prompt): string
{
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        "contents" => [["parts" => [["text" => $prompt]]]]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Gemini cURL Error: " . $curlError);
        return "";
    }

    $json = json_decode($response, true);

    if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        error_log("Gemini API unexpected response: " . substr($response, 0, 500));
        return "";
    }

    return $json['candidates'][0]['content']['parts'][0]['text'];
}

/* ============================================================
   NORMALIZER
============================================================ */

function normalizeQuestionsForUI($questions)
{
    $out = [];

    foreach ($questions as $q) {
        $stem = $q["stem"] ?? $q["question"] ?? "";
        $choices = $q["choices"] ?? $q["options"] ?? [];
        $ans = $q["answer_index"] ?? $q["answer"] ?? 0;

        if (is_string($ans) && preg_match('/^[A-D]$/i', $ans)) {
            $ans = ord(strtoupper($ans)) - 65;
        }

        $out[] = [
            "stem" => $stem,
            "choices" => array_values($choices),
            "answer_index" => intval($ans)
        ];
    }

    return $out;
}

/* ============================================================
   FALLBACK
============================================================ */

function getFallbackTestQuestions($skill, $cefr, $count)
{
    $q = [];
    for ($i = 1; $i <= $count; $i++) {
        $q[] = [
            "stem" => "Mock Question {$i} for {$skill} ({$cefr})",
            "choices" => ["Option A", "Option B", "Option C", "Option D"],
            "answer_index" => 0
        ];
    }

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
