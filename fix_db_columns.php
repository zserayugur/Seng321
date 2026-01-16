<?php
// fix_db_columns.php
require_once "config/db.php";

echo "<h2>Database Repair Tool</h2>";

try {
    echo "Connected to database: " . htmlspecialchars($dbname) . "<br>";

    // 1. Check & Fix Users Table
    echo "<h3>Checking 'users' table...</h3>";
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    echo "Current columns: " . implode(", ", $columns) . "<br>";

    if (!in_array("cefr_level", $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN cefr_level VARCHAR(20) DEFAULT 'Not Determined'");
        echo "<span style='color:green'>+ Added cefr_level column.</span><br>";
    } else {
        echo "<span style='color:blue'>✓ cefr_level exists.</span><br>";
    }

    if (!in_array("ielts_estimate", $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN ielts_estimate DECIMAL(4,1) DEFAULT 0");
        echo "<span style='color:green'>+ Added ielts_estimate column.</span><br>";
    } else {
        echo "<span style='color:blue'>✓ ielts_estimate exists.</span><br>";
    }

    if (!in_array("toefl_estimate", $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN toefl_estimate INT DEFAULT 0");
        echo "<span style='color:green'>+ Added toefl_estimate column.</span><br>";
    } else {
        echo "<span style='color:blue'>✓ toefl_estimate exists.</span><br>";
    }

    // 2. Check & Fix Results Table
    echo "<h3>Checking 'ai_test_results' table...</h3>";
    // Check if table exists first
    $tables = $pdo->query("SHOW TABLES LIKE 'ai_test_results'")->fetchAll();
    if (count($tables) == 0) {
        $sql = "
        CREATE TABLE ai_test_results (
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
        echo "<span style='color:green'>+ Created ai_test_results table.</span><br>";
    } else {
        echo "<span style='color:blue'>✓ ai_test_results table exists.</span><br>";
    }

    echo "<hr><h2 style='color:green'>Repair Completed!</h2>";
    echo "<p>You can now try the tests again.</p>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
}
?>