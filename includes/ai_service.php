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
<<<<<<< HEAD
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key=" . GEMINI_API_KEY;
=======
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . GEMINI_API_KEY;
>>>>>>> 33cf4f2697a1be304ab688270f08808189c02e73

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

<<<<<<< HEAD
/* ============================================================
   FALLBACK
============================================================ */

function getFallbackTestQuestions($skill, $cefr, $count)
{
    // Fallback meant to be meaningful for Reading
    if (strtolower($skill) === 'reading') {
        $passage = "The Future of Urban Transportation

As cities around the world continue to grow, the challenge of moving people efficiently and sustainably becomes increasingly urgent. Urban transportation is undergoing a major transformation, driven by technological advancements and shifting environmental priorities.

The rise of electric vehicles (EVs) is a cornerstone of this shift. Governments are incentivizing the adoption of EVs to reduce air pollution and noise levels in densely populated areas. However, simply replacing gas-powered cars with electric ones is not a complete solution. Congestion remains a critical issue. To address this, many cities are investing in smart traffic management systems that use artificial intelligence to optimize traffic light timing and reduce bottlenecks.

Public transportation is also evolving. High-speed trains and autonomous buses are being tested in various metropolises. These innovations aim to make public transit faster, safer, and more convenient than driving. Furthermore, the concept of 'Mobility as a Service' (MaaS) is gaining traction. MaaS platforms integrate different modes of transport—buses, trains, bike-shares, and ride-hailing app —into a single, seamless interface, allowing users to plan and pay for their entire journey with one click.

Another significant trend is the resurgence of cycling and walking. Urban planners are redesigning streets to prioritize pedestrians and cyclists, creating dedicated lanes and car-free zones. This 'active travel' approach not only alleviates traffic but also promotes public health.

Despite these advancements, challenges such as infrastructure costs and equitable access remain. It is crucial that the future of transportation is inclusive, ensuring that all citizens, regardless of income, benefit from these improvements.";

        $questions = [
            [
                "stem" => "What is the primary focus of the passage?",
                "choices" => [
                    "The history of cars in the 20th century.",
                    "The decline of public transportation systems.",
                    "The transformation and future of urban transportation.",
                    "The benefits of living in rural areas."
                ],
                "answer_index" => 2
            ],
            [
                "stem" => "Why are governments encouraging electric vehicles?",
                "choices" => [
                    "To increase noise pollution.",
                    "To reduce air pollution and noise.",
                    "To make cars more expensive.",
                    "To use more fossil fuels."
                ],
                "answer_index" => 1
            ],
            [
                "stem" => "What is 'Mobility as a Service' (MaaS)?",
                "choices" => [
                    "A service that sells luxury cars.",
                    "A platform integrating various transport modes into one interface.",
                    "A new type of high-speed train.",
                    "A law banning cars in cities."
                ],
                "answer_index" => 1
            ],
            [
                "stem" => "How are urban planners promoting 'active travel'?",
                "choices" => [
                    "By removing sidewalks.",
                    "By building more parking lots.",
                    "By creating dedicated lanes for cyclists and pedestrians.",
                    "By increasing speed limits for cars."
                ],
                "answer_index" => 2
            ],
            [
                "stem" => "What remains a challenge mentioned in the conclusion?",
                "choices" => [
                    "The lack of technology.",
                    "The disinterest of the public.",
                    "Infrastructure costs and equitable access.",
                    "The slowness of electric cars."
                ],
                "answer_index" => 2
            ]
        ];

        // Ensure we meet the requested count
        while (count($questions) < $count) {
            $base = $questions[count($questions) % 5];
            $questions[] = [
                "stem" => $base["stem"] . " (Variant " . (count($questions) + 1) . ")",
                "choices" => $base["choices"],
                "answer_index" => $base["answer_index"]
            ];
        }

        return [
            "passage" => $passage,
            "questions" => array_slice($questions, 0, $count),
            "source" => "fallback"
        ];
    }

    // Generic fallback for other skills
    $q = [];
    for ($i = 1; $i <= $count; $i++) {
        $q[] = [
            "stem" => "Sample Question {$i} for {$skill} ({$cefr})",
            "choices" => ["Correct Choice", "Incorrect Choice A", "Incorrect Choice B", "Incorrect Choice C"],
            "answer_index" => 0
=======
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
>>>>>>> 33cf4f2697a1be304ab688270f08808189c02e73
        ];
    }
=======
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . trim(GEMINI_API_KEY);
>>>>>>> Stashed changes

    return [
        "questions" => $q,
        "source" => "fallback"
    ];
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
