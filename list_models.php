<?php
require_once 'includes/env.php';
$keyFile = __DIR__ . '/.gemini_key';
$apiKey = trim(file_get_contents($keyFile));

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<h1>Available Models</h1>";
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (strpos($m['name'], 'flash') !== false || strpos($m['name'], 'pro') !== false) {
            echo $m['name'] . " (" . $m['version'] . ")<br>";
        }
    }
} else {
    echo "Error: " . $response;
}
?>