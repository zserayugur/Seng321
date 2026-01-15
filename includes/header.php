<?php
if (!isset($path_prefix)) { $path_prefix = ''; }

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$base = "/Seng321";

// role normalize
$role = strtolower(trim($_SESSION["user"]["role"] ?? "learner"));
if ($role === "student") $role = "learner";

// Dashboard URL
if ($role === "admin") {
  $dashboardUrl = $base . "/admin/dashboard.php";
} elseif ($role === "instructor") {
  $dashboardUrl = $base . "/dashboard/instructor.php";
} else {
  $dashboardUrl = $base . "/dashboard/learner.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LinguaPro - AI Language Learning</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/style.css">
</head>
<body>
<div class="container">
  <header class="main-header">
    <div class="logo">LinguaPro AI</div>

    <nav>
      <ul class="nav-links">

        <!-- Her rolde Dashboard olsun -->
        <li>
          <a href="<?php echo $dashboardUrl; ?>" class="<?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
            Dashboard
          </a>
        </li>

        <?php if ($role === "learner"): ?>
          <!-- LEARNER MENÜ (tam menü) -->
          <li><a href="<?php echo $path_prefix; ?>pages/cefr.php" class="<?php echo ($page == 'cefr') ? 'active' : ''; ?>">CEFR & Predictions</a></li>
          <li><a href="<?php echo $path_prefix; ?>pages/reports.php" class="<?php echo ($page == 'reports') ? 'active' : ''; ?>">Reports & Analytics</a></li>
          <li><a href="<?php echo $path_prefix; ?>pages/todo.php" class="<?php echo ($page == 'todo') ? 'active' : ''; ?>">To-Do List</a></li>
          <li><a href="<?php echo $path_prefix; ?>pages/recommendations.php" class="<?php echo ($page == 'ai') ? 'active' : ''; ?>">AI Coach</a></li>

          <li><a href="<?php echo $path_prefix; ?>pages/listening.php" class="<?php echo ($page == 'listening') ? 'active' : ''; ?>">Listening</a></li>
          <li><a href="<?php echo $path_prefix; ?>pages/speaking.php" class="<?php echo ($page == 'speaking') ? 'active' : ''; ?>">Speaking</a></li>
          <li><a href="<?php echo $path_prefix; ?>pages/writing.php" class="<?php echo ($page == 'writing') ? 'active' : ''; ?>">Writing</a></li>

          <li><a href="<?php echo $path_prefix; ?>pages/profile.php" class="<?php echo ($page == 'profile') ? 'active' : ''; ?>">Profile</a></li>
          <li><a href="<?php echo $base; ?>/login_part/logout.php">Logout</a></li>

        <?php elseif ($role === "instructor"): ?>
          <!-- INSTRUCTOR MENÜ -->
          <!-- Requirements: öğretmen rapor/performans, sınıf kodları, ödev/yayın vb. FR5-F7 :contentReference[oaicite:2]{index=2} -->
          <li><a href="<?php echo $path_prefix; ?>pages/reports.php" class="<?php echo ($page == 'reports') ? 'active' : ''; ?>">Class Reports</a></li>
          <li><a href="<?php echo $path_prefix; ?>pages/review.php" class="<?php echo ($page == 'review') ? 'active' : ''; ?>">Review Answers</a></li>

          <!-- Eğer instructor sayfaların farklı klasördeyse (ör. /instructor/assignments.php) burayı ona göre yaz -->
          <li><a href="<?php echo $base; ?>/instructor/assignments.php">Assignments</a></li>
          <li><a href="<?php echo $base; ?>/instructor/class_codes.php">Class Codes</a></li>

          <li><a href="<?php echo $path_prefix; ?>pages/profile.php" class="<?php echo ($page == 'profile') ? 'active' : ''; ?>">Profile</a></li>
          <li><a href="<?php echo $base; ?>/login_part/logout.php">Logout</a></li>

        <?php else: ?>
          <!-- ADMIN MENÜ -->
          <!-- Requirements: kullanıcı yönetimi, bulk, monitor, role/permission FR4 :contentReference[oaicite:3]{index=3} -->
          <li><a href="<?php echo $base; ?>/admin/users.php">Manage Users</a></li>
          <li><a href="<?php echo $base; ?>/admin/bulk_upload.php">Bulk Upload</a></li>
          <li><a href="<?php echo $base; ?>/admin/monitor.php">System Monitoring</a></li>

          <li><a href="<?php echo $base; ?>/admin/dashboard.php">Admin Home</a></li>
          <li><a href="<?php echo $base; ?>/login_part/logout.php">Logout</a></li>
        <?php endif; ?>

      </ul>
    </nav>
  </header>
  <main>
