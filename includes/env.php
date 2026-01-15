<?php
// includes/env.php

$envFile = __DIR__ . '/../.env';

// EKRANA ASLA BASMA. Debug gerekiyorsa:
// error_log("Looking for env at: " . $envFile);

if (!file_exists($envFile)) {
    return;
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;

    [$key, $value] = array_pad(explode('=', $line, 2), 2, null);
    if (!$key || $value === null) continue;

    $key = trim($key);
    $value = trim($value);

    // Tırnakları temizle: KEY="value" veya KEY='value'
    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
        $value = substr($value, 1, -1);
    }

    putenv("$key=$value");
    $_ENV[$key] = $value;
}