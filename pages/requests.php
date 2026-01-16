<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* 🔐 SADECE INSTRUCTOR GİRER */
if (
    !isset($_SESSION['user']) ||
    ($_SESSION['user']['role'] ?? '') !== 'INSTRUCTOR'
) {
    header("Location: /Seng321/login.php");
    exit;
}

$instructorId = (int) $_SESSION['user']['id'];

/* 📥 REQUESTLERİ ÇEK */
$sql = "
SELECT
  r.id AS request_id,
  r.status,
  r.requested_at,
  c.title AS class_title,
  c.class_code,
  u.full_name AS student_name
FROM class_join_requests r
JOIN classes c ON c.id = r.class_id
JOIN users u ON u.id = r.student_id
WHERE c.instructor_id = ?
ORDER BY 
  CASE r.status WHEN 'pending' THEN 1 ELSE 2 END,
  r.requested_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$instructorId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Join Requests</title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<div class="container">
  <h2 class="page-title">Join Requests</h2>

  <?php if (empty($requests)): ?>
    <div class="card">
      <p class="text-muted">No join requests yet.</p>
    </div>
  <?php else: ?>

    <?php foreach ($requests as $r): ?>
      <div class="card request-card">

        <div class="request-info">
          <strong><?= htmlspecialchars($r['student_name']) ?></strong>
          <p class="text-muted">
            wants to join <b><?= htmlspecialchars($r['class_title']) ?></b>
            (<?= htmlspecialchars($r['class_code']) ?>)
          </p>
          <small class="text-muted">
            <?= htmlspecialchars($r['requested_at']) ?>
          </small>
        </div>

        <div class="request-actions">
          <?php if ($r['status'] === 'pending'): ?>
            <span class="badge badge-pending">Pending</span>
            <button class="btn btn-success" disabled>Approve</button>
            <button class="btn btn-danger" disabled>Reject</button>
          <?php elseif ($r['status'] === 'approved'): ?>
            <span class="badge badge-approved">Approved</span>
          <?php else: ?>
            <span class="badge badge-rejected">Rejected</span>
          <?php endif; ?>
        </div>

      </div>
    <?php endforeach; ?>

  <?php endif; ?>
</div>

</body>
</html>
