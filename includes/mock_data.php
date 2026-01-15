<?php
// Centralized Mock Data Source

// FR23: Previous Result Storage
// FR23: Previous Result Storage
function getTestResults()
{
    if (isset($_SESSION['test_history']) && !empty($_SESSION['test_history'])) {
        // Detect old mock data (ID 101 is the signature of the old static data)
        // New data uses time() as ID, which is a large integer.
        $first = $_SESSION['test_history'][0] ?? null;
        if ($first && isset($first['id']) && $first['id'] === 101) {
            $_SESSION['test_history'] = [];
        }

        // Also check the LAST item just in case they added new ones on top
        $last = end($_SESSION['test_history']);
        if ($last && isset($last['id']) && $last['id'] === 105) {
            // Filter out IDs < 100000 (old mocks)
            $_SESSION['test_history'] = array_filter($_SESSION['test_history'], function ($item) {
                return isset($item['id']) && $item['id'] > 100000;
            });
        }
    }

    if (!isset($_SESSION['test_history'])) {
        // Init with empty
        $_SESSION['test_history'] = [];
    }
    return $_SESSION['test_history'];
}

function addTestResult($result)
{
    if (!isset($_SESSION['test_history'])) {
        getTestResults(); // Init
    }
    // Prepend new result
    array_unshift($_SESSION['test_history'], $result);
}

// FR10: CEFR status
function getUserProfile()
{
    if (!isset($_SESSION['user_profile'])) {
        $_SESSION['user_profile'] = [
            "name" => "Irem Nur",
            "current_level" => "A1", // Default to Beginner
            "target_level" => "C1",
            "ielts_estimate" => 3.0,
            "toefl_estimate" => 20,
            "progress_percent" => 0,
            "streak_days" => 1
        ];
    }

    // Auto-fix: If user is stuck on "B1" default with NO history, reset to A1
    // This fixes the issue for the current active user immediately
    if ($_SESSION['user_profile']['current_level'] === 'B1' && empty($_SESSION['test_history'])) {
        $_SESSION['user_profile']['current_level'] = 'A1';
        $_SESSION['user_profile']['ielts_estimate'] = 3.0;
        $_SESSION['user_profile']['toefl_estimate'] = 20;
    }

    return $_SESSION['user_profile'];
}

function updateUserProfile($updates)
{
    if (!isset($_SESSION['user_profile'])) {
        getUserProfile(); // Init
    }
    foreach ($updates as $k => $v) {
        $_SESSION['user_profile'][$k] = $v;
    }
}

// FR13: AI Recommendations
function getAiRecommendations()
{
    return [
        ["type" => "grammar", "title" => "Review Past Perfect", "duration" => "15 min", "priority" => "High"],
        ["type" => "listening", "title" => "Podcast: Tech Trends", "duration" => "20 min", "priority" => "Medium"],
        ["type" => "speaking", "title" => "Describe your workspace", "duration" => "5 min", "priority" => "Low"]
    ];
}
?>