<?php
require_once 'config/db.php';
echo "Checking tables...\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\nChecking ai_test_results columns...\n";
if (in_array('ai_test_results', $tables)) {
    $stmt = $pdo->query("DESCRIBE ai_test_results");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo "Table ai_test_results NOT FOUND.\n";
    // Create it logic could go here if needed
}
?>