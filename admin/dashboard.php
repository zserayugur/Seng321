<?php
$path_prefix = "../";   // header.php linkleri için
$page = "admin";        // aktif menü vs. kullanıyorsan

require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../includes/header.php";
?>

<h2>Admin Dashboard</h2>

<p>Welcome, <?= htmlspecialchars($_SESSION["user"]["name"] ?? "Admin") ?> (ADMIN)</p>

<div class="card" style="margin-top:16px;">
  <ul style="margin:0; padding-left:18px; line-height:1.9;">
    <li><a href="<?= $path_prefix ?>admin/users.php" style=" color: #e692beff;">Manage Users</a></li>
    <li><a href="<?= $path_prefix ?>admin/bulk_upload.php" style=" color: #e692beff;">Bulk Upload (CSV)</a></li>
    <li><a href="<?= $path_prefix ?>admin/monitor.php" style=" color: #e692beff;">System Monitoring</a></li>
    <li><a href="/Seng321/auth/logout.php" style=" color: #e692beff;">Logout</a></li>
  </ul>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
