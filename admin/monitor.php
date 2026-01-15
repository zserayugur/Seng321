<?php
require_once __DIR__ . "/../includes/admin_guard.php";
$path_prefix = "../";   // admin klasöründen root'a çıkmak için
require_once __DIR__ . "/../includes/header.php";
?>

<h2>System Monitoring (Mock)</h2>

<ul>
  <li>System Status: <b>RUNNING</b></li>
  <li>AI Engine Status: <b>ACTIVE</b></li>
  <li>Last Check: <b><?= date("Y-m-d H:i:s") ?></b></li>
</ul>

<p><a href="/language-platform/admin/dashboard.php">Back</a></p>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>
