<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../includes/header.php";
?>

<h2>Admin Dashboard</h2>

<p>Welcome, <?= htmlspecialchars($_SESSION["user"]["name"] ?? "Admin") ?> (ADMIN)</p>

<ul>
 <li><a href="/SENG321/admin/users.php">Manage Users</a></li>
<li><a href="/SENG321/admin/bulk_upload.php">Bulk Upload (CSV)</a></li>
<li><a href="/SENG321/admin/monitor.php">System Monitoring</a></li>
<li><a href="/SENG321/auth/logout.php">Logout</a></li>

</ul>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>
