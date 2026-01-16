<?php
/**
 * AI Service for Language Assessment Platform
 * - Supports Gemini API with fallback to mock mode
 * - Implements caching and throttling to reduce API usage
 * - Provides test question generation and writing evaluation
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/mock_data.php';

// Ensure session is started for caching
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// AI_MODE: 'live' or 'mock' (default to mock if no key)
$aiMode = getenv('AI_MODE') ?: ($_ENV['AI_MODE'] ?? (empty($apiKey) ? 'mock' : 'live'));

if (empty($apiKey) && $aiMode === 'live') {
    error_log("AI Service: AI_MODE=live but GEMINI_API_KEY not found. Falling back to mock mode.");
    $aiMode = 'mock';
}

define('GEMINI_API_KEY', $apiKey);
define('AI_MODE', $aiMode);

// Cache directory (optional file cache)
$cacheDir = __DIR__ . '/../cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

/* ============================================================
   CACHING & THROTTLING
============================================================ */

function getCacheKey(string $prefix, array $params): string {
    return $prefix . '_' . md5(json_encode($params));
}

function getCachedResult(string $cacheKey, int $ttlSeconds = 600): ?array {
    // Session cache
    if (!isset($_SESSION['AI_CACHE'])) {
        $_SESSION['AI_CACHE'] = [];
    }
    
    if (isset($_SESSION['AI_CACHE'][$cacheKey])) {
        $cached = $_SESSION['AI_CACHE'][$cacheKey];
        if (isset($cached['expires_at']) && $cached['expires_at'] > time()) {
            return $cached['data'];
        }
        unset($_SESSION['AI_CACHE'][$cacheKey]);
    }
    
    // File cache (optional, for persistence across sessions)
    global $cacheDir;
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    if (file_exists($cacheFile)) {
        $fileData = json_decode(file_get_contents($cacheFile), true);
        if ($fileData && isset($fileData['expires_at']) && $fileData['expires_at'] > time()) {
            // Also update session cache
            $_SESSION['AI_CACHE'][$cacheKey] = $fileData;
            return $fileData['data'];
        }
    }
    
    return null;
}

function setCachedResult(string $cacheKey, array $data, int $ttlSeconds = 600): void {
    $cacheData = [
        'data' => $data,
        'expires_at' => time() + $ttlSeconds
    ];
    
    // Session cache
    if (!isset($_SESSION['AI_CACHE'])) {
        $_SESSION['AI_CACHE'] = [];
    }
    $_SESSION['AI_CACHE'][$cacheKey] = $cacheData;
    
    // File cache (optional)
    global $cacheDir;
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    @file_put_contents($cacheFile, json_encode($cacheData));
}

// Throttling: prevent rapid successive calls
$lastCallTime = $_SESSION['AI_LAST_CALL_TIME'] ?? 0;
$throttleSeconds = 2;

function checkThrottle(): bool {
    global $lastCallTime, $throttleSeconds;
    $now = time();
    if (($now - $lastCallTime) < $throttleSeconds) {
        return false; // Too soon
    }
    $_SESSION['AI_LAST_CALL_TIME'] = $now;
    return true;
}

/* ============================================================
   GEMINI API CALL
============================================================ */

function geminiCallJson(string $prompt): array|string {
    if (AI_MODE === 'mock' || empty(GEMINI_API_KEY)) {
        return ''; // Will trigger fallback
    }
    
    // Check throttle
    if (!checkThrottle()) {
        error_log("AI Service: Throttled (too soon after last call)");
        return '';
    }
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key=" . GEMINI_API_KEY;
    
    $payload = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
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
        
        if (!$curlError && $httpCode === 200) {
            curl_close($ch);
            $json = json_decode($response, true);
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return $text;
        }
        
        // Retryable errors
        if ($httpCode === 429 || $httpCode >= 500) {
            $attempt++;
            error_log("Gemini Retry {$attempt}/{$maxRetries} due to HTTP {$httpCode}");
            sleep(2 * $attempt);
            continue;
        }
        
        // Fatal error
        error_log("Gemini Fatal Error: HTTP {$httpCode}. Response: " . substr($response, 0, 200));
        break;
    }
    
    curl_close($ch);
    return '';
}

function cleanJson(string $jsonStr): string {
    $jsonStr = preg_replace('/^```json\s*/i', '', $jsonStr);
    $jsonStr = preg_replace('/^```\s*/i', '', $jsonStr);
    $jsonStr = preg_replace('/\s*```$/', '', $jsonStr);
    return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonStr);
}

