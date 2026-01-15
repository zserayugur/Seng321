<?php
$path_prefix = "../";
$page = "student_assignments";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth_guard.php";

if (current_user_role() !== "LEARNER") {
  http_response_code(403);
  exit("Forbidden");
}

$student_id = current_user_id();

$stmt = $pdo->prepare("
  SELECT a.id, a.type, a.title, a.created_at, a.due_at,
         u.name AS instructor_name
  FROM assignments a
  JOIN users u ON u.id = a.instructor_id
  WHERE a.student_id=? AND a.status='pending'
  ORDER BY a.created_at DESC
");
$stmt->execute([$student_id]);
$assignments = $stmt->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>
<div class="container" style="max-width: 1000px; margin: 24px auto;">
  <h2>My Assignments</h2>

  <?php if (empty($assignments)): ?>
    <p>Şu an bekleyen ödevin yok 🎉</p>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
      <?php foreach ($assignments as $a): ?>
        <div style="border:1px solid #ddd;border-radius:12px;padding:14px;">
          <div style="font-weight:800;font-size:16px;">
            <?= htmlspecialchars(strtoupper($a['type'])) ?>
          </div>

          <div style="margin-top:8px;">
            <?php if (!empty($a['title'])): ?>
              <?= htmlspecialchars($a['title']) ?>
            <?php else: ?>
              <?= htmlspecialchars("Instructor sana {$a['type']} ödevi atadı.") ?>
            <?php endif; ?>
          </div>

          <div style="margin-top:10px;font-size:13px;opacity:.8;">
            From: <?= htmlspecialchars($a['instructor_name']) ?><br>
            Assigned: <?= htmlspecialchars($a['created_at']) ?><br>
            Due: <?= htmlspecialchars($a['due_at'] ?? '-') ?>
          </div>

          <div style="margin-top:12px;">
            <a href="/Seng321/assignments/start.php?id=<?= (int)$a['id'] ?>"
               style="display:inline-block;padding:8px 12px;border:1px solid #333;border-radius:10px;text-decoration:none;">
              Start
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
