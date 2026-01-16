<?php
// includes/mock_data.php - NOW CONVERTED TO REAL DB ADAPTER
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_guard.php';

function getTestResults(int $limit = 8): array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $uid = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
    if ($uid <= 0) return [];

    // PDO
    $pdo = db(); // sende farklıysa: getPDO() / $GLOBALS['pdo'] vs.

    $sql = "
      SELECT
        a.category,
        ar.score_percent,
        ar.correct_count,
        ar.wrong_count,
        ar.cefr_estimate,
        ar.created_at
      FROM assessments a
      JOIN assessment_results ar ON ar.assessment_id = a.id
      WHERE a.user_id = :uid
      ORDER BY ar.created_at DESC
      LIMIT :lim
    ";

    $st = $pdo->prepare($sql);
    $st->bindValue(':uid', $uid, PDO::PARAM_INT);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();

    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) return [];

    // UI/AI coach tarafı için basit normalize
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'category' => $r['category'],
            'score_percent' => (float)$r['score_percent'],
            'correct' => (int)$r['correct_count'],
            'wrong' => (int)$r['wrong_count'],
            'cefr' => $r['cefr_estimate'],
            'date' => $r['created_at'],
        ];
    }
    return $out;
}

function addTestResult($result)
{
    global $pdo;
    $userId = current_user_id();

    $sql = "INSERT INTO ai_test_results (user_id, test_type, test_name, score, max_score, cefr_level, result_json) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $jsonData = isset($result['details']) ? json_encode($result['details']) : null;

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