<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$path_prefix = "../";
$page = "admin";
require_once __DIR__ . "/../includes/header.php";

/* --------- KART SAYILARI --------- */
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE active=1")->fetchColumn();

$cntLearner = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='LEARNER'")->fetchColumn();
$cntInstr   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='INSTRUCTOR'")->fetchColumn();
$cntAdmin   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='ADMIN'")->fetchColumn();

$totalAssessments = (int)$pdo->query("SELECT COUNT(*) FROM assessments")->fetchColumn();
$last7Assessments = (int)$pdo->query("SELECT COUNT(*) FROM assessments WHERE started_at >= (NOW() - INTERVAL 7 DAY)")->fetchColumn();

$totalRecs = (int)$pdo->query("SELECT COUNT(*) FROM ai_recommendations")->fetchColumn();

/* --------- SON AKTİVİTE LİSTELERİ --------- */
$recentUsers = $pdo->query("
  SELECT id, name, email, role, created_at
  FROM users
  ORDER BY id DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recentAssess = $pdo->query("
  SELECT a.id, u.name AS user_name, a.category, a.level, a.started_at
  FROM assessments a
  JOIN users u ON u.id = a.user_id
  ORDER BY a.id DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recentRecs = $pdo->query("
  SELECT r.id, u.name AS user_name, r.cefr_level, r.created_at
  FROM ai_recommendations r
  JOIN users u ON u.id = r.user_id
  ORDER BY r.id DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Admin Dashboard</h2>
<p>Welcome, <?= htmlspecialchars($_SESSION["user"]["name"] ?? "Admin") ?> (ADMIN)</p>

<!-- Özet kartları -->
<div class="card" style="margin-top:16px;">
  <h3 style="margin-top:0;">System Overview</h3>

  <div style="display:flex; gap:16px; flex-wrap:wrap;">
    <div class="card" style="min-width:200px; flex:1;">
      <div style="opacity:.8;">Total Users</div>
      <div style="font-size:28px; font-weight:700;"><?= $totalUsers ?></div>
      <div style="opacity:.8;">Active: <?= $activeUsers ?></div>
    </div>

    <div class="card" style="min-width:200px; flex:1;">
      <div style="opacity:.8;">Roles</div>
      <div style="margin-top:8px; line-height:1.8;">
        Learners: <b><?= $cntLearner ?></b><br>
        Instructors: <b><?= $cntInstr ?></b><br>
        Admins: <b><?= $cntAdmin ?></b>
      </div>
    </div>

    <div class="card" style="min-width:200px; flex:1;">
      <div style="opacity:.8;">Assessments</div>
      <div style="font-size:28px; font-weight:700;"><?= $totalAssessments ?></div>
      <div style="opacity:.8;">Last 7 days: <?= $last7Assessments ?></div>
    </div>

    <div class="card" style="min-width:200px; flex:1;">
      <div style="opacity:.8;">AI Recommendations</div>
      <div style="font-size:28px; font-weight:700;"><?= $totalRecs ?></div>
      <div style="opacity:.8;">Generated so far</div>
    </div>
  </div>
</div>

<!-- Hızlı aksiyonlar -->
<div class="card" style="margin-top:16px;">
  <h3 style="margin-top:0;">Quick Actions</h3>
  <div style="display:flex; gap:12px; flex-wrap:wrap;">
    <a class="btn" href="/Seng321/admin/users.php">Manage Users</a>
    <a class="btn" href="/Seng321/admin/bulk_upload.php">Bulk Upload</a>
    <a class="btn" href="/Seng321/admin/monitor.php">System Monitoring</a>
  </div>
</div>

<!-- Son aktiviteler -->
<div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:16px;">
  <div class="card" style="flex:1; min-width:320px;">
    <h3 style="margin-top:0;">Recent Users</h3>
    <ul style="margin:0; padding-left:18px; line-height:1.9;">
      <?php foreach ($recentUsers as $u): ?>
        <li>
          <b><?= htmlspecialchars($u["name"]) ?></b>
          (<?= htmlspecialchars($u["role"]) ?>) —
          <?= htmlspecialchars($u["email"]) ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <div style="margin-top:10px;">
      <a class="btn" href="/Seng321/admin/users.php">View all</a>
    </div>
  </div>

  <div class="card" style="flex:1; min-width:320px;">
    <h3 style="margin-top:0;">Recent Assessments</h3>
    <ul style="margin:0; padding-left:18px; line-height:1.9;">
      <?php foreach ($recentAssess as $a): ?>
        <li>
          <b><?= htmlspecialchars($a["user_name"]) ?></b> —
          <?= htmlspecialchars($a["category"]) ?>
          <?= $a["level"] ? "(" . htmlspecialchars($a["level"]) . ")" : "" ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="card" style="flex:1; min-width:320px;">
    <h3 style="margin-top:0;">Recent AI Recommendations</h3>
    <ul style="margin:0; padding-left:18px; line-height:1.9;">
      <?php foreach ($recentRecs as $r): ?>
        <li>
          <b><?= htmlspecialchars($r["user_name"]) ?></b> —
          CEFR: <?= htmlspecialchars($r["cefr_level"] ?? "-") ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