/* ============================================================
   AI TEST QUESTIONS
============================================================ */

function fetchAITestQuestions(string $skill, string $cefr, int $count, array $options = []): array {
    $skill = strtolower($skill);
    
    // Check cache first
    $cacheKey = getCacheKey('test_questions', ['skill' => $skill, 'cefr' => $cefr, 'count' => $count]);
    $cached = getCachedResult($cacheKey, 600); // 10 min cache
    if ($cached !== null) {
        return $cached;
    }
    
    // If mock mode or no key, return fallback immediately
    if (AI_MODE === 'mock' || empty(GEMINI_API_KEY)) {
        $result = getFallbackTestQuestions($skill, $cefr, $count);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    // Generate prompt
    if ($skill === "reading") {
        $prompt = "
You are creating an English reading test.

REQUIREMENTS:
1. Write a detailed CEFR {$cefr} level reading passage of 250–350 words. The passage MUST be informative and complete.
2. Create exactly {$count} multiple choice questions based ONLY on that passage.
3. Questions must test comprehension of the passage content.

STRICT OUTPUT RULES:
- Return VALID JSON only. No markdown blocks (no ```json).
- The 'passage' field MUST contain the full reading text (non-empty).
- The 'questions' array MUST have exactly {$count} items.
- Each question must have exactly 4 choices.
- answer_index must be 0, 1, 2, or 3.

Format:
{
  \"passage\": \"Full reading text here (250-350 words, CEFR {$cefr} level)\",
  \"questions\": [
    {\"stem\":\"Question text based on passage\",\"choices\":[\"Choice A\",\"Choice B\",\"Choice C\",\"Choice D\"],\"answer_index\":0},
    ...
  ]
}
";
    } else {
        // Grammar or Vocabulary
        $prompt = "
You are an expert English teacher.
Create a JSON object containing exactly {$count} multiple-choice questions to test {$skill}.

CEFR Level: {$cefr}

STRICT OUTPUT RULES:
- Return VALID JSON only. No markdown formatting (no ```json).
- The 'questions' array MUST have exactly {$count} items.
- Each question must have exactly 4 choices.
- answer_index must be 0, 1, 2, or 3.

Format:
{
  \"questions\": [
    {\"stem\":\"Question text...\",\"choices\":[\"Choice A\",\"Choice B\",\"Choice C\",\"Choice D\"],\"answer_index\":0},
    ...
  ]
}
";
    }
    
    $raw = geminiCallJson($prompt);
    
    if (empty($raw)) {
        $result = getFallbackTestQuestions($skill, $cefr, $count);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    // Parse JSON
    $jsonStr = cleanJson($raw);
    if (!preg_match('/\{[\s\S]*\}/', $jsonStr, $m)) {
        $result = getFallbackTestQuestions($skill, $cefr, $count);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    $data = json_decode($m[0], true);
    
    if (!$data) {
        $result = getFallbackTestQuestions($skill, $cefr, $count);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    // Validate and normalize
    if ($skill === "reading") {
        $passage = trim($data["passage"] ?? "");
        if (empty($passage)) {
            error_log("AI Service: Reading passage is empty, using fallback");
            $result = getFallbackTestQuestions($skill, $cefr, $count);
            setCachedResult($cacheKey, $result, 600);
            return $result;
        }
    }
    
    $questions = $data["questions"] ?? [];
    if (!is_array($questions) || count($questions) < $count) {
        error_log("AI Service: Invalid question count, using fallback");
        $result = getFallbackTestQuestions($skill, $cefr, $count);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    $normalized = normalizeQuestionsForUI($questions);
    if (count($normalized) < $count) {
        error_log("AI Service: Normalization failed, using fallback");
        $result = getFallbackTestQuestions($skill, $cefr, $count);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    // Ensure exactly $count questions
    $normalized = array_slice($normalized, 0, $count);
    
    $result = [
        "skill" => $skill,
        "cefr" => $cefr,
        "passage" => ($skill === "reading") ? $passage : null,
        "questions" => $normalized,
        "source" => "gemini"
    ];
    
    setCachedResult($cacheKey, $result, 600);
    return $result;
}

/* ============================================================
   WRITING EVALUATION
============================================================ */

function fetchAIWritingEvaluation(string $essayText, ?string $knownCefr = null): array {
    // Count words locally
    $wordCount = countWords($essayText);
    
    // Check cache
    $cacheKey = getCacheKey('writing_eval', ['text_hash' => md5($essayText), 'cefr' => $knownCefr]);
    $cached = getCachedResult($cacheKey, 3600); // 1 hour cache for same essay
    if ($cached !== null) {
        $cached['word_count'] = $wordCount; // Update word count
        return $cached;
    }
    
    // If mock mode or no key, return fallback
    if (AI_MODE === 'mock' || empty(GEMINI_API_KEY)) {
        $result = getFallbackWritingEvaluation($essayText, $knownCefr, $wordCount);
        setCachedResult($cacheKey, $result, 3600);
        return $result;
    }
    
    $cefrHint = $knownCefr ? " The student's current CEFR level is approximately {$knownCefr}." : "";
    
    $prompt = "
You are an expert English language assessor. Evaluate the following essay and provide a comprehensive assessment.

Essay:
{$essayText}

{$cefrHint}

REQUIREMENTS:
1. Assess the essay's CEFR level (A1, A2, B1, B2, C1, or C2).
2. Provide IELTS band estimate (0.0 to 9.0).
3. Provide TOEFL score estimate (0 to 120).
4. Write a diagnostic summary (2-3 sentences).
5. List 3-5 strengths.
6. List 3-5 areas for improvement.
7. Suggest 2-3 next steps for improvement.

STRICT OUTPUT RULES:
- Return VALID JSON only. No markdown blocks.
- Use exact field names as specified.

Format:
{
  \"cefr\": \"B2\",
  \"ielts_estimate\": 6.5,
  \"toefl_estimate\": 85,
  \"diagnostic\": \"Brief diagnostic summary (2-3 sentences)\",
  \"strengths\": [\"Strength 1\", \"Strength 2\", \"Strength 3\"],
  \"improvements\": [\"Area 1\", \"Area 2\", \"Area 3\"],
  \"next_steps\": [\"Step 1\", \"Step 2\", \"Step 3\"]
}
";
    
    $raw = geminiCallJson($prompt);
    
    if (empty($raw)) {
        $result = getFallbackWritingEvaluation($essayText, $knownCefr, $wordCount);
        setCachedResult($cacheKey, $result, 3600);
        return $result;
    }
    
    // Parse JSON
    $jsonStr = cleanJson($raw);
    if (!preg_match('/\{[\s\S]*\}/', $jsonStr, $m)) {
        $result = getFallbackWritingEvaluation($essayText, $knownCefr, $wordCount);
        setCachedResult($cacheKey, $result, 3600);
        return $result;
    }
    
    $data = json_decode($m[0], true);
    
    if (!$data || !isset($data['cefr'])) {
        $result = getFallbackWritingEvaluation($essayText, $knownCefr, $wordCount);
        setCachedResult($cacheKey, $result, 3600);
        return $result;
    }
    
    // Validate and normalize
    $result = [
        'cefr' => strtoupper(trim($data['cefr'] ?? 'B1')),
        'ielts_estimate' => (float)($data['ielts_estimate'] ?? 5.0),
        'toefl_estimate' => (int)($data['toefl_estimate'] ?? 60),
        'diagnostic' => trim($data['diagnostic'] ?? 'Essay evaluation completed.'),
        'strengths' => is_array($data['strengths'] ?? null) ? $data['strengths'] : [],
        'improvements' => is_array($data['improvements'] ?? null) ? $data['improvements'] : [],
        'next_steps' => is_array($data['next_steps'] ?? null) ? $data['next_steps'] : [],
        'word_count' => $wordCount
    ];
    
    setCachedResult($cacheKey, $result, 3600);
    return $result;
}

function countWords(string $text): int {
    $text = trim($text);
    if (empty($text)) return 0;
    return count(preg_split('/\s+/', $text));
}

/* ============================================================
   HELPERS & NORMALIZER
============================================================ */

function normalizeQuestionsForUI(array $questions): array {
    $out = [];
    if (!is_array($questions)) return [];
    
    foreach ($questions as $q) {
        $stem = $q["stem"] ?? $q["question"] ?? "";
        $choices = $q["choices"] ?? $q["options"] ?? [];
        $ans = $q["answer_index"] ?? $q["answer"] ?? 0;
        
        // Convert letter answer (A-D) to index
        if (is_string($ans) && preg_match('/^[A-D]$/i', $ans)) {
            $ans = ord(strtoupper($ans)) - 65;
        }
        
        // Ensure exactly 4 choices
        if (count($choices) < 4) {
            while (count($choices) < 4) {
                $choices[] = "Option " . (count($choices) + 1);
            }
        }
        $choices = array_slice($choices, 0, 4);
        
        // Validate answer_index
        $ans = max(0, min(3, intval($ans)));
        
        $out[] = [
            "stem" => $stem,
            "choices" => array_values($choices),
            "answer_index" => $ans
        ];
    }
    return $out;
}

/* ============================================================
   FALLBACK FUNCTIONS
============================================================ */

function getFallbackTestQuestions(string $skill, string $cefr, int $count): array {
    if (strtolower($skill) === 'reading') {
        $passage = "The Future of Urban Transportation\n\nAs cities around the world continue to grow, the challenge of moving people efficiently and sustainably becomes increasingly urgent. Urban transportation is undergoing a major transformation, driven by technological advancements and shifting environmental priorities.\n\nThe rise of electric vehicles (EVs) is a cornerstone of this shift. Governments are incentivizing the adoption of EVs to reduce air pollution and noise levels in densely populated areas. However, simply replacing gas-powered cars with electric ones is not a complete solution. Congestion remains a critical issue.\n\nPublic transportation is also evolving. High-speed trains and autonomous buses are being tested in various metropolises. These innovations aim to make public transit faster, safer, and more convenient than driving. Smart city initiatives are integrating real-time data to optimize routes and reduce waiting times.\n\nCycling infrastructure is expanding in many cities, with dedicated bike lanes and bike-sharing programs becoming commonplace. This shift reflects a broader move toward sustainable, healthy, and cost-effective transportation options.";
        
        $baseQuestions = [
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
            ],
            [
                "stem" => "What are smart city initiatives doing?",
                "choices" => ["Building more roads", "Integrating real-time data to optimize routes", "Removing public transport", "Increasing car production"],
                "answer_index" => 1
            ],
            [
                "stem" => "What does the passage say about cycling?",
                "choices" => ["It's declining", "It's expanding with dedicated lanes", "It's only for rural areas", "It's not mentioned"],
                "answer_index" => 1
            ]
        ];
        
        // Fill to count
        $questions = [];
        for ($i = 0; $i < $count; $i++) {
            $base = $baseQuestions[$i % count($baseQuestions)];
            $questions[] = [
                "stem" => $base["stem"] . ($i >= count($baseQuestions) ? " (Question " . ($i + 1) . ")" : ""),
                "choices" => $base["choices"],
                "answer_index" => $base["answer_index"]
            ];
        }
        
        return [
            "skill" => "reading",
            "cefr" => $cefr,
            "passage" => $passage,
            "questions" => $questions,
            "source" => "fallback"
        ];
    }
    
    // Generic fallback for grammar/vocabulary
    $questions = [];
    for ($i = 1; $i <= $count; $i++) {
        $questions[] = [
            "stem" => "Sample Question {$i} for {$skill} (CEFR {$cefr})",
            "choices" => ["Correct Choice", "Choice A", "Choice B", "Choice C"],
            "answer_index" => 0
        ];
    }
    
    return [
        "skill" => $skill,
        "cefr" => $cefr,
        "passage" => null,
        "questions" => $questions,
        "source" => "fallback"
    ];
}

function getFallbackWritingEvaluation(string $essayText, ?string $knownCefr, int $wordCount): array {
    // Simple heuristic-based evaluation
    $cefr = $knownCefr ?? 'B1';
    $wordCountLevel = ($wordCount >= 400) ? 1 : (($wordCount >= 300) ? 0 : -1);
    
    $cefrMap = ['A1' => 0, 'A2' => 1, 'B1' => 2, 'B2' => 3, 'C1' => 4, 'C2' => 5];
    $currentIdx = $cefrMap[$cefr] ?? 2;
    $adjustedIdx = max(0, min(5, $currentIdx + $wordCountLevel));
    $estimatedCefr = array_search($adjustedIdx, $cefrMap);
    
    $ieltsMap = ['A1' => 3.0, 'A2' => 4.0, 'B1' => 5.0, 'B2' => 6.0, 'C1' => 7.0, 'C2' => 8.0];
    $toeflMap = ['A1' => 20, 'A2' => 35, 'B1' => 55, 'B2' => 75, 'C1' => 95, 'C2' => 110];
    
    return [
        'cefr' => $estimatedCefr ?? 'B1',
        'ielts_estimate' => $ieltsMap[$estimatedCefr] ?? 5.0,
        'toefl_estimate' => $toeflMap[$estimatedCefr] ?? 60,
        'diagnostic' => "Essay evaluated in fallback mode. Word count: {$wordCount}. Estimated level: " . ($estimatedCefr ?? 'B1') . ".",
        'strengths' => ['Adequate length', 'Basic structure present', 'Attempts to address topic'],
        'improvements' => ['Expand vocabulary', 'Improve sentence variety', 'Enhance coherence'],
        'next_steps' => ['Practice writing regularly', 'Read more English texts', 'Focus on grammar accuracy'],
        'word_count' => $wordCount
    ];
}

function getFallbackData(): array {
    return [
        "insight_text" => "Demo mode (AI unavailable)",
        "focus_area" => "AI Service",
        "daily_plan" => [],
        "resources" => []
    ];
}

/* ============================================================
   LISTENING TEST GENERATION
============================================================ */

function fetchAIListeningTest(string $cefr, int $part = 1): array {
    // Check cache
    $cacheKey = getCacheKey('listening_test', ['cefr' => $cefr, 'part' => $part]);
    $cached = getCachedResult($cacheKey, 600);
    if ($cached !== null) {
        return $cached;
    }
    
    // If mock mode or no key, return fallback
    if (AI_MODE === 'mock' || empty(GEMINI_API_KEY)) {
        $result = getFallbackListeningTest($cefr, $part);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    $level = ($part === 1) ? 'intermediate' : 'advanced';
    
    $prompt = "
You are creating an English listening test.

REQUIREMENTS:
1. Write a 1-minute listening passage (approximately 150-200 words when read at normal pace) suitable for CEFR {$cefr} level, {$level} difficulty.
2. Create exactly 10 multiple choice questions based ONLY on that passage.
3. Questions must test comprehension of the listening content.

STRICT OUTPUT RULES:
- Return VALID JSON only. No markdown blocks (no ```json).
- The 'passage' field MUST contain the full listening text (non-empty).
- The 'questions' array MUST have exactly 10 items.
- Each question must have exactly 4 choices.
- answer_index must be 0, 1, 2, or 3.

Format:
{
  \"passage\": \"Full listening text here (150-200 words, CEFR {$cefr} level, {$level})\",
  \"questions\": [
    {\"stem\":\"Question text based on passage\",\"choices\":[\"Choice A\",\"Choice B\",\"Choice C\",\"Choice D\"],\"answer_index\":0},
    ...
  ]
}
";
    
    $raw = geminiCallJson($prompt);
    
    if (empty($raw)) {
        $result = getFallbackListeningTest($cefr, $part);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    $jsonStr = cleanJson($raw);
    if (!preg_match('/\{[\s\S]*\}/', $jsonStr, $m)) {
        $result = getFallbackListeningTest($cefr, $part);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    $data = json_decode($m[0], true);
    
    if (!$data || !isset($data['passage']) || empty(trim($data['passage']))) {
        $result = getFallbackListeningTest($cefr, $part);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    $questions = $data['questions'] ?? [];
    if (!is_array($questions) || count($questions) < 10) {
        $result = getFallbackListeningTest($cefr, $part);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    $normalized = normalizeQuestionsForUI($questions);
    if (count($normalized) < 10) {
        $result = getFallbackListeningTest($cefr, $part);
        setCachedResult($cacheKey, $result, 600);
        return $result;
    }
    
    $normalized = array_slice($normalized, 0, 10);
    
    $result = [
        'passage' => trim($data['passage']),
        'questions' => $normalized,
        'part' => $part,
        'cefr' => $cefr,
        'source' => 'gemini'
    ];
    
    setCachedResult($cacheKey, $result, 600);
    return $result;
}

function getFallbackListeningTest(string $cefr, int $part): array {
    $passages = [
        1 => "Good morning, everyone. Today I'd like to talk about the benefits of regular exercise. Exercise is important for our health. It helps us stay strong and feel good. When we exercise, our body releases chemicals that make us happy. Many people exercise in the morning because it gives them energy for the day. You don't need to go to a gym to exercise. Walking, running, or even dancing at home can be great exercise. The key is to do something active every day. Start with just 10 minutes and gradually increase. Remember, any exercise is better than no exercise.",
        2 => "In today's lecture, we'll explore the fascinating world of renewable energy. Solar and wind power have become increasingly cost-effective over the past decade. Governments worldwide are investing billions in infrastructure to support these technologies. However, challenges remain, particularly regarding energy storage and grid stability. Battery technology is advancing rapidly, but we still need more efficient solutions for storing energy during peak production times. Additionally, the transition from fossil fuels requires significant workforce retraining and economic restructuring."
    ];
    
    $passage = $passages[$part] ?? $passages[1];
    
    $baseQuestions = [
        ["stem" => "What is the main topic?", "choices" => ["Exercise benefits", "Cooking recipes", "Travel tips", "Study methods"], "answer_index" => 0],
        ["stem" => "When do many people exercise?", "choices" => ["Evening", "Morning", "Night", "Afternoon"], "answer_index" => 1],
        ["stem" => "What releases chemicals that make us happy?", "choices" => ["Sleeping", "Eating", "Exercise", "Reading"], "answer_index" => 2],
        ["stem" => "Do you need a gym to exercise?", "choices" => ["Yes, always", "No, you can exercise at home", "Only on weekends", "Only in summer"], "answer_index" => 1],
        ["stem" => "What is a good way to start exercising?", "choices" => ["Start with 2 hours", "Start with 10 minutes", "Never start", "Only on holidays"], "answer_index" => 1],
        ["stem" => "What is better than no exercise?", "choices" => ["Watching TV", "Any exercise", "Sleeping", "Eating"], "answer_index" => 1],
        ["stem" => "What can be great exercise?", "choices" => ["Only running", "Walking, running, or dancing", "Only gym", "Only swimming"], "answer_index" => 1],
        ["stem" => "What should you do gradually?", "choices" => ["Decrease exercise", "Increase exercise time", "Stop exercising", "Change diet"], "answer_index" => 1],
        ["stem" => "What helps us stay strong?", "choices" => ["Sleeping", "Exercise", "Eating only", "Reading"], "answer_index" => 1],
        ["stem" => "What is the key message?", "choices" => ["Exercise is difficult", "Do something active every day", "Exercise is boring", "Only exercise in gyms"], "answer_index" => 1]
    ];
    
    if ($part === 2) {
        $baseQuestions = [
            ["stem" => "What is the main topic?", "choices" => ["Renewable energy", "Cooking", "Travel", "History"], "answer_index" => 0],
            ["stem" => "What has become cost-effective?", "choices" => ["Fossil fuels", "Solar and wind power", "Coal", "Oil"], "answer_index" => 1],
            ["stem" => "What is a challenge mentioned?", "choices" => ["Cost", "Energy storage", "Weather", "Time"], "answer_index" => 1],
            ["stem" => "What is advancing rapidly?", "choices" => ["Solar panels", "Battery technology", "Wind turbines", "Coal mining"], "answer_index" => 1],
            ["stem" => "What is needed for peak production?", "choices" => ["More workers", "Efficient storage solutions", "More money", "Better weather"], "answer_index" => 1],
            ["stem" => "What requires workforce retraining?", "choices" => ["Fossil fuel transition", "Renewable energy transition", "Cooking transition", "Travel transition"], "answer_index" => 1],
            ["stem" => "What are governments investing in?", "choices" => ["Coal", "Infrastructure for renewables", "Oil", "Gas"], "answer_index" => 1],
            ["stem" => "What decade is mentioned?", "choices" => ["Past decade", "Next decade", "Current decade", "Future decade"], "answer_index" => 0],
            ["stem" => "What needs restructuring?", "choices" => ["Education", "Economy", "Healthcare", "Transport"], "answer_index" => 1],
            ["stem" => "What is the lecture about?", "choices" => ["Renewable energy world", "Cooking world", "Travel world", "History world"], "answer_index" => 0]
        ];
    }
    
    return [
        'passage' => $passage,
        'questions' => $baseQuestions,
        'part' => $part,
        'cefr' => $cefr,
        'source' => 'fallback'
    ];
}

/* ============================================================
   SPEAKING TOPIC GENERATION
============================================================ */

function fetchAISpeakingTopic(string $cefr): string {
    // Check cache
    $cacheKey = getCacheKey('speaking_topic', ['cefr' => $cefr]);
    $cached = getCachedResult($cacheKey, 600);
    if ($cached !== null && isset($cached['topic'])) {
        return $cached['topic'];
    }
    
    // If mock mode or no key, return fallback
    if (AI_MODE === 'mock' || empty(GEMINI_API_KEY)) {
        $topic = getFallbackSpeakingTopic($cefr);
        setCachedResult($cacheKey, ['topic' => $topic], 600);
        return $topic;
    }
    
    $prompt = "
You are an expert English teacher. Create a speaking topic for a CEFR {$cefr} level student.

REQUIREMENTS:
- The topic should allow the student to speak for 2-3 minutes (150 seconds).
- It should be engaging and relevant.
- Return ONLY the topic/question text, no JSON, no markdown, just the topic.

Example: 'Describe your favorite place to relax. Explain why you like it and what you usually do there.'

Now create a similar topic for {$cefr} level:
";
    
    $raw = geminiCallJson($prompt);
    
    if (empty($raw)) {
        $topic = getFallbackSpeakingTopic($cefr);
        setCachedResult($cacheKey, ['topic' => $topic], 600);
        return $topic;
    }
    
    $topic = trim(cleanJson($raw));
    if (empty($topic)) {
        $topic = getFallbackSpeakingTopic($cefr);
    }
    
    setCachedResult($cacheKey, ['topic' => $topic], 600);
    return $topic;
}

function getFallbackSpeakingTopic(string $cefr): string {
    $topics = [
        "Describe your favorite place to relax. Explain why you like it and what you usually do there.",
        "Talk about a memorable trip you took. Where did you go and what made it special?",
        "Describe a person who has influenced you. How did they impact your life?",
        "Discuss a hobby or activity you enjoy. Why do you like it and how often do you do it?",
        "Talk about your ideal job. What would you do and why would you enjoy it?"
    ];
    return $topics[array_rand($topics)];
}

/* ============================================================
   SPEAKING EVALUATION
============================================================ */

function fetchAISpeakingEvaluation(string $audioTranscript, ?string $knownCefr = null): array {
    // Check cache
    $cacheKey = getCacheKey('speaking_eval', ['transcript_hash' => md5($audioTranscript), 'cefr' => $knownCefr]);
    $cached = getCachedResult($cacheKey, 3600);
    if ($cached !== null) {
        return $cached;
    }
    
    // If mock mode or no key, return fallback
    if (AI_MODE === 'mock' || empty(GEMINI_API_KEY)) {
        $result = getFallbackSpeakingEvaluation($audioTranscript, $knownCefr);
        setCachedResult($cacheKey, $result, 3600);
        return $result;
    }
    
    $cefrHint = $knownCefr ? " The student's current CEFR level is approximately {$knownCefr}." : "";
    
    $prompt = "
You are an expert English language assessor. Evaluate the following speaking transcript and provide a comprehensive assessment.

Transcript:
{$audioTranscript}

{$cefrHint}

REQUIREMENTS:
1. Assess the speaking's CEFR level (A1, A2, B1, B2, C1, or C2).
2. Provide IELTS band estimate (0.0 to 9.0).
3. Provide TOEFL score estimate (0 to 120).
4. Write a diagnostic summary (2-3 sentences).
5. List 3-5 strengths.
6. List 3-5 areas for improvement.
7. Provide a fluency score (0-10).
8. Provide a pronunciation score (0-10).
9. Provide a grammar score (0-10).
10. Provide a vocabulary score (0-10).

STRICT OUTPUT RULES:
- Return VALID JSON only. No markdown blocks.
- Use exact field names as specified.

Format:
{
  \"cefr\": \"B2\",
  \"ielts_estimate\": 6.5,
  \"toefl_estimate\": 85,
  \"diagnostic\": \"Brief diagnostic summary (2-3 sentences)\",
  \"strengths\": [\"Strength 1\", \"Strength 2\", \"Strength 3\"],
  \"improvements\": [\"Area 1\", \"Area 2\", \"Area 3\"],
  \"fluency_score\": 7.5,
  \"pronunciation_score\": 7.0,
  \"grammar_score\": 6.5,
  \"vocabulary_score\": 7.0
}
";
    
    $raw = geminiCallJson($prompt);
    
    if (empty($raw)) {
        $result = getFallbackSpeakingEvaluation($audioTranscript, $knownCefr);
        setCachedResult($cacheKey, $result, 3600);
        return $result;
    }
    
    $jsonStr = cleanJson($raw);
    if (!preg_match('/\{[\s\S]*\}/', $jsonStr, $m)) {
        $result = getFallbackSpeakingEvaluation($audioTranscript, $knownCefr);
        setCachedResult($cacheKey, $result, 3600);
        return $result;
    }
    
    $data = json_decode($m[0], true);
    
    if (!$data || !isset($data['cefr'])) {
        $result = getFallbackSpeakingEvaluation($audioTranscript, $knownCefr);
        setCachedResult($cacheKey, $result, 3600);
        return $result;
    }
    
    // Validate and normalize
    $result = [
        'cefr' => strtoupper(trim($data['cefr'] ?? 'B1')),
        'ielts_estimate' => (float)($data['ielts_estimate'] ?? 5.0),
        'toefl_estimate' => (int)($data['toefl_estimate'] ?? 60),
        'diagnostic' => trim($data['diagnostic'] ?? 'Speaking evaluation completed.'),
        'strengths' => is_array($data['strengths'] ?? null) ? $data['strengths'] : [],
        'improvements' => is_array($data['improvements'] ?? null) ? $data['improvements'] : [],
        'fluency_score' => (float)($data['fluency_score'] ?? 5.0),
        'pronunciation_score' => (float)($data['pronunciation_score'] ?? 5.0),
        'grammar_score' => (float)($data['grammar_score'] ?? 5.0),
        'vocabulary_score' => (float)($data['vocabulary_score'] ?? 5.0),
        'overall_score' => round((($data['fluency_score'] ?? 5.0) + ($data['pronunciation_score'] ?? 5.0) + ($data['grammar_score'] ?? 5.0) + ($data['vocabulary_score'] ?? 5.0)) / 4, 1)
    ];
    
    setCachedResult($cacheKey, $result, 3600);
    return $result;
}

function getFallbackSpeakingEvaluation(string $transcript, ?string $knownCefr): array {
    $cefr = $knownCefr ?? 'B1';
    $wordCount = str_word_count($transcript);
    $levelAdjust = ($wordCount > 100) ? 1 : (($wordCount < 50) ? -1 : 0);
    
    $cefrMap = ['A1' => 0, 'A2' => 1, 'B1' => 2, 'B2' => 3, 'C1' => 4, 'C2' => 5];
    $currentIdx = $cefrMap[$cefr] ?? 2;
    $adjustedIdx = max(0, min(5, $currentIdx + $levelAdjust));
    $estimatedCefr = array_search($adjustedIdx, $cefrMap);
    
    $ieltsMap = ['A1' => 3.0, 'A2' => 4.0, 'B1' => 5.0, 'B2' => 6.0, 'C1' => 7.0, 'C2' => 8.0];
    $toeflMap = ['A1' => 20, 'A2' => 35, 'B1' => 55, 'B2' => 75, 'C1' => 95, 'C2' => 110];
    
    $baseScore = 5.0 + ($levelAdjust * 0.5);
    
    return [
        'cefr' => $estimatedCefr ?? 'B1',
        'ielts_estimate' => $ieltsMap[$estimatedCefr] ?? 5.0,
        'toefl_estimate' => $toeflMap[$estimatedCefr] ?? 60,
        'diagnostic' => "Speaking evaluated in fallback mode. Transcript length: {$wordCount} words. Estimated level: " . ($estimatedCefr ?? 'B1') . ".",
        'strengths' => ['Clear pronunciation', 'Adequate vocabulary', 'Basic grammar structure'],
        'improvements' => ['Increase fluency', 'Use more complex sentences', 'Expand vocabulary range'],
        'fluency_score' => $baseScore,
        'pronunciation_score' => $baseScore + 0.5,
        'grammar_score' => $baseScore - 0.5,
        'vocabulary_score' => $baseScore,
        'overall_score' => round($baseScore, 1)
    ];
}

function fetchAIRecommendationsFromChatGPT(): array {
    if (AI_MODE === 'mock' || empty(GEMINI_API_KEY)) {
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
    
    $raw = geminiCallJson($prompt);
    if (empty($raw)) {
        return getFallbackData();
    }
    
    $jsonStr = cleanJson($raw);
    if (preg_match('/\{[\s\S]*\}/', $jsonStr, $matches)) {
        $data = json_decode($matches[0], true);
        if ($data && isset($data['insight_text'])) {
            return $data;
        }
    }
    
    return getFallbackData();
}
