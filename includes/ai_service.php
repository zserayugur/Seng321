<?php
require_once __DIR__ . '/env.php';
require_once 'mock_data.php';

define('GEMINI_API_KEY', trim(file_get_contents(__DIR__ . '/../.gemini_key')));

/* ============================================================
   AI RECOMMENDATIONS
============================================================ */

function fetchAIRecommendationsFromChatGPT()
{
    if (empty(GEMINI_API_KEY)) return getFallbackData();

    $raw = geminiCall("Return JSON only. Give short study plan.");
    $data = json_decode($raw, true);

    return $data ?: getFallbackData();
}

/* ============================================================
   AI TEST QUESTIONS
============================================================ */

function fetchAITestQuestions(string $skill, string $cefr, int $count = 20): array
{
    if (empty(GEMINI_API_KEY)) return getFallbackTestQuestions($skill, $cefr, $count);

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

    if (!preg_match('/\{[\s\S]*\}/', $raw, $m)) {
        return getFallbackTestQuestions($skill, $cefr, $count);
    }

    $data = json_decode($m[0], true);

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
        "contents" => [[ "parts" => [[ "text" => $prompt ]] ]]
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
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['candidates'][0]['content']['parts'][0]['text'] ?? "";
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
    for ($i=1;$i<=$count;$i++) {
        $q[] = [
            "stem" => "{$skill} {$cefr} Q{$i}",
            "choices" => ["A","B","C","D"],
            "answer_index" => 1
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
        "insight_text" => "Demo mode",
        "focus_area" => "AI offline",
        "daily_plan" => [],
        "resources" => []
    ];
}
