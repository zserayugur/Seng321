<?php
// test_gemini.php
// API Key'i includes/ai_service.php'den okumaya çalışalım veya elle girelim.
// Basitlik için dosyayı okuyup define'ı parse edelim.

$keyFile = __DIR__ . '/.gemini_key';

if (!file_exists($keyFile)) {
    die("Error: .gemini_key file not found at $keyFile\n");
}

$apiKey = trim(file_get_contents($keyFile));

if (empty($apiKey)) {
    die("Error: API Key is empty in .gemini_key\n");
}

echo "Using API Key: " . substr($apiKey, 0, 5) . "...\n";

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Connection Error: $error\n");
}

$data = json_decode($response, true);

if (isset($data['error'])) {
    die("API Error: " . print_r($data['error'], true));
}

echo "Available Models for generateContent:\n";
foreach ($data['models'] as $model) {
    if (in_array('generateContent', $model['supportedGenerationMethods'])) {
        echo "- " . $model['name'] . "\n";
    }
}
?>