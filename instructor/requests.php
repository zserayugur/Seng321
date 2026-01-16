<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

$role = strtoupper($_SESSION["user"]["role"] ?? "");
if ($role !== "INSTRUCTOR") {
  http_response_code(403);
  die("403 Forbidden (Instructor only)");
}

$path_prefix = "../";
$page = "requests";
require_once __DIR__ . "/../includes/header.php";

$instructor_id = (int)$_SESSION["user"]["id"];

/* Instructor'a ait class'lara gelen join requestler */
$sql = "
SELECT
  r.id AS request_id,
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
$stmt->execute([$instructor_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Join Requests</h2>

<?php if (!$requests): ?>
  <p>No pending join requests.</p>
<?php else: ?>
  <table border="1" cellpadding="8" cellspacing="0">
    <tr>
      <th>Class</th>
      <th>Student</th>
      <th>Status</th>
      <th>Requested At</th>
      <th>Action</th>
    </tr>

    <?php foreach ($requests as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r["class_title"]) ?> (<?= htmlspecialchars($r["class_code"]) ?>)</td>
        <td><?= htmlspecialchars($r["student_name"]) ?><br><?= htmlspecialchars($r["student_email"]) ?></td>
        <td><?= htmlspecialchars($r["status"]) ?></td>
        <td><?= htmlspecialchars($r["requested_at"]) ?></td>
        <td>
          <?php if ($r["status"] === "pending"): ?>
            <a href="request_action.php?id=<?= (int)$r["request_id"] ?>&action=approve">Approve</a>
            |
            <a href="request_action.php?id=<?= (int)$r["request_id"] ?>&action=reject">Reject</a>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>