<?php
function ai_generate_text(string $prompt, string $model = "llama3.1:8b"): string {
  $url = "http://127.0.0.1:11434/api/generate";

  $payload = json_encode([
    "model" => $model,
    "prompt" => $prompt,
    "stream" => false,
    "options" => [
      "temperature" => 0.4
    ]
  ]);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 90,
  ]);

  $res = curl_exec($ch);
  if ($res === false) {
    throw new Exception("AI request failed: " . curl_error($ch));
  }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($code < 200 || $code >= 300) {
    throw new Exception("AI HTTP error: $code | $res");
  }

  $decoded = json_decode($res, true);
  return (string)($decoded["response"] ?? "");
}

function ai_generate_json(string $prompt, string $model = "llama3.1:8b"): array {
  $text = ai_generate_text($prompt, $model);

  // İçinden JSON yakala
  $jsonStart = strpos($text, "{");
  $jsonEnd = strrpos($text, "}");
  if ($jsonStart === false || $jsonEnd === false) {
    throw new Exception("AI did not return JSON. Raw:\n" . $text);
  }

  $jsonStr = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
  $out = json_decode($jsonStr, true);

  if (!is_array($out)) {
    throw new Exception("Invalid JSON from AI. Raw JSON:\n" . $jsonStr);
  }

  return $out;
}
