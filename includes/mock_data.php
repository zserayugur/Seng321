<?php
// Centralized Mock Data Source

// FR23: Previous Result Storage
function getTestResults()
{
    return [
        ["id" => 101, "date" => "2023-10-25", "test" => "General Diagnostic A", "score" => 68, "max_score" => 100, "level" => "B1", "status" => "Completed"],
        ["id" => 102, "date" => "2023-11-02", "test" => "Listening Unit 4", "score" => 85, "max_score" => 100, "level" => "B1+", "status" => "Completed"],
        ["id" => 103, "date" => "2023-11-10", "test" => "Speaking Mock", "score" => 72, "max_score" => 100, "level" => "B2", "status" => "Review Pending"],
        ["id" => 104, "date" => "2023-11-15", "test" => "Grammar: Conditionals", "score" => 90, "max_score" => 100, "level" => "B2", "status" => "Completed"],
        ["id" => 105, "date" => "2023-11-18", "test" => "Vocabulary: Business", "score" => 88, "max_score" => 100, "level" => "B2", "status" => "Completed"],
    ];
}

// FR10: CEFR status
function getUserProfile()
{
    return [
        "name" => "Student",
        "current_level" => "B1", // Calculated from recent tests
        "target_level" => "C1",
        "progress_percent" => 65, // For B1 -> B2 progress bar
        "streak_days" => 12
    ];
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