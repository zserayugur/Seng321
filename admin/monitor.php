<?php require_once __DIR__ . "/../includes/admin_guard.php"; ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>System Monitoring</title></head>
<body>
<h2>System Monitoring (Mock)</h2>
<ul>
  <li>System Status: <b>RUNNING</b></li>
  <li>AI Engine Status: <b>ACTIVE</b></li>
  <li>Last Check: <b><?= date("Y-m-d H:i:s") ?></b></li>
</ul>
<p><a href="/language-platform/admin/dashboard.php">Back</a></p>
</body>
</html>
