<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

$role = strtoupper($_SESSION["user"]["role"] ?? "");
if ($role !== "LEARNER") {
  http_response_code(403);
  die("403 Forbidden (Learner only)");
}

$path_prefix = "../";
$page = "assignments";


$student_id = (int)($_SESSION["user"]["id"] ?? 0);

// Öğrencinin üye olduğu class'ların published assignment'ları
$stmt = $pdo->prepare("
  SELECT a.id, a.title, a.description, a.due_date, a.created_at,
         c.title AS class_title, c.class_code,
         u.name AS instructor_name
  FROM class_members m
  JOIN classes c ON c.id = m.class_id
  JOIN assignments a ON a.class_id = c.id
  JOIN users u ON u.id = a.instructor_id
  WHERE m.student_id = ?
    AND a.is_published = 1
  ORDER BY a.created_at DESC
");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Assignments</h2>

<?php if (count($rows) === 0): ?>
  <p style="color: var(--text-muted);">No published assignments yet.</p>
<?php else: ?>
  <div class="card" style="padding:16px;">
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%;">
      <tr>
        <th>Class</th>
        <th>Instructor</th>
        <th>Title</th>
        <th>Due</th>
      </tr>

      <?php foreach ($rows as $a): ?>
        <td>
  <?php
    $fs = $pdo->prepare("SELECT id, original_name FROM assignment_files WHERE assignment_id=? ORDER BY id DESC LIMIT 1");
    $fs->execute([(int)$a["id"]]);
    $f = $fs->fetch(PDO::FETCH_ASSOC);

    if ($f) {
      $fid = (int)$f["id"];
      echo '<a href="/Seng321/pages/download_assignment_file.php?id=' . $fid . '">Download</a>';
      echo '<div style="color:var(--text-muted); font-size:0.85rem;">' . htmlspecialchars($f["original_name"]) . '</div>';
    } else {
      echo '<span style="color:var(--text-muted);">-</span>';
    }
  ?>
</td>

        <tr>
          <td><?= htmlspecialchars($a["class_title"]) ?> (<?= htmlspecialchars($a["class_code"]) ?>)</td>
          <td><?= htmlspecialchars($a["instructor_name"] ?? "-") ?></td>
          <td>
            <b><?= htmlspecialchars($a["title"]) ?></b><br>
            <span style="color:var(--text-muted); font-size:0.9rem;">
              <?= nl2br(htmlspecialchars($a["description"] ?? "")) ?>
            </span>
          </td>
          <td><?= htmlspecialchars($a["due_date"] ?? "-") ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>