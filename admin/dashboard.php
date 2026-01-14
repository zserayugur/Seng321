<?php require_once __DIR__ . "/../includes/admin_guard.php";
      require_once __DIR__ . "/../includes/header.php"; ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin Dashboard</title></head>
<body>
<h2>Admin Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION["user"]["name"]); ?> (ADMIN)</p>

<ul>
  <li><a href="/language-platform/admin/users.php">Manage Users</a></li>
  <li><a href="/language-platform/admin/bulk_upload.php">Bulk Upload (CSV)</a></li>
  <li><a href="/language-platform/admin/monitor.php">System Monitoring</a></li>
  <li><a href="/language-platform/auth/logout.php">Logout</a></li>
</ul>
</body>
</html>
<?php
require_once __DIR__ . "/../includes/footer.php";
?>