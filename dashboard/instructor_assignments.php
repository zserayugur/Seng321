<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../includes/csrf.php";

// Role check
if (current_user_role() !== "INSTRUCTOR") {
  http_response_code(403);
  exit("Forbidden");
}

$instructor_id = current_user_id();
$errors = [];
$success = "";

$allowed_types = ['writing','speaking','listening','vocabulary','grammar','reading'];

// Öğrencileri çek
$students = [];
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role='LEARNER' AND active=1 ORDER BY name ASC");
if ($stmt && $stmt->execute()) {
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $students[] = $row;
  $stmt->close();
} else {
  $errors[] = "Öğrenciler çekilemedi (DB).";
}

// Form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate($_POST['csrf_token'] ?? null);

  $student_id = (int)($_POST['student_id'] ?? 0);
  $type = strtolower(trim($_POST['type'] ?? ''));
  $title = trim($_POST['title'] ?? '');
  $due_at_raw = trim($_POST['due_at'] ?? '');

  if ($student_id <= 0) $errors[] = "Öğrenci seçmelisin.";
  if (!in_array($type, $allowed_types, true)) $errors[] = "Geçersiz assignment türü.";

  // due_at opsiyonel (datetime-local => 2026-01-15T19:30)
  $due_at = null;
  if ($due_at_raw !== '') {
    $due_at_raw2 = str_replace('T', ' ', $due_at_raw) . ":00";
    $dt = date_create($due_at_raw2);
    if ($dt === false) {
      $errors[] = "Teslim tarihi hatalı.";
    } else {
      $due_at = $dt->format('Y-m-d H:i:s');
    }
  }

  // öğrenci gerçekten LEARNER mı?
  if (empty($errors)) {
    $check = $conn->prepare("SELECT id FROM users WHERE id=? AND role='LEARNER' LIMIT 1");
    $check->bind_param("i", $student_id);
    $check->execute();
    $checkRes = $check->get_result();
    if ($checkRes->num_rows === 0) $errors[] = "Seçilen kullanıcı LEARNER değil.";
    $check->close();
  }

  if (empty($errors)) {
    $ins = $conn->prepare("
      INSERT INTO assignments (instructor_id, student_id, type, title, due_at)
      VALUES (?, ?, ?, ?, ?)
    ");

    $title_db = ($title === '') ? null : $title;
    $due_db = $due_at; // null olabilir

    // null değerler için bind_param yine de çalışır
    $ins->bind_param("iisss", $instructor_id, $student_id, $type, $title_db, $due_db);

    if ($ins->execute()) {
      $success = "Assignment başarıyla atandı.";
    } else {
      $errors[] = "DB hatası: " . $conn->error;
    }
    $ins->close();
  }
}

// Instructor’ın atadıkları
$assigned = [];
$stmt2 = $conn->prepare("
  SELECT a.id, a.type, a.status, a.title, a.created_at, a.due_at,
         u.name AS student_name, u.email AS student_email
  FROM assignments a
  JOIN users u ON u.id = a.student_id
  WHERE a.instructor_id=?
  ORDER BY a.created_at DESC
  LIMIT 200
");
$stmt2->bind_param("i", $instructor_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) $assigned[] = $row;
$stmt2->close();

require_once __DIR__ . "/../includes/header.php";
?>
<div class="container" style="max-width: 1000px; margin: 24px auto;">
  <h2>Instructor - Assignments</h2>

  <?php if (!empty($success)): ?>
    <div style="padding:10px;border:1px solid #4caf50;background:#e8f5e9;margin:12px 0;border-radius:8px;">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div style="padding:10px;border:1px solid #f44336;background:#ffebee;margin:12px 0;border-radius:8px;">
      <ul style="margin:0;padding-left:18px;">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div style="padding:16px;border:1px solid #ddd;border-radius:12px;margin:16px 0;">
    <h3>Yeni Assignment Ata</h3>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:260px;">
          <label>Öğrenci</label><br>
          <select name="student_id" required style="width:100%;padding:8px;">
            <option value="">Seç...</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= (int)$s['id'] ?>">
                <?= htmlspecialchars($s['name'] . " (" . $s['email'] . ")") ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="flex:1;min-width:240px;">
          <label>Tür</label><br>
          <select name="type" required style="width:100%;padding:8px;">
            <?php foreach ($allowed_types as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;">
        <div style="flex:2;min-width:260px;">
          <label>Başlık (opsiyonel)</label><br>
          <input type="text" name="title" placeholder="Örn: Writing practice #3" style="width:100%;padding:8px;">
        </div>

        <div style="flex:1;min-width:240px;">
          <label>Teslim Tarihi (opsiyonel)</label><br>
          <input type="datetime-local" name="due_at" style="width:100%;padding:8px;">
        </div>
      </div>

      <div style="margin-top:12px;">
        <button type="submit" style="padding:10px 14px;cursor:pointer;">Ata</button>
      </div>
    </form>
  </div>

  <div style="padding:16px;border:1px solid #ddd;border-radius:12px;margin:16px 0;">
    <h3>Atadıklarım</h3>

    <?php if (empty($assigned)): ?>
      <p>Henüz assignment yok.</p>
    <?php else: ?>
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;border-bottom:1px solid #ccc;padding:8px;">Öğrenci</th>
            <th style="text-align:left;border-bottom:1px solid #ccc;padding:8px;">Tür</th>
            <th style="text-align:left;border-bottom:1px solid #ccc;padding:8px;">Durum</th>
            <th style="text-align:left;border-bottom:1px solid #ccc;padding:8px;">Atanma</th>
            <th style="text-align:left;border-bottom:1px solid #ccc;padding:8px;">Due</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assigned as $a): ?>
            <tr>
              <td style="padding:8px;border-bottom:1px solid #eee;">
                <?= htmlspecialchars($a['student_name'] . " (" . $a['student_email'] . ")") ?>
              </td>
              <td style="padding:8px;border-bottom:1px solid #eee;"><?= htmlspecialchars($a['type']) ?></td>
              <td style="padding:8px;border-bottom:1px solid #eee;"><?= htmlspecialchars($a['status']) ?></td>
              <td style="padding:8px;border-bottom:1px solid #eee;"><?= htmlspecialchars($a['created_at']) ?></td>
              <td style="padding:8px;border-bottom:1px solid #eee;"><?= htmlspecialchars($a['due_at'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
