<?php
echo "Looking for env at: " . __DIR__ . '/../.env';

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;

        [$key, $value] = array_pad(explode('=', $line, 2), 2, null);
        if ($key && $value) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
