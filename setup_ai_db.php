<?php
require_once "config/db.php";

echo "<h2>Setting up AI & Profiling Tables...</h2>";

try {
    // 1. Add CEFR columns to users table if not exists
    echo "Checking 'users' table columns...<br>";
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);

    // Use NULL allowable columns initially to avoid default value issues
    if (!in_array("cefr_level", $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN cefr_level VARCHAR(20) DEFAULT NULL");
        echo "Added cefr_level column.<br>";
    }
    if (!in_array("ielts_estimate", $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN ielts_estimate DECIMAL(4,1) DEFAULT 0");
        echo "Added ielts_estimate column.<br>";
    }
    if (!in_array("toefl_estimate", $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN toefl_estimate INT DEFAULT 0");
        echo "Added toefl_estimate column.<br>";
    }

    // 2. Create ai_test_results table
    echo "Creating 'ai_test_results' table...<br>";
    $sql = "
    CREATE TABLE IF NOT EXISTS ai_test_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        test_type VARCHAR(50) NOT NULL,
        test_name VARCHAR(100) NOT NULL,
        score DECIMAL(5,2) DEFAULT 0,
        max_score INT DEFAULT 100,
        cefr_level VARCHAR(10),
        result_json TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table 'ai_test_results' is ready.<br>";

    echo "<h3 style='color:green'>Database Setup Completed Successfully!</h3>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>