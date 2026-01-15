<?php
// includes/ai_service.php

require_once 'mock_data.php';

// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// KULLANICI DİKKAT: BURAYA GOOGLE GEMINI API KEY'İNİZİ YAZINIZ
// USER ATTENTION: PASTE YOUR GOOGLE GEMINI API KEY HERE
// Link: https://aistudio.google.com/app/apikey
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
define('GEMINI_API_KEY',  getenv('GEMINI_API_KEY') ?: ''); // Örnek: 'AIzaSy...'

function fetchAIRecommendationsFromChatGPT()
{
    // Fonksiyon ismi uyumluluk için aynı kalsın ama içi Gemini olsun

    // 1. Context Verilerini Hazırla
    $userProfile = getUserProfile();
    $recentResults = getTestResults();

    // Eğer API Key yoksa, varsayılan (mock) verileri döndür
    if (empty(GEMINI_API_KEY)) {
        return getFallbackData();
    }

    // 2. Gemini için Prompt Hazırla
    // Gemini JSON konusunda biraz daha hassas, o yüzden prompt'u netleştirelim.
    $prompt = "
        You are an expert English language tutor. Analyze this student:
        Profile: " . json_encode($userProfile) . "
        Recent Results: " . json_encode($recentResults) . "
        
        Output valid JSON only. No markdown formatting. No ```json tags.
        Structure:
        {
            \"insight_text\": \"A short 2-sentence diagnostic insight.\",
            \"focus_area\": \"Short phrase (e.g. Grammar & Fluency)\",
            \"daily_plan\": [
                {\"title\": \"Task Name\", \"type\": \"grammar/listening/speaking\", \"duration\": \"15 min\", \"priority\": \"High/Medium/Low\"}
            ],
            \"resources\": [
                {\"title\": \"Resource Name\", \"type\": \"Article/Video/Quiz\", \"description\": \"Short description\"}
            ]
        }
        Provide exactly 3 items for daily_plan and 3 items for resources.
    ";

    // 3. API İsteği Gönder (Google Gemini REST API)
    // Model: gemini-flash-latest (Listede kesinlikle var olan model)
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . trim(GEMINI_API_KEY);

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    // SSL Doğrulamasını Devre Dışı Bırak (XAMPP için)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        $fallback = getFallbackData();
        $fallback['insight_text'] .= " (Connection Error: " . $error_msg . ")";
        return $fallback;
    }

    curl_close($ch);

    // 4. Yanıtı İşle
    $decodedResponse = json_decode($response, true);

    // Hata Kontrolü
    if (isset($decodedResponse['error'])) {
        $errorMsg = $decodedResponse['error']['message'] ?? 'Unknown Gemini Error';
        $fallback = getFallbackData();
        $fallback['insight_text'] = "Gemini API Error: " . $errorMsg;
        return $fallback;
    }

    // Cevabı al
    if (isset($decodedResponse['candidates'][0]['content']['parts'][0]['text'])) {
        $rawText = $decodedResponse['candidates'][0]['content']['parts'][0]['text'];

        // Markdown temizliği (Gemini bazen ```json ekler)
        $rawText = str_replace("```json", "", $rawText);
        $rawText = str_replace("```", "", $rawText);

        $aiData = json_decode($rawText, true);

        if ($aiData) {
            return $aiData;
        } else {
            $fallback = getFallbackData();
            $fallback['insight_text'] = "Gemini Parse Error. Raw: " . substr($rawText, 0, 100);
            return $fallback;
        }
    }

    $fallback = getFallbackData();
    $fallback['insight_text'] .= " (No valid response from Gemini)";
    return $fallback;
}

// Fallback Data (Aynı kalıyor)
function getFallbackData()
{
    return [
        'insight_text' => "Based on your recent tests, our AI usually detects patterns here. (API Key Missing Mode)",
        'focus_area' => "Demo Mode",
        'daily_plan' => getAiRecommendations(),
        'resources' => [
            ['title' => 'Advanced Grammar Guide', 'type' => 'Article', 'description' => 'Comprehensive guide to complex structures.'],
            ['title' => 'BBC Learning English', 'type' => 'Video', 'description' => 'Daily news review in English.'],
            ['title' => 'IELTS Mock Test 4', 'type' => 'Quiz', 'description' => 'Full length practice test.']
        ]
    ];
}
?>