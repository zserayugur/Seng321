<?php
// includes/ai_service.php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/mock_data.php';

/* ============================================================
   API KEY LOADING ( .gemini_key > env )
============================================================ */

$apiKey = '';
$keyFile = __DIR__ . '/../.gemini_key';

if (file_exists($keyFile)) {
    $apiKey = trim((string)file_get_contents($keyFile));
}

if ($apiKey === '') {
    $apiKey = (string)getenv('GEMINI_API_KEY');
}
if ($apiKey === '' && isset($_ENV['GEMINI_API_KEY'])) {
    $apiKey = (string)$_ENV['GEMINI_API_KEY'];
}

define('GEMINI_API_KEY', trim($apiKey));

/* ============================================================
   SMALL HELPERS
============================================================ */

function ai_mode(): string {
    return strtolower(trim(getenv('AI_MODE') ?: 'live')); // live | mock
}

function ensure_session_started(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Gemini raw call -> returns raw text or throws Exception
 */
function gemini_generate_text(string $prompt): string
{
    if (GEMINI_API_KEY === '') {
        throw new Exception("Gemini API key missing");
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key='
         . urlencode(GEMINI_API_KEY);

    $payload = [
        'contents' => [[
            'parts' => [[ 'text' => $prompt ]]
        ]]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    // XAMPP için SSL kapalı (prod'da aç)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $resp = curl_exec($ch);

    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("Gemini connection error: " . $err);
    }
    curl_close($ch);

    $decoded = json_decode((string)$resp, true);
    if (!is_array($decoded)) {
        throw new Exception("Gemini response not JSON");
    }
    if (isset($decoded['error'])) {
        $msg = $decoded['error']['message'] ?? 'Unknown Gemini error';
        throw new Exception("Gemini API error: " . $msg);
    }

    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $text = str_replace(["```json", "```"], "", (string)$text);
    $text = trim($text);

    if ($text === '') {
        throw new Exception("Gemini returned empty text");
    }

    return $text;
}

/* ============================================================
   AI RECOMMENDATIONS (AI Coach)
============================================================ */

function fetchAIRecommendationsFromChatGPT()
{
    $userProfile = getUserProfile();
    $recentResults = getTestResults();

    if (ai_mode() === 'mock') {
        return getFallbackData();
    }

    if (GEMINI_API_KEY === '') {
        return getFallbackData();
    }

    $prompt = "
You are an expert English language tutor. Analyze this student:
Profile: " . json_encode($userProfile) . "
Recent Results: " . json_encode($recentResults) . "

Output valid JSON only. No markdown. No backticks.
Structure:
{
  \"insight_text\": \"A short 2-sentence diagnostic insight.\",
  \"focus_area\": \"Short phrase (e.g. Grammar & Fluency)\",
  \"daily_plan\": [
    {\"title\":\"Task Name\",\"type\":\"grammar/listening/speaking\",\"duration\":\"15 min\",\"priority\":\"High/Medium/Low\"}
  ],
  \"resources\": [
    {\"title\":\"Resource Name\",\"type\":\"Article/Video/Quiz\",\"description\":\"Short description\"}
  ]
}
Provide exactly 3 items for daily_plan and 3 items for resources.
";

    try {
        $raw = gemini_generate_text($prompt);
        $data = json_decode($raw, true);
        if (is_array($data)) return $data;

        $fallback = getFallbackData();
        $fallback['insight_text'] = "Gemini Parse Error. Raw head: " . substr($raw, 0, 120);
        return $fallback;

    } catch (Throwable $e) {
        $fallback = getFallbackData();
        $fallback['insight_text'] = "AI Error: " . $e->getMessage();
        return $fallback;
    }
<<<<<<< HEAD
=======

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
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemma-3-1b-it:generateContent?key=" . GEMINI_API_KEY;

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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        $msg = "Gemini cURL Error: " . $curlError;
        error_log($msg);
        return '{"error": "' . $msg . '"}'; // Return error as JSON so caller sees it
    }

    if ($httpCode !== 200) {
        $msg = "Gemini HTTP Error: {$httpCode}. Response: " . substr($response, 0, 200);
        error_log($msg);
        return '{"error": "' . $msg . '"}';
    }

    $json = json_decode($response, true);

    if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        error_log("Gemini API unexpected response: " . substr($response, 0, 500));
        return $response; // Return raw so we can debug
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
>>>>>>> 04157ae9377dafc54a14f8937bf7ed7f79e6bf77
}

function getFallbackData()
{
    return [
        'insight_text' => "Based on your recent tests, our AI usually detects patterns here. (Fallback Mode)",
        'focus_area' => "Demo Mode",
        'daily_plan' => getAiRecommendations(),
        'resources' => [
            ['title' => 'Advanced Grammar Guide', 'type' => 'Article', 'description' => 'Comprehensive guide to complex structures.'],
            ['title' => 'BBC Learning English', 'type' => 'Video', 'description' => 'Daily news review in English.'],
            ['title' => 'IELTS Mock Test 4', 'type' => 'Quiz', 'description' => 'Full length practice test.']
        ]
    ];
}

/* ============================================================
   TEST QUESTION GENERATOR (Grammar / Vocabulary / Reading)
============================================================ */

function fetchAITestQuestions(string $skill, string $cefr, int $count = 20): array
{
    $skill = strtolower(trim($skill));
    $cefr  = strtoupper(trim($cefr));
    if ($cefr === '') $cefr = 'B1';
    if ($count < 1) $count = 8;

    // mock veya key yoksa fallback
    if (ai_mode() === 'mock' || GEMINI_API_KEY === '') {
        return getFallbackTestQuestions($skill, $cefr, $count);
    }

    // Prompt
    if ($skill === 'reading') {
        // Reading: passage + questions
        $prompt = "
You are creating an English READING test.

CEFR Level: {$cefr}
Passage length: 120-180 words (single passage)
Questions: {$count}

Rules:
- Return JSON only (no markdown, no backticks).
- Questions must be based ONLY on the passage.
- Exactly {$count} questions.
- Each has 4 choices.
- answer_index 0..3.

Return EXACT JSON structure:
{
  \"passage\": \"...\",
  \"questions\": [
    {\"stem\":\"...\",\"choices\":[\"A\",\"B\",\"C\",\"D\"],\"answer_index\":0}
  ]
}
";
    } else {
        $topicHint = ($skill === 'vocabulary')
            ? 'vocabulary meaning/usage'
            : 'grammar structures';

        $prompt = "
You are an English assessment item writer.
Create CEFR-aligned multiple-choice questions.

Skill: {$skill}
CEFR Level: {$cefr}
Count: {$count}

Rules:
- Return VALID JSON ONLY (no markdown, no backticks).
- Exactly {$count} questions.
- Each question has exactly 4 choices.
- answer_index is 0..3 and matches the correct choice.
- Keep difficulty appropriate for CEFR {$cefr}.
- Focus on: {$topicHint}.

Return EXACT JSON structure:
{
  \"questions\": [
    {\"stem\":\"...\",\"choices\":[\"A\",\"B\",\"C\",\"D\"],\"answer_index\":0}
  ]
}
";
    }

    // Call
    try {
        $raw = gemini_generate_text($prompt);
        $aiData = json_decode($raw, true);

        if (!is_array($aiData)) {
            $fb = getFallbackTestQuestions($skill, $cefr, $count);
            $fb['debug'] = "Gemini parse error. Raw head: " . substr($raw, 0, 140);
            return $fb;
        }

        // Reading passage zorunlu
        $passage = null;
        if ($skill === 'reading') {
            $passage = trim((string)($aiData['passage'] ?? ''));
            if ($passage === '') {
                $fb = getFallbackTestQuestions($skill, $cefr, $count);
                $fb['debug'] = "Gemini returned empty passage.";
                return $fb;
            }
        }

        if (!isset($aiData['questions']) || !is_array($aiData['questions'])) {
            $fb = getFallbackTestQuestions($skill, $cefr, $count);
            $fb['debug'] = "Gemini missing questions array.";
            return $fb;
        }

        $questions = array_slice($aiData['questions'], 0, $count);

        // validate
        foreach ($questions as $q) {
            if (!isset($q['stem'], $q['choices'], $q['answer_index'])) {
                $fb = getFallbackTestQuestions($skill, $cefr, $count);
                $fb['debug'] = "Invalid question structure.";
                return $fb;
            }
            if (!is_array($q['choices']) || count($q['choices']) !== 4) {
                $fb = getFallbackTestQuestions($skill, $cefr, $count);
                $fb['debug'] = "Each question must have exactly 4 choices.";
                return $fb;
            }
            $ai = (int)$q['answer_index'];
            if ($ai < 0 || $ai > 3) {
                $fb = getFallbackTestQuestions($skill, $cefr, $count);
                $fb['debug'] = "answer_index out of range.";
                return $fb;
            }
        }

        return [
            'skill' => $skill,
            'cefr' => $cefr,
            'passage' => ($skill === 'reading') ? $passage : null,
            'questions' => $questions,
            'source' => 'gemini'
        ];

    } catch (Throwable $e) {
        $fb = getFallbackTestQuestions($skill, $cefr, $count);
        $fb['debug'] = $e->getMessage();
        return $fb;
    }
}

function getFallbackTestQuestions(string $skill, string $cefr, int $count = 8): array
{
    $skill = strtolower(trim($skill));
    $cefr  = strtoupper(trim($cefr));
    if ($cefr === '') $cefr = 'B1';

    $qs = [];
    for ($i = 0; $i < $count; $i++) {
        $qs[] = [
            'stem' => strtoupper($skill) . " (CEFR {$cefr}) Q" . ($i + 1) . ": Choose the correct answer.",
            'choices' => ["Option A", "Option B", "Option C", "Option D"],
            'answer_index' => 1
        ];
    }

    return [
        'skill' => $skill,
        'cefr' => $cefr,
        'passage' => ($skill === 'reading') ? "Fallback reading passage (CEFR {$cefr})." : null,
        'questions' => $qs,
        'source' => 'fallback'
    ];
}

/* ============================================================
   WRITING EVALUATION (CEFR + IELTS + TOEFL)
============================================================ */

function fetchAIWritingEvaluation(string $text, string $knownCefr = ''): array
{
    ensure_session_started();

    $text = trim((string)$text);
    $knownCefr = strtoupper(trim((string)$knownCefr));
    $wordCount = str_word_count(preg_replace('/\s+/', ' ', strip_tags($text)));

    if ($wordCount < 30) {
        return [
            'cefr' => $knownCefr ?: 'A2',
            'ielts_estimate' => 4.0,
            'toefl_estimate' => 45,
            'diagnostic' => 'Text too short to evaluate reliably (min ~30 words).',
            'strengths' => [],
            'improvements' => ['Write a longer response with clearer structure.'],
            'next_steps' => ['Aim for 250–450 words as required.'],
            'word_count' => $wordCount,
            'source' => 'fallback'
        ];
    }

    if (ai_mode() === 'mock' || GEMINI_API_KEY === '') {
        return [
            'cefr' => $knownCefr ?: 'B1',
            'ielts_estimate' => 5.5,
            'toefl_estimate' => 72,
            'diagnostic' => (ai_mode() === 'mock') ? 'Mock evaluation mode (AI_MODE=mock).' : 'API key missing. Using fallback evaluation.',
            'strengths' => ['Message is understandable.'],
            'improvements' => ['Use more varied sentence structures.'],
            'next_steps' => ['Add linking words (however, therefore, although).'],
            'word_count' => $wordCount,
            'source' => (ai_mode() === 'mock') ? 'mock' : 'fallback'
        ];
    }

    // cache (6 saat)
    $cacheKey = 'writing_eval_' . hash('sha256', $knownCefr . '|' . $text);
    if (isset($_SESSION['AI_CACHE'][$cacheKey])) {
        $item = $_SESSION['AI_CACHE'][$cacheKey];
        if (is_array($item) && (time() - (int)($item['ts'] ?? 0)) < 6 * 3600) {
            $data = $item['data'] ?? null;
            if (is_array($data)) {
                $data['source'] = 'cache';
                return $data;
            }
        }
    }

    // throttle (15 sn)
    $tKey = $cacheKey . '_ts';
    $last = (int)($_SESSION['AI_THROTTLE'][$tKey] ?? 0);
    if ($last && (time() - $last) < 15) {
        return [
            'cefr' => $knownCefr ?: 'B1',
            'ielts_estimate' => 5.5,
            'toefl_estimate' => 72,
            'diagnostic' => 'Throttled: please wait a few seconds and try again.',
            'strengths' => [],
            'improvements' => [],
            'next_steps' => [],
            'word_count' => $wordCount,
            'source' => 'throttle'
        ];
    }
    $_SESSION['AI_THROTTLE'][$tKey] = time();

    $prompt = "
You are an expert English writing assessor.
Evaluate the student's essay and output VALID JSON only (no markdown, no backticks).

Known CEFR (if provided): {$knownCefr}

Return EXACTLY this JSON structure:
{
  \"cefr\": \"A1/A2/B1/B2/C1/C2\",
  \"ielts_estimate\": 0.0,
  \"toefl_estimate\": 0,
  \"diagnostic\": \"2-3 sentences\",
  \"strengths\": [\"...\",\"...\",\"...\"],
  \"improvements\": [\"...\",\"...\",\"...\"],
  \"next_steps\": [\"...\",\"...\",\"...\"]
}

Rules:
- Determine CEFR from the text itself.
- IELTS estimate: 0.0-9.0 (one decimal).
- TOEFL iBT estimate: 0-120 (integer).
- Keep feedback concise and practical.
- Output JSON ONLY.

Student essay:
\"\"\"{$text}\"\"\"
";

    try {
        $raw = gemini_generate_text($prompt);
        $ai = json_decode($raw, true);

        if (!is_array($ai) || empty($ai['cefr'])) {
            return [
                'cefr' => $knownCefr ?: 'B1',
                'ielts_estimate' => 5.5,
                'toefl_estimate' => 72,
                'diagnostic' => 'Parse error. Raw head: ' . substr($raw, 0, 140),
                'strengths' => [],
                'improvements' => [],
                'next_steps' => [],
                'word_count' => $wordCount,
                'source' => 'fallback'
            ];
        }

        $result = [
            'cefr' => strtoupper(trim((string)$ai['cefr'])),
            'ielts_estimate' => round((float)($ai['ielts_estimate'] ?? 0), 1),
            'toefl_estimate' => (int)($ai['toefl_estimate'] ?? 0),
            'diagnostic' => trim((string)($ai['diagnostic'] ?? '')),
            'strengths' => is_array($ai['strengths'] ?? null) ? $ai['strengths'] : [],
            'improvements' => is_array($ai['improvements'] ?? null) ? $ai['improvements'] : [],
            'next_steps' => is_array($ai['next_steps'] ?? null) ? $ai['next_steps'] : [],
            'word_count' => $wordCount,
            'source' => 'gemini'
        ];

        $_SESSION['AI_CACHE'][$cacheKey] = ['ts' => time(), 'data' => $result];
        return $result;

    } catch (Throwable $e) {
        return [
            'cefr' => $knownCefr ?: 'B1',
            'ielts_estimate' => 5.5,
            'toefl_estimate' => 72,
            'diagnostic' => 'AI error: ' . $e->getMessage(),
            'strengths' => [],
            'improvements' => [],
            'next_steps' => [],
            'word_count' => $wordCount,
            'source' => 'fallback'
        ];
    }
}