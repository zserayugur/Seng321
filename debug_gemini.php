<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Gemini API Debugger</h1>";

// 1. Check Key File
$keyFile = __DIR__ . '/.gemini_key';
echo "<h2>1. Checking API Key</h2>";
echo "Looking for file at: " . htmlspecialchars($keyFile) . "<br>";

if (!file_exists($keyFile)) {
    echo "<span style='color:red'>❌ File not found!</span><br>";
} else {
    echo "<span style='color:green'>✅ File found.</span><br>";
    $keyContent = file_get_contents($keyFile);
    $key = trim($keyContent);
    echo "File size: " . strlen($keyContent) . " bytes<br>";
    echo "Key length (trimmed): " . strlen($key) . "<br>";
    if (strlen($key) > 5) {
        echo "Key preview: " . substr($key, 0, 5) . "..." . substr($key, -4) . "<br>";
    } else {
        echo "<span style='color:red'>❌ Key seems too short/empty.</span><br>";
    }
}

// 2. Test Connection
echo "<h2>2. Testing API Connection</h2>";

if (empty($key)) {
    echo "Cannot test connection: No API Key.<br>";
} else {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemma-3-1b-it:generateContent?key=" . $key;

    // Simple verification prompt
    $payload = [
        "contents" => [
            ["parts" => [["text" => "Return this exact word: CONNECTED"]]]
        ]
    ];

    echo "Endpoint: " . htmlspecialchars("https://generativelanguage.googleapis.com/...models/gemini-flash-latest...") . "<br>";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $start = microtime(true);
    $response = curl_exec($ch);
    $duration = microtime(true) - $start;
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Duration: " . number_format($duration, 2) . "s<br>";
    echo "HTTP Code: " . $httpCode . "<br>";

    if ($curlError) {
        echo "<span style='color:red'>❌ cURL Error: $curlError</span><br>";
    } else {
        echo "<span style='color:green'>✅ Request sent successfully.</span><br>";
        echo "<h3>Response Body:</h3>";
        echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc;'>" . htmlspecialchars($response) . "</pre>";

        $json = json_decode($response, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $json['candidates'][0]['content']['parts'][0]['text'];
            echo "<h3>Parsed Text:</h3>";
            echo "<pre>" . htmlspecialchars($text) . "</pre>";
        } else {
            echo "<span style='color:orange'>⚠️ Could not find expected text in response structure.</span><br>";
        }
    }
}
?>