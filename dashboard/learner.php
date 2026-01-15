<?php
// SENG321/dashboard/learner.php
$page = 'dashboard';
$path_prefix = '../';

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();

// Son attempt'leri çek (listening/speaking/writing)
$stmt = $pdo->prepare("
  SELECT id, type, part, status, started_at, submitted_at, duration_seconds
  FROM assessment_attempts
  WHERE user_id = ?
  ORDER BY id DESC
  LIMIT 10
");
$stmt->execute([$userId]);
$attempts = $stmt->fetchAll();

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function nice_type($t) {
  if ($t === 'listening') return 'Listening';
  if ($t === 'speaking') return 'Speaking';
  if ($t === 'writing') return 'Writing';
  return $t;
}

function nice_status($s) {
  if ($s === 'in_progress') return 'In Progress';
  if ($s === 'submitted') return 'Submitted';
  if ($s === 'evaluated') return 'Evaluated';
  return $s;
}
?>

<h2>Learner Dashboard</h2>

<p>
  Welcome, <b><?= h($_SESSION["user"]["name"] ?? "Student") ?></b>
</p>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:16px 0;">
  <div style="border:1px solid #e5e5e5;border-radius:12px;padding:14px;">
    <h3 style="margin:0 0 6px 0;">Speaking</h3>
    <p style="margin:0 0 10px 0;opacity:.85;">10s prep + 150s recording</p>
    <a href="/SENG321/pages/speaking.php">Start Speaking</a>
  </div>

  <div style="border:1px solid #e5e5e5;border-radius:12px;padding:14px;">
    <h3 style="margin:0 0 6px 0;">Writing</h3>
    <p style="margin:0 0 10px 0;opacity:.85;">250–450 words / 50 min</p>
    <a href="/SENG321/pages/writing.php">Start Writing</a>
  </div>

  <div style="border:1px solid #e5e5e5;border-radius:12px;padding:14px;">
    <h3 style="margin:0 0 6px 0;">Listening</h3>
    <p style="margin:0 0 10px 0;opacity:.85;">Part 1 & Part 2 (10 min)</p>
    <a href="/SENG321/pages/listening.php">Start Listening</a>
  </div>
</div>

<hr style="margin:18px 0;">

<h3>Recent Attempts</h3>

<?php if (!$attempts): ?>
  <p style="opacity:.8;">No attempts found yet. Start a module to see your history here.</p>
<?php else: ?>
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">#</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Type</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Part</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Status</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Started</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Submitted</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Duration</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($attempts as $a): ?>
        <tr>
          <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= (int)$a["id"] ?></td>
          <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h(nice_type($a["type"])) ?></td>
          <td style="border-bottom:1px solid #f0f0f0;padding:8px;">
            <?= $a["part"] !== null ? (int)$a["part"] : "-" ?>
          </td>
          <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h(nice_status($a["status"])) ?></td>
          <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h($a["started_at"] ?? "-") ?></td>
          <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h($a["submitted_at"] ?? "-") ?></td>
          <td style="border-bottom:1px solid #f0f0f0;padding:8px;">
            <?= (int)$a["duration_seconds"] ?>s
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<hr style="margin:18px 0;">

<div style="display:flex;gap:14px;flex-wrap:wrap;">
  <a href="/SENG321/pages/reports.php">Go to Reports</a>
  <a href="/SENG321/pages/todo.php">Open To-Do List</a>
  <a href="/SENG321/login_part/logout.php">Logout</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
