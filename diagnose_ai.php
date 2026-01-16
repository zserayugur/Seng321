<?php
// diagnose_ai.php
// A comprehensive diagnostic tool to find a WORKING Gemini model.

require_once 'includes/env.php';
$keyFile = __DIR__ . '/.gemini_key';

echo "<h1>🔍 AI Connection Diagnostic</h1>";

// 1. Check API Key
if (file_exists($keyFile)) {
    $apiKey = trim(file_get_contents($keyFile));
    echo "✅ API Key found in .gemini_key<br>";
} else {
    die("❌ .gemini_key file missing! Please add your API key.");
}

// 2. List of Candidates to Try
$modelsToTry = [
    "gemini-1.5-flash",
    "gemini-1.5-flash-latest",
    "gemini-pro",
    "gemini-1.0-pro",
    "gemini-flash-latest",
    "gemini-2.0-flash-exp" // Experimental but often free quota
];

echo "<h3>Testing Models...</h3>";

$workingModel = null;

foreach ($modelsToTry as $model) {
    echo "Testing <strong>$model</strong>... ";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=" . $apiKey;
    $payload = json_encode(["contents" => [["parts" => [["text" => "Hi"]]]]]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        echo "<span style='color:green'>SUCCESS (200) ✅</span><br>";
        $workingModel = $model;
        break; // Found one!
    } elseif ($httpCode == 429) {
        echo "<span style='color:orange'>QUOTA EXCEEDED (429) ⏳</span><br>";
    } elseif ($httpCode == 404) {
        echo "<span style='color:red'>NOT FOUND (404) ❌</span><br>";
    } else {
        echo "<span style='color:red'>ERROR ($httpCode) ❌</span><br>";
    }
}

echo "<hr>";

if ($workingModel) {
    echo "<h2 style='color:green'>🎉 FOUND WORKING MODEL: $workingModel</h2>";
    echo "<p>I will now automatically update your system to use this model.</p>";

    // Auto-fix ai_service.php
    $file = 'includes/ai_service.php';
    $content = file_get_contents($file);

    // Regex to replace ANY existing model url with the new one
    $pattern = '/https:\/\/generativelanguage\.googleapis\.com\/v1beta\/models\/[a-zA-Z0-9\-\._]+:generateContent/';
    $replacement = "https://generativelanguage.googleapis.com/v1beta/models/$workingModel:generateContent";

    $newContent = preg_replace($pattern, $replacement, $content);

    if (file_put_contents($file, $newContent)) {
        echo "✅ includes/ai_service.php updated successfully.<br>";
        echo "👉 <a href='pages/vocabulary.php?reset=1'>Click here to try the Vocabulary Test again</a>";
    } else {
        echo "❌ Failed to write to ai_service.php. Please manually update line 181 to use '$workingModel'.";
    }

} else {
    echo "<h2 style='color:red'>❌ NO WORKING MODELS FOUND</h2>";
    echo "<p>This likely means your API key quota is fully exhausted for all standard models. Please wait 5-10 minutes and try again.</p>";
}
?>