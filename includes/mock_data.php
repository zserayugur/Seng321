<?php
// Centralized Mock Data Source

// FR23: Previous Result Storage
function getTestResults()
{
    return [
        [
            "id" => 101, 
            "date" => "2023-10-25", 
            "test" => "General Diagnostic A", 
            "type" => "standard",
            "score" => 68, 
            "max_score" => 100, 
            "level" => "B1", 
            "status" => "Completed"
        ],
        [
            "id" => 102, 
            "date" => "2023-11-02", 
            "test" => "Listening Unit 4", 
            "type" => "listening",
            "score" => 85, 
            "max_score" => 100, 
            "level" => "B1+", 
            "status" => "Completed",
            "details" => [
                "audio_mock" => true,
                "transcript" => "Speaker 1: The weather in London is quite unpredictable... [User hears this]"
            ]
        ],
        [
            "id" => 103, 
            "date" => "2023-11-10", 
            "test" => "Speaking Mock", 
            "type" => "speaking",
            "score" => 72, 
            "max_score" => 100, 
            "level" => "B2", 
            "status" => "Review Pending",
            "details" => [
                "recording_path" => "mock_audio.mp3",
                "asr_transcript" => "I think that global warming is... um... very danger for our planet.",
                "feedback" => "Good vocabulary, but work on pronunciation of 'dangerous'."
            ]
        ],
        [
            "id" => 104, 
            "date" => "2023-11-15", 
            "test" => "Writing Task 1", 
            "type" => "writing",
            "score" => 82, 
            "max_score" => 100, 
            "level" => "B2", 
            "status" => "Completed",
            "details" => [
                "prompt" => "Describe the graph showing population growth.",
                "essay" => "The graph illustrates the population growth in three different countries over a period of 10 years...",
                "ai_corrections" => "Review usage of linking words. 'However' was used incorrectly in paragraph 2."
            ]
        ],
        [
            "id" => 105, 
            "date" => "2023-11-18", 
            "test" => "Vocabulary: Business", 
            "type" => "standard",
            "score" => 88, 
            "max_score" => 100, 
            "level" => "B2", 
            "status" => "Completed"
        ],
    ];
}

// FR10: CEFR status
function getUserProfile()
{
    return [
        "name" => "Irem Nur",
        "current_level" => "B1", // Calculated from recent tests
        "target_level" => "C1",
        "ielts_estimate" => 5.5,
        "toefl_estimate" => 50,
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