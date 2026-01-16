<?php
// includes/ai_service.php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/mock_data.php';

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
    // Batched Request Logic
    // Batching Logic for > 10 questions
    if ($skill !== "reading" && $count > 10) {
        $batchSize = 10;
        $batches = ceil($count / $batchSize);
        $allQuestions = [];

        for ($i = 0; $i < $batches; $i++) {
            $currentRequestCount = ($i === $batches - 1) ? ($count - ($i * $batchSize)) : $batchSize;
            if ($currentRequestCount <= 0)
                break;

            // Use the Robust Prompt
            $prompt = "
You are an expert English teacher.
Create a JSON object containing exactly {$currentRequestCount} multiple-choice questions to test {$skill}.

DIFFICULTY: Mixed (A2 to C1).

STRICT OUTPUT RULES:
1. Return VALID JSON only.
2. No markdown formatting (no ```json).
3. The 'questions' array MUST have exactly {$currentRequestCount} items.

Format:
{
  \"questions\": [
     {\"stem\":\"Question text...\",\"choices\":[\"A\",\"B\",\"C\",\"D\"],\"answer_index\":0}
  ]
}
";
            $raw = geminiCall($prompt);
            $jsonStr = cleanJson($raw);
            $data = json_decode($jsonStr, true);

            // Robust Parsing
            $questionsRaw = [];
            if (isset($data["questions"]) && is_array($data["questions"])) {
                $questionsRaw = $data["questions"];
            } elseif (isset($data["items"]) && is_array($data["items"])) {
                $questionsRaw = $data["items"];
            } elseif (is_array($data) && isset($data[0])) {
                $questionsRaw = $data;
            }

            if (!empty($questionsRaw)) {
                // Normalize immediately
                $normalizedBatch = normalizeQuestionsForUI($questionsRaw);
                foreach ($normalizedBatch as $q) {
                    $allQuestions[] = $q;
                }
            } else {
                error_log("Batch $i failed. Raw: " . substr($jsonStr, 0, 150));
            }

            // Delay for rate limits
            if ($i < $batches - 1)
                sleep(1);
        }

        // If we have at least some questions, return them (don't fail completely)
        if (count($allQuestions) >= 5) {
            return [
                "skill" => $skill,
                "cefr" => $cefr,
                "passage" => null,
                "questions" => array_slice($allQuestions, 0, $count),
                "source" => "gemini_batched"
            ];
        } else {
            error_log("Batching failed completely. Count: " . count($allQuestions));
            return getFallbackTestQuestions($skill, $cefr, $count);
        }
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

    $questionsRaw = [];
    if (isset($data["questions"]) && is_array($data["questions"])) {
        $questionsRaw = $data["questions"];
    } elseif (isset($data["items"]) && is_array($data["items"])) { // Common hallucination
        $questionsRaw = $data["items"];
    } elseif (is_array($data) && isset($data[0]) && is_array($data[0])) {
        // Direct array
        $questionsRaw = $data;
    }

    $normalized = normalizeQuestionsForUI($questionsRaw);

    if (count($normalized) === 0) {
        error_log("AI Service: Failed to normalize questions. Raw JSON: " . substr($jsonStr, 0, 200));
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
            $json = json_decode($response, true);
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Cache’e yaz
            $cacheDir = __DIR__ . "/../cache_ai";
            if (!is_dir($cacheDir))
                mkdir($cacheDir, 0777, true);
            file_put_contents($cacheDir . "/" . sha1($prompt) . ".json", $text);

            curl_close($ch);
            return $text;
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

    // Eğer API başarısızsa ama cache varsa onu kullan
    $cacheFile = __DIR__ . "/../cache_ai/" . sha1($prompt) . ".json";
    if (file_exists($cacheFile)) {
        return file_get_contents($cacheFile);
    }

    return "";

}

/* ============================================================
   HELPERS & NORMALIZER
============================================================ */

function cleanJson($jsonStr)
{
    // Remove markdown code blocks
    $jsonStr = preg_replace('/^```json\s*/i', '', $jsonStr);
    $jsonStr = preg_replace('/^```\s*/i', '', $jsonStr);
    $jsonStr = preg_replace('/\s*```$/', '', $jsonStr);

    // Attempt to extract JSON object if surrounded by text
    if (($start = strpos($jsonStr, '{')) !== false) {
        $jsonStr = substr($jsonStr, $start);
    }
    if (($end = strrpos($jsonStr, '}')) !== false) {
        $jsonStr = substr($jsonStr, 0, $end + 1);
    }

    return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonStr);
}

function normalizeQuestionsForUI($questions)
{
    $out = [];
    // Ensure input is array
    if (!is_array($questions))
        return [];

    foreach ($questions as $q) {
        $stem = $q["stem"] ?? $q["question"] ?? $q["text"] ?? "";

        // Handle choices
        $rawChoices = $q["choices"] ?? $q["options"] ?? $q["answers"] ?? [];
        $choices = [];

        // If choices is ["A"=>"val", "B"=>"val"], allow it but we need mapped index
        if (is_array($rawChoices)) {
            // Check if associative
            if (count(array_filter(array_keys($rawChoices), 'is_string')) > 0) {
                $choices = array_values($rawChoices);
            } else {
                $choices = $rawChoices;
            }
        }

        $ans = $q["answer_index"] ?? $q["answer"] ?? $q["correct_answer"] ?? 0;

        // If ans is string "A", "B", map to index
        if (is_string($ans)) {
            $ans = trim(strtoupper($ans));
            if (strlen($ans) == 1 && $ans >= 'A' && $ans <= 'D') {
                $ans = ord($ans) - 65;
            } elseif (is_numeric($ans)) {
                $ans = intval($ans);
            } else {
                // Try to find the string value in choices? Too complex/risky, default 0
                $ans = 0;
            }
        }

        $out[] = [
            "stem" => $stem,
            "choices" => array_slice(array_values($choices), 0, 4), // Force max 4 and re-index
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

    // Generic fallback for Vocabulary/Grammar (Real Questions now)
    $vocabQuestions = [
        ["stem" => "The company ________ a new marketing strategy last week.", "choices" => ["adopted", "adapted", "adequated", "adepted"], "answer_index" => 0],
        ["stem" => "She is highly ________ in three languages.", "choices" => ["efficient", "proficient", "sufficient", "deficient"], "answer_index" => 1],
        ["stem" => "The scientist made a significant ________ in cancer research.", "choices" => ["breakdown", "breakout", "breakthrough", "breakup"], "answer_index" => 2],
        ["stem" => "Please ________ your seatbelt before takeoff.", "choices" => ["fasten", "tie", "stick", "lock"], "answer_index" => 0],
        ["stem" => "The weather forecast predicts ________ rain for tomorrow.", "choices" => ["heavy", "strong", "thick", "hard"], "answer_index" => 0],
        ["stem" => "He ________ his ambition to become a pilot.", "choices" => ["reached", "fulfilled", "filled", "completed"], "answer_index" => 1],
        ["stem" => "The meeting was ________ due to lack of interest.", "choices" => ["called off", "called for", "called in", "called out"], "answer_index" => 0],
        ["stem" => "It is important to ________ a healthy lifestyle.", "choices" => ["maintain", "remain", "contain", "detain"], "answer_index" => 0],
        ["stem" => "She gave a ________ explanation of the process.", "choices" => ["detail", "details", "detailed", "detailing"], "answer_index" => 2],
        ["stem" => "The cost of living has ________ significantly this year.", "choices" => ["raised", "risen", "arisen", "lifted"], "answer_index" => 1]
    ];

    $grammarQuestions = [
        ["stem" => "If I ________ you, I would accept the offer.", "choices" => ["was", "were", "am", "be"], "answer_index" => 1],
        ["stem" => "She ________ to the gym three times a week.", "choices" => ["go", "goes", "gone", "going"], "answer_index" => 1],
        ["stem" => "I have lived here ________ 2010.", "choices" => ["since", "for", "from", "during"], "answer_index" => 0],
        ["stem" => "We ________ watch TV when the power went out.", "choices" => ["are watching", "were watching", "watched", "have watched"], "answer_index" => 1],
        ["stem" => "You ________ smoke in the hospital.", "choices" => ["don't have to", "mustn't", "needn't", "couldn't"], "answer_index" => 1],
        ["stem" => "By the time we arrive, the movie ________.", "choices" => ["will start", "will have started", "started", "has started"], "answer_index" => 1],
        ["stem" => "He asked me where ________.", "choices" => ["was I going", "I was going", "did I go", "I am going"], "answer_index" => 1],
        ["stem" => "The book ________ was written by Agatha Christie is a bestseller.", "choices" => ["who", "which", "where", "whose"], "answer_index" => 1],
        ["stem" => "I look forward into ________ you soon.", "choices" => ["see", "seeing", "saw", "seen"], "answer_index" => 1],
        ["stem" => "She made me ________ the dishes.", "choices" => ["do", "doing", "to do", "done"], "answer_index" => 0]
    ];

    $q = (strtolower($skill) === 'grammar') ? $grammarQuestions : $vocabQuestions;

    // Ensure we respect the count if possible (though fixed 10 is fine)
    if ($count < 10)
        $q = array_slice($q, 0, $count);

    return [
        "questions" => $q,
        "source" => "fallback_static"
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

/* ============================================================
   LISTENING TEST (Open Ended)
   ============================================================ */

function fetchListeningTest()
{
    if (empty(GEMINI_API_KEY)) {
        // Fallback for demo
        return [
            "script" => "This is a demo listening script. Since AI is unavailable, we cannot generate a long unique text. However, imagine a long lecture about the history of the internet, covering its origins in ARPANET, the development of TCP/IP, the invention of the World Wide Web by Tim Berners-Lee, and the subsequent dot-com boom and bust. It would discuss how social media changed communication and the modern challenges of privacy and cybersecurity...",
            "questions" => [
                "What is the main topic of the lecture?",
                "Who invented the World Wide Web?",
                "What early network is mentioned?",
                "What historical event followed the initial growth?",
                "What modern challenges are discussed?",
                "How did social media impact communication?",
                "What is TCP/IP?",
                "Define the 'dot-com boom'.",
                "What is the speaker's tone?",
                "Summarize the conclusion."
            ]
        ];
    }

    $topics = ["The Future of Artificial Intelligence", "The History of Ancient Civilizations", "Space Exploration and Mars Colonization", "The Psychology of Happiness", "Climate Change and Renewable Energy", "The Evolution of Language"];
    $topic = $topics[array_rand($topics)];

    $prompt = "
You are an expert English examiner creating a listening comprehension test.

1. Write a **LONG and DETAILED** script (approx. 400-500 words) on: '{$topic}'.
   - Rich vocabulary, academic or documentary style.

2. Create 10 **OPEN-ENDED** comprehension questions.

STRICT JSON OUTPUT FORMAT ONLY.
DO NOT WRAP IN MARKDOWN (no ```json ... ``` blocks).
Just return the raw JSON object.

{
  \"script\": \"Full text of the audio script...\",
  \"questions\": [\"Question 1\", \"Question 2\"]
}
";

    $raw = geminiCall($prompt);
    $json = cleanJson($raw);
    $data = json_decode($json, true);

    if ($data && isset($data['script']) && isset($data['questions'])) {
        return $data;
    }

    // Debugging
    error_log("Listening Test Gen Failed. Raw: " . substr($raw, 0, 200));
    error_log("JSON Error: " . json_last_error_msg());

    return [
        "script" => "Error generating content. Please try again. (İçerik oluşturulamadı, lütfen tekrar deneyin.)",
        "questions" => []
    ];
}

function evaluateListeningAnswers($script, $questions, $userAnswers)
{
    if (empty(GEMINI_API_KEY)) {
        return "Demo Feedback: Great job! (AI not connected)";
    }

    $q_a_str = "";
    foreach ($questions as $i => $q) {
        $ans = $userAnswers[$i] ?? "(No answer)";
        $q_a_str .= "Q" . ($i + 1) . ": $q\nStudent Answer: $ans\n\n";
    }

    $prompt = "
You are an English teacher evaluating a listening test.
Script:
$script

Questions and Student Answers:
$q_a_str

Task:
Provide a comprehensive feedback report.
1. Correct the student's answers.
2. Give a score out of 100.
3. Explain mistakes.
4. Keep the tone encouraging but professional.

Output Format: plain text or markdown is fine.
";

    return geminiCall($prompt);
}

/* ============================================================
   SPEAKING TEST
   ============================================================ */

function fetchSpeakingTopic()
{
    $topics = [
        "Describe a memorable holiday you have taken.",
        "Talk about a hobby you enjoy and why.",
        "Discuss the advantages and disadvantages of social media.",
        "Describe a person who has influenced you significantly.",
        "Do you think technology brings people closer or drives them apart?",
        "Talk about your favorite book or movie and why you like it.",
        "Describe a challenge you faced and how you overcame it."
    ];
    $topic = $topics[array_rand($topics)];

    // Optional: Ask AI for a fresher topic? 
    // For speed/reliability, hybrid is best. Let's stick to list or allow AI enhancement.
    // Let's use AI to make it more exam-like.

    if (!empty(GEMINI_API_KEY)) {
        $prompt = "Generate a single interesting IELTS Speaking Part 2 style topic card. Return ONLY the topic text, no extra formatting.";
        $aiTopic = geminiCall($prompt);
        if (strlen($aiTopic) > 10 && strlen($aiTopic) < 200) {
            $topic = trim($aiTopic);
        }
    }

    return $topic;
}

function evaluateSpeakingAttempt($topic, $transcript)
{
    if (empty(GEMINI_API_KEY)) {
        return [
            "score" => 75,
            "cefr" => "B1",
            "feedback" => "Demo Feedback: Since AI is offline, I can't evaluate your speech properly. However, good effort! normally I would analyze your grammar, vocabulary, and relevance to the topic: '$topic'.",
            "corrections" => "Check your verb tenses."
        ];
    }

    $prompt = "
You are an expert IELTS/TOEFL examiner.
Topic: \"$topic\"
Student's Spoken Response (Transcribed): \"$transcript\"

Task:
Evaluate the student's speaking performance based on the transcript.
1. Assign a CEFR Level (A1-C2).
2. Give a Score (0-100).
3. Provide feedback on Vocabulary, Grammar, and Coherence.
4. Suggest specific improvements.

Return VALID JSON only:
{
  \"cefr\": \"B2\",
  \"score\": 85,
  \"feedback\": \"Detailed feedback here...\",
  \"corrections\": \"Specific corrections here...\"
}
";

    $raw = geminiCall($prompt);
    $json = cleanJson($raw);
    $data = json_decode($json, true);

    if ($data && isset($data['cefr'])) {
        return $data;
    }

    return [
        "score" => 0,
        "cefr" => "N/A",
        "feedback" => "Error evaluating speech. Please try again.",
        "corrections" => ""
    ];
}
/* ============================================================
   WRITING TEST
   ============================================================ */

function fetchWritingTopic()
{
    $topics = [
        "Some people believe that the best way to travel is in a group led by a tour guide. Others prefer to travel alone. Discuss both views and give your opinion.",
        "In many countries, children are engaged in some kind of paid work. Some people regard this as completely wrong, while others consider it as valuable work experience, important for learning and taking responsibility. Discuss both views and give your opinion.",
        "Some people think that strict punishments for driving offenses are the key to reducing traffic accidents. Others, however, believe that other measures would be more effective in improving road safety. Discuss both these views and give your own opinion.",
        "Some people think that schools should teach students how to be good citizens and not just focus on academic subjects. To what extent do you agree or disagree?",
        "Nowadays many people choose to be self-employed, rather than to work for a company or organization. Why is this the case? What could be the disadvantages of being self-employed?"
    ];
    $topic = $topics[array_rand($topics)];

    // AI Enhancement
    if (!empty(GEMINI_API_KEY)) {
        $prompt = "Generate a single interesting IELTS Writing Task 2 essay topic. Return ONLY the topic text, no extra formatting.";
        $aiTopic = geminiCall($prompt);
        if (strlen($aiTopic) > 20 && strlen($aiTopic) < 400) {
            $topic = trim($aiTopic);
        }
    }

    return $topic;
}

function evaluateWritingAttempt($topic, $text)
{
    if (empty(GEMINI_API_KEY)) {
        return [
            "score" => 65,
            "cefr" => "B1",
            "feedback" => "Demo Mode: Good effort. Since AI is offline, I can't analyze your writing depth. Try to use more complex sentences.",
            "correction_points" => "Check subject-verb agreement."
        ];
    }

    $prompt = "
You are an expert IELTS/TOEFL examiner.
Topic: \"$topic\"
Student's Essay: 
\"$text\"

Task:
Evaluate the essay based on:
1. Grammar & Variance (Sentence Structure)
2. Vocabulary (Lexical Resource)
3. Coherence & Cohesion
4. Task Response

Return VALID JSON only:
{
  \"cefr\": \"B2\",
  \"score\": 78,
  \"feedback\": \"Detailed feedback on grammar, structure, and vocabulary...\",
  \"correction_points\": \"List of specific errors and suggested fixes...\"
}
";

    $raw = geminiCall($prompt);
    $json = cleanJson($raw);
    $data = json_decode($json, true);

    if ($data && isset($data['cefr'])) {
        return $data;
    }

    return [
        "score" => 0,
        "cefr" => "N/A",
        "feedback" => "Error evaluating essay. Please try again.",
        "correction_points" => ""
    ];
}
?>