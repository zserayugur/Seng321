<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

if (strtoupper($_SESSION["user"]["role"] ?? "") !== "INSTRUCTOR") {
  http_response_code(403);
  die("403 Forbidden");
}

$path_prefix = "../";
require_once __DIR__ . "/../includes/header.php";

$class_id = (int)($_GET["class_id"] ?? 0);
$instructor_id = (int)$_SESSION["user"]["id"];

$stmt = $pdo->prepare("
  SELECT id, title, class_code
  FROM classes
  WHERE id = ? AND instructor_id = ?
");
$stmt->execute([$class_id, $instructor_id]);
$class = $stmt->fetch();

if (!$class) {
  die("Class not found or unauthorized");
}

$stmt = $pdo->prepare("
  SELECT u.name, u.email, m.joined_at
  FROM class_members m
  JOIN users u ON u.id = m.student_id
  WHERE m.class_id = ?
  ORDER BY m.joined_at DESC
");
$stmt->execute([$class_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Class Members</h2>
<p><b><?= htmlspecialchars($class["title"]) ?></b> (<?= htmlspecialchars($class["class_code"]) ?>)</p>

<a href="/Seng321/instructor/class_codes.php">← Back</a>

<?php if (!$members): ?>
  <p>No approved students yet.</p>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0" style="margin-top:10px;">
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Joined At</th>
    </tr>
    <?php foreach ($members as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m["name"]) ?></td>
        <td><?= htmlspecialchars($m["email"]) ?></td>
        <td><?= htmlspecialchars($m["joined_at"]) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>