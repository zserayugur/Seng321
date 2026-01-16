<?php
ini_set('display_errors', 1);
require_once 'includes/ai_service.php';

echo "Testing fetchListeningTest...\n";
$res = fetchListeningTest();

if (isset($res['script']) && count($res['questions']) > 0) {
    echo "SUCCESS!\n";
    echo "Script Length: " . strlen($res['script']) . "\n";
    echo "Questions: " . count($res['questions']) . "\n";
} else {
    echo "FAILED.\n";
    print_r($res);
}
?>