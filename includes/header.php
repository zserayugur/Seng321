<?php
if (!isset($path_prefix)) { $path_prefix = ''; }

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$base = "/Seng321";

// role normalize
$role = strtolower(trim($_SESSION["user"]["role"] ?? "learner"));
if ($role === "student") $role = "learner";

// dashboard url
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
  <div class="logo-wrapper">
  

</div>


  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Chart.js for Graphs -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/style.css">
</head>

<body>
  <div class="container">
    <header class="main-header">
     <img 
  src="/Seng321/assets/logo2.png" 
  alt="LevelUP English"
  style="height: 120px; width: 150px;">

      <nav>
        <ul class="nav-links">

          <!-- Dashboard (her rolde var) -->
          <li>
            <a href="<?php echo $dashboardUrl; ?>" class="<?php echo (isset($page) && $page === 'dashboard') ? 'active' : ''; ?>">
              Dashboard
            </a>
          </li>

          <?php if ($role === "learner"): ?>

            <li><a href="<?php echo $path_prefix; ?>pages/cefr.php" class="<?php echo (isset($page) && $page === 'cefr') ? 'active' : ''; ?>">CEFR & Predictions</a></li>
            <li><a href="<?php echo $path_prefix; ?>pages/reports.php" class="<?php echo (isset($page) && $page === 'reports') ? 'active' : ''; ?>">Reports & Analytics</a></li>
            <li><a href="<?php echo $path_prefix; ?>pages/todo.php" class="<?php echo (isset($page) && $page === 'todo') ? 'active' : ''; ?>">To-Do List</a></li>
            <li><a href="<?php echo $path_prefix; ?>pages/recommendations.php" class="<?php echo (isset($page) && $page === 'ai') ? 'active' : ''; ?>">AI Coach</a></li>

            <li><a href="<?php echo $path_prefix; ?>pages/listening.php" class="<?php echo (isset($page) && $page === 'listening') ? 'active' : ''; ?>">Listening</a></li>
            <li><a href="<?php echo $path_prefix; ?>pages/speaking.php" class="<?php echo (isset($page) && $page === 'speaking') ? 'active' : ''; ?>">Speaking</a></li>
            <li><a href="<?php echo $path_prefix; ?>pages/writing.php" class="<?php echo (isset($page) && $page === 'writing') ? 'active' : ''; ?>">Writing</a></li>
            <li><a href="<?php echo $path_prefix; ?>pages/vocabulary.php" class="<?php echo ($page == 'vocabulary') ? 'active' : ''; ?>">Vocabulary</a></li>
                    <li><a href="<?php echo $path_prefix; ?>pages/grammar.php" class="<?php echo ($page == 'grammar') ? 'active' : ''; ?>">Grammar</a></li>
                    <li><a href="<?php echo $path_prefix; ?>pages/reading.php" class="<?php echo ($page == 'reading') ? 'active' : ''; ?>">Reading</a></li>
            <li><a href="<?php echo $path_prefix; ?>pages/profile.php" class="<?php echo (isset($page) && $page === 'profile') ? 'active' : ''; ?>">Profile</a></li>
            <li><a href="<?php echo $base; ?>/login_part/logout.php">Logout</a></li>

          <?php elseif ($role === "instructor"): ?>

            <li><a href="<?php echo $path_prefix; ?>pages/reports.php" class="<?php echo (isset($page) && $page === 'reports') ? 'active' : ''; ?>">Class Reports</a></li>
            <li><a href="<?php echo $path_prefix; ?>pages/review.php" class="<?php echo (isset($page) && $page === 'review') ? 'active' : ''; ?>">Review Answers</a></li>

            <!-- Eğer bu dosyalar sende yoksa bu 2 satırı sil veya dosyaları oluştur -->
            <li><a href="<?php echo $base; ?>/instructor/assignments.php">Assignments</a></li>
            <li><a href="<?php echo $base; ?>/instructor/class_codes.php">Class Codes</a></li>

            <li><a href="<?php echo $path_prefix; ?>pages/profile.php" class="<?php echo (isset($page) && $page === 'profile') ? 'active' : ''; ?>">Profile</a></li>
            <li><a href="<?php echo $base; ?>/login_part/logout.php">Logout</a></li>

          <?php else: ?>

            <li><a href="<?php echo $base; ?>/admin/users.php">Manage Users</a></li>
            <li><a href="<?php echo $base; ?>/admin/bulk_upload.php">Bulk Upload</a></li>
            <li><a href="<?php echo $base; ?>/admin/monitor.php">System Monitoring</a></li>

            <li><a href="<?php echo $base; ?>/login_part/logout.php">Logout</a></li>

          <?php endif; ?>

        </ul>
      </nav>
    </header>

    <main>
