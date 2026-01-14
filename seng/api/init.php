<?php
// api/init.php
require_once __DIR__ . '/../lib/json.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/assessment.php';
require_once __DIR__ . '/../lib/timer.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  json_ok();
}
