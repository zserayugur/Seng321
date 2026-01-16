<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/ai_service.php';

header('Content-Type: application/json');

$data = fetchListeningTest();

echo json_encode($data);
