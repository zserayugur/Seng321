<?php
/**
 * Dynamic BASE_PATH detection for XAMPP Windows (case-insensitive)
 * Detects the project folder name from SCRIPT_NAME and builds paths accordingly
 */

if (!function_exists('get_base_path')) {
    function get_base_path(): string {
        // Use cached value if available
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }
        
        // Detect from SCRIPT_NAME (e.g., /Seng321/pages/grammar.php -> /Seng321)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Extract first folder after root
        $parts = explode('/', trim($scriptName, '/'));
        if (!empty($parts[0])) {
            $base = '/' . $parts[0];
        } else {
            // Fallback: try REQUEST_URI
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $uriParts = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));
            if (!empty($uriParts[0])) {
                $base = '/' . $uriParts[0];
            } else {
                // Final fallback
                $base = '/Seng321';
            }
        }
        
        // Normalize (case-insensitive on Windows, but preserve original case for consistency)
        define('BASE_PATH', $base);
        return $base;
    }
}

// Auto-initialize
if (!defined('BASE_PATH')) {
    get_base_path();
}
