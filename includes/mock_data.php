<?php
// includes/mock_data.php - NOW CONVERTED TO REAL DB ADAPTER
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_guard.php';

function getTestResults()
{
    global $pdo;
    $userId = current_user_id();

    $stmt = $pdo->prepare("SELECT * FROM ai_test_results WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map DB fields to UI expectation format
    $history = [];
    foreach ($rows as $r) {
        $history[] = [
            "id" => $r["id"],
            "date" => date("Y-m-d", strtotime($r["created_at"])),
            "test" => $r["test_name"],
            "type" => $r["test_type"],
            "score" => floatval($r["score"]),
            "max_score" => intval($r["max_score"]),
            "level" => $r["cefr_level"],
            "status" => "Completed",
            "details" => json_decode($r["result_json"] ?? '{}', true)
        ];
    }
    return $history;
}

function addTestResult($result)
{
    global $pdo;
    $userId = current_user_id();

    $sql = "INSERT INTO ai_test_results (user_id, test_type, test_name, score, max_score, cefr_level, result_json) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $jsonData = isset($result['details']) ? json_encode($result['details']) : null;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $result['type'],
            $result['test'],
            $result['score'],
            $result['max_score'],
            $result['level'],
            $jsonData
        ]);
    } catch (PDOException $e) {
        error_log("DB Insert Test Result Error: " . $e->getMessage());
        // Fail silently or maybe save to session as backup?
        // For now, silent fail to avoid breaking UI
    }
}

function getUserProfile()
{
    global $pdo;
    $userId = current_user_id();

    try {
        $stmt = $pdo->prepare("SELECT name, email, cefr_level, ielts_estimate, toefl_estimate FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If column missing or DB error, ignore and fall through to fallback
        $user = false;
        error_log("DB Profile Fetch Error: " . $e->getMessage());
    }

    if (!$user) {
        // Fallback mainly for dev environment if user not found or DB schema mismatch
        return [
            "name" => "Guest",
            "current_level" => "Not Determined",
            "target_level" => "C1",
            "ielts_estimate" => 0,
            "toefl_estimate" => 0,
            "progress_percent" => 0,
            "streak_days" => 1
        ];
    }

    return [
        "name" => $user['name'] ?? "User",
        "current_level" => $user['cefr_level'] ?? "Not Determined",
        "target_level" => "C1", // Hardcoded for now or add column
        "ielts_estimate" => floatval($user['ielts_estimate'] ?? 0),
        "toefl_estimate" => intval($user['toefl_estimate'] ?? 0),
        "progress_percent" => 50, // Could be calculated
        "streak_days" => 1
    ];
}

function updateUserProfile($updates)
{
    global $pdo;
    $userId = current_user_id();

    // Map UI keys to DB columns
    $map = [
        "current_level" => "cefr_level",
        "ielts_estimate" => "ielts_estimate",
        "toefl_estimate" => "toefl_estimate"
    ];

    $setParts = [];
    $params = [];

    foreach ($updates as $k => $v) {
        if (isset($map[$k])) {
            $col = $map[$k];
            $setParts[] = "$col = ?";
            $params[] = $v;
        }
    }

    if (empty($setParts))
        return;

    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(", ", $setParts) . " WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Also update session to reflect immediate changes
    if (isset($_SESSION['user_profile'])) {
        foreach ($updates as $k => $v) {
            $_SESSION['user_profile'][$k] = $v;
        }
    }
}

// Keep the mock function for recommendations as it doesn't need DB yet (can be added later)
function getAiRecommendations()
{
    return [
        ["type" => "grammar", "title" => "Review Past Perfect", "duration" => "15 min", "priority" => "High"],
        ["type" => "listening", "title" => "Podcast: Tech Trends", "duration" => "20 min", "priority" => "Medium"],
        ["type" => "speaking", "title" => "Describe your workspace", "duration" => "5 min", "priority" => "Low"]
    ];
}
?>