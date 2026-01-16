<?php
require_once __DIR__ . "/../includes/instructor_guard.php";
require_once __DIR__ . "/../config/db.php";

$path_prefix = "../";
require_once __DIR__ . "/../includes/header.php";

$instructorId = (int)($_SESSION["user"]["id"] ?? 0);

// Instructor'ın class'larına gelen pending istekleri çek
$sql = "
SELECT
  r.id AS request_id,
  r.class_id,
  r.student_id,
  r.status,
  r.requested_at,
  c.title AS class_title,
  c.class_code,
  u.name AS student_name,
  u.email AS student_email
FROM class_join_requests r
JOIN classes c ON c.id = r.class_id
JOIN users u ON u.id = r.student_id
WHERE c.instructor_id = ?
ORDER BY r.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$instructorId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Join Requests</h2>

<?php if (isset($_GET["ok"])): ?>
  <p style="color:#9fffb0;">Action completed.</p>
<?php endif; ?>

<?php if (!$requests): ?>
  <p>No requests yet.</p>
<?php else: ?>
  <table border="1" cellpadding="8" cellspacing="0" style="margin-top:10px;">
    <tr>
      <th>Class</th>
      <th>Code</th>
      <th>Student</th>
      <th>Email</th>
      <th>Status</th>
      <th>Requested At</th>
      <th>Action</th>
    </tr>

    <?php foreach ($requests as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r["class_title"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["class_code"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["student_name"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["student_email"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["status"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["requested_at"] ?? "") ?></td>
        <td>
          <?php if (($r["status"] ?? "") === "pending"): ?>
            <form method="post" action="/Seng321/instructor/request_action.php" style="display:inline;">
              <input type="hidden" name="request_id" value="<?= (int)$r["request_id"] ?>">
              <button name="action" value="approve" type="submit">Approve</button>
            </form>

            <form method="post" action="/Seng321/instructor/request_action.php" style="display:inline;">
              <input type="hidden" name="request_id" value="<?= (int)$r["request_id"] ?>">
              <button name="action" value="reject" type="submit">Reject</button>
            </form>
          <?php else: ?>
            <span>-</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>