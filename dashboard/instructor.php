<?php
// Seng321/dashboard/instructor.php
$page = 'dashboard';
$path_prefix = '../';

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// (Opsiyonel) Instructor rol kontrolü
$role = strtolower(trim($_SESSION["user"]["role"] ?? ""));
if ($role !== "instructor" && $role !== "admin") {
  // instructor değilse learner dashboard'a gönder
  header("Location: /Seng321/dashboard/learner.php");
  exit;
}

// Son 20 attempt (tüm kullanıcılar) — öğretmen için izleme gibi
$stmt = $pdo->prepare("
  SELECT a.id, a.user_id, u.name AS student_name, a.type, a.part, a.status, a.started_at, a.submitted_at
  FROM assessment_attempts a
  LEFT JOIN users u ON u.id = a.user_id
  ORDER BY a.id DESC
  LIMIT 20
");
$stmt->execute();
$rows = $stmt->fetchAll();
?>

<h2>Instructor Dashboard</h2>

<p>Welcome, <b><?= h($_SESSION["user"]["name"] ?? "Instructor") ?></b> (INSTRUCTOR)</p>



<hr style="margin:18px 0;">

<h3>Recent Student Attempts</h3>

<?php if (!$rows): ?>
  <p style="opacity:.8;">No attempts found.</p>
<?php else: ?>
  <div style="overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">#</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Student</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Type</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Part</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Status</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Started</th>
          <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Submitted</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= (int)$r["id"] ?></td>
            <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h($r["student_name"] ?? ("User ".$r["user_id"])) ?></td>
            <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h($r["type"]) ?></td>
            <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= $r["part"] !== null ? (int)$r["part"] : "-" ?></td>
            <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h($r["status"]) ?></td>
            <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h($r["started_at"] ?? "-") ?></td>
            <td style="border-bottom:1px solid #f0f0f0;padding:8px;"><?= h($r["submitted_at"] ?? "-") ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
