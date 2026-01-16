<?php
/**
 * Database Configuration for Language Assessment Platform
 * Supports .env file and XAMPP defaults
 */

// Load environment variables if available
require_once __DIR__ . '/../includes/env.php';

// Read from environment or use defaults (XAMPP standard)
$host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? "localhost"; // Use localhost instead of 127.0.0.1 for XAMPP
$port = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? "3307"; // Port 3307 (as per db.sql and user configuration)
$dbname = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? "language_platform";
$user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? "root";
$pass = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? "";

// Debug mode: if ?debug_db=1 in URL, show detailed error
$debugMode = isset($_GET['debug_db']) && $_GET['debug_db'] === '1';

// Determine if we're in an API context (JSON response expected)
$isApiContext = (
    strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false ||
    strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
);

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    error_log("Connection attempt: host=$host, port=$port, dbname=$dbname, user=$user");
    
    // Build error message
    $errorMessage = "Database connection failed. Please check your configuration in config/db.php or .env file.";
    
    // In debug mode, show detailed error
    if ($debugMode) {
        $errorMessage .= "\n\nDEBUG INFO:\n";
        $errorMessage .= "Error: " . $e->getMessage() . "\n";
        $errorMessage .= "Host: $host\n";
        $errorMessage .= "Port: $port\n";
        $errorMessage .= "Database: $dbname\n";
        $errorMessage .= "User: $user\n";
        $errorMessage .= "Password: " . (empty($pass) ? "(empty)" : "(set)") . "\n";
        $errorMessage .= "\nTo hide this debug info, remove ?debug_db=1 from URL.";
    }
    
    if ($isApiContext) {
        // API context: return JSON
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        $response = [
            'ok' => false,
            'error' => 'Database connection failed. Please check your configuration.'
        ];
        if ($debugMode) {
            $response['debug'] = [
                'message' => $e->getMessage(),
                'host' => $host,
                'port' => $port,
                'database' => $dbname,
                'user' => $user
            ];
        }
        echo json_encode($response);
        exit;
    } else {
        // Regular page: show friendly error (with debug if enabled)
        if ($debugMode) {
            die("<pre>" . htmlspecialchars($errorMessage) . "</pre>");
        } else {
            die($errorMessage);
        }
    }
}
