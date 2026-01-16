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

    $prompt = "
You are an expert English tutor. 
Create a study plan for a B2 student.
Return VALID JSON only. No markdown. No comments.

Format:
{
  \"insight_text\": \"Short motivating insight about consistency.\",
  \"focus_area\": \"e.g. Business Vocabulary\",
  \"daily_plan\": [
     {\"title\":\"Task 1\",\"duration\":\"15 min\",\"priority\":\"High\",\"type\":\"Quiz\"},
     {\"title\":\"Task 2\",\"duration\":\"10 min\",\"priority\":\"Medium\",\"type\":\"Video\"}
  ],
  \"resources\": [
     {\"title\":\"Resource 1\",\"description\":\"Desc...\",\"type\":\"Reading\"}
  ]
}
";

    $raw = geminiCall($prompt);

    if (preg_match('/\{[\s\S]*\}/', $raw, $matches)) {
        $jsonStr = $matches[0];
        $jsonStr = cleanJson($jsonStr);

        $data = json_decode($jsonStr, true);
        if ($data && isset($data['insight_text'])) {
            return $data;
        }
    }

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

    // BATCHING LOGIC FOR NON-READING TESTS > 10 QUESTIONS
    // This ensures we get exactly the requested number of questions by breaking it down.
    if ($skill !== "reading" && $count > 10) {
        $batchSize = 10;
        $batches = ceil($count / $batchSize);
        $allQuestions = [];

        for ($i = 0; $i < $batches; $i++) {
            $currentRequestCount = ($i === $batches - 1) ? ($count - ($i * $batchSize)) : $batchSize;
            if ($currentRequestCount <= 0)
                break;

            $prompt = "
You are an expert English teacher.
Create a JSON object containing exactly {$currentRequestCount} multiple-choice questions to test {$skill}.

DIFFICULTY: Mixed (A2 to C1).

STRICT RULES:
1. Return VALID JSON only.
2. The 'questions' array MUST have exactly {$currentRequestCount} items.
3. No markdown blocks.

Format:
{\"questions\": [{\"stem\":\"...\",\"choices\":[\"A\",\"B\",\"C\",\"D\"],\"answer_index\":0}, ...]}
";
            $raw = geminiCall($prompt);

            $jsonStr = $raw;
            $jsonStr = cleanJson($jsonStr);
            $data = json_decode($jsonStr, true);

            if ($data && isset($data['questions'])) {
                foreach ($data['questions'] as $q) {
                    $allQuestions[] = $q;
                }
            }

            // Short delay to avoid rate limits
            if ($i < $batches - 1)
                usleep(500000);
        }

        if (empty($allQuestions)) {
            return getFallbackTestQuestions($skill, $cefr, $count);
        }

        $allQuestions = array_slice($allQuestions, 0, $count);

        return [
            "skill" => $skill,
            "cefr" => $cefr,
            "passage" => null,
            "questions" => normalizeQuestionsForUI($allQuestions),
            "source" => "gemini_batched"
        ];
    }

    // STANDARD LOGIC (Reading or Small Counts)
    if ($skill === "reading") {
        $prompt = "
You are creating an English reading test.

First write a long and detailed CEFR {$cefr} level reading passage of 250–350 words.

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
        $prompt = "
You are an expert English teacher.
Create a JSON object containing exactly {$count} multiple-choice questions to test {$skill}.

Difficulty Distribution:
- 5 Easy (A2)
- 10 Medium (B1-B2)
- 5 Hard (C1)

Strict Output Rules:
- Return valid JSON only.
- No markdown formatting (no ```json).
- The 'questions' array MUST have exactly {$count} items.

Format:
{
  \"questions\": [
     {\"stem\":\"Question text...\",\"choices\":[\"A\",\"B\",\"C\",\"D\"],\"answer_index\":0},
     ...
  ]
}
";
    }

    $raw = geminiCall($prompt);

    if (!preg_match('/\{[\s\S]*\}/', $raw, $m)) {
        return getFallbackTestQuestions($skill, $cefr, $count);
    }

    $jsonStr = cleanJson($m[0]);
    $data = json_decode($jsonStr, true);

    if (!$data || !isset($data["questions"])) {
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
    // Using valid working model
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "start_of_sequence_token" => null,
            "response_mime_type" => "application/json",
            "maxOutputTokens" => 8192,
            "temperature" => 0.7
        ]
    ];

    $maxRetries = 3;
    $attempt = 0;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 45
    ]);

    while ($attempt < $maxRetries) {
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Success
        if (!$curlError && $httpCode === 200) {
            curl_close($ch);
            $json = json_decode($response, true);
            return $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }

        // Retryable errors: 429 (Too Many Requests) or 5xx (Server Error)
        if ($httpCode === 429 || $httpCode >= 500) {
            $attempt++;
            error_log("Gemini Retry {$attempt}/{$maxRetries} due to HTTP {$httpCode}...");
            sleep(2 * $attempt); // Backoff: 2s, 4s, 6s...
            continue;
        }

        // Fatal error (400, 401, 403, 404, etc.)
        error_log("Gemini Fatal Error: HTTP {$httpCode}. Response: " . substr($response, 0, 200));
        break;
    }

    curl_close($ch);
    return "";
}

/* ============================================================
   HELPERS & NORMALIZER
============================================================ */

function cleanJson($jsonStr)
{
    $jsonStr = preg_replace('/^```json\s*/i', '', $jsonStr);
    $jsonStr = preg_replace('/^```\s*/i', '', $jsonStr);
    $jsonStr = preg_replace('/\s*```$/', '', $jsonStr);
    return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonStr);
}

function normalizeQuestionsForUI($questions)
{
    $out = [];
    // Ensure input is array
    if (!is_array($questions))
        return [];

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
    if (strtolower($skill) === 'reading') {
        $passage = "The Future of Urban Transportation\n\nAs cities around the world continue to grow, the challenge of moving people efficiently and sustainably becomes increasingly urgent. Urban transportation is undergoing a major transformation, driven by technological advancements and shifting environmental priorities.\n\nThe rise of electric vehicles (EVs) is a cornerstone of this shift. Governments are incentivizing the adoption of EVs to reduce air pollution and noise levels in densely populated areas. However, simply replacing gas-powered cars with electric ones is not a complete solution. Congestion remains a critical issue.\n\nPublic transportation is also evolving. High-speed trains and autonomous buses are being tested in various metropolises. These innovations aim to make public transit faster, safer, and more convenient than driving.";

        $questions = [
            [
                "stem" => "What is the primary focus of the passage?",
                "choices" => ["History of cars", "Decline of public transport", "Transformation of urban transport", "Rural living"],
                "answer_index" => 2
            ],
            [
                "stem" => "Why are governments encouraging electric vehicles?",
                "choices" => ["To increase noise", "To reduce air pollution and noise", "To make cars expensive", "To use fossil fuels"],
                "answer_index" => 1
            ],
            [
                "stem" => "What remains a critical issue despite EVs?",
                "choices" => ["Speed", "Safety", "Congestion", "Comfort"],
                "answer_index" => 2
            ]
        ];

        // Fill up to count
        while (count($questions) < $count) {
            $base = $questions[count($questions) % 3];
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

    // Generic fallback
    $q = [];
    for ($i = 1; $i <= $count; $i++) {
        $q[] = [
            "stem" => "Sample Question {$i} for {$skill} ({$cefr})",
            "choices" => ["Correct Choice", "Choice A", "Choice B", "Choice C"],
            "answer_index" => 0
        ];
    }

    return [
        "questions" => $q,
        "source" => "fallback"
    ];
}

function getFallbackData()
{
    return [
        "insight_text" => "Demo mode (AI unavailable)",
        "focus_area" => "AI Service",
        "daily_plan" => [],
        "resources" => []
    ];
}
?>